<?php

namespace App\Services;

use App\Models\KnowledgeBase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class PineconeFetchService
{
    private string $apiKey;
    private string $indexName;

    public function __construct()
    {
        $this->apiKey = (string) config('services.pinecone.api_key');
        $this->indexName = (string) config('services.pinecone.index_name', 'bapenda-kb');
    }

    /**
     * Get Pinecone index host URL.
     */
    public function getIndexHost(): ?string
    {
        try {
            $response = Http::timeout(30)->withHeaders([
                'Api-Key' => $this->apiKey,
            ])->get("https://api.pinecone.io/indexes/{$this->indexName}");

            if ($response->successful()) {
                $data = $response->json();
                $host = $data['host'] ?? null;
                return $host ? "https://{$host}" : null;
            }

            Log::error('Failed to get Pinecone index host: ' . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error('PineconeFetchService::getIndexHost Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Fetch all vectors from Pinecone and synchronize with MySQL database.
     *
     * @param bool $dryRun If true, analyze without modifying database
     * @param callable|null $onProgress Optional callback for progress reporting: fn($stage, $percent, $message)
     * @return array ['success' => bool, 'message' => string, 'stats' => array, 'documents' => array]
     */
    public function fetchAndSyncToDatabase(bool $dryRun = false, ?callable $onProgress = null): array
    {
        $report = function (string $stage, int $percent, string $message) use ($onProgress) {
            if ($onProgress && is_callable($onProgress)) {
                $onProgress($stage, $percent, $message);
            }
        };

        $report('connect', 10, 'Menghubungkan ke Pinecone Vector Database Cluster...');

        $host = $this->getIndexHost();
        if (!$host) {
            return [
                'success' => false,
                'message' => 'Gagal menghubungkan ke host Pinecone index.',
                'stats' => null,
                'documents' => []
            ];
        }

        // ── Phase 1: Retrieve all vector IDs ─────────────────────────────────
        $report('list_vectors', 25, 'Memindai daftar seluruh Vector IDs di Pinecone...');

        $allVectorIds = [];
        $paginationToken = null;

        do {
            $url = "{$host}/vectors/list?limit=100" . ($paginationToken ? "&paginationToken=" . urlencode($paginationToken) : "");
            $res = Http::timeout(30)->withHeaders(['Api-Key' => $this->apiKey])->get($url);

            if (!$res->successful()) {
                Log::error('Pinecone vectors list error: ' . $res->body());
                break;
            }

            $data = $res->json();
            foreach ($data['vectors'] ?? [] as $v) {
                if (!empty($v['id'])) {
                    $allVectorIds[] = $v['id'];
                }
            }
            $paginationToken = $data['pagination']['next'] ?? null;
        } while (!empty($paginationToken));

        $totalVectors = count($allVectorIds);
        if ($totalVectors === 0) {
            return [
                'success' => true,
                'message' => 'Tidak ada vektor yang ditemukan di Pinecone index.',
                'stats' => [
                    'total_vectors' => 0,
                    'db_created' => 0,
                    'db_updated' => 0,
                    'db_unchanged' => 0,
                    'categories' => []
                ],
                'documents' => []
            ];
        }

        $report('fetch_metadata', 45, "Mengambil metadata untuk {$totalVectors} vektor...");

        // ── Phase 2: Batch fetch vector payloads ─────────────────────────────
        $documents = [];
        $chunks = array_chunk($allVectorIds, 50);

        foreach ($chunks as $chunkIdx => $chunkIds) {
            $fetchUrl = "{$host}/vectors/fetch?" . implode('&', array_map(fn($id) => 'ids=' . urlencode($id), $chunkIds));
            $res = Http::timeout(30)->withHeaders(['Api-Key' => $this->apiKey])->get($fetchUrl);

            if ($res->successful()) {
                $fetched = $res->json()['vectors'] ?? [];
                foreach ($fetched as $vId => $vData) {
                    $meta = $vData['metadata'] ?? [];
                    
                    // Identify original ID or composite key
                    $kbId = isset($meta['kb_id']) && is_numeric($meta['kb_id']) ? (int)$meta['kb_id'] : null;
                    $docKey = $kbId ?: $vId;
                    $title = trim($meta['title'] ?? 'Dokumen Tanpa Judul');

                    if (!isset($documents[$docKey])) {
                        $documents[$docKey] = [
                            'kb_id' => $kbId,
                            'title' => $title,
                            'category' => $meta['category'] ?? 'pajak',
                            'type' => $meta['type'] ?? 'faq',
                            'ai_instructions' => $meta['ai_instructions'] ?? null,
                            'keywords' => $meta['keywords'] ?? '',
                            'chunks' => []
                        ];
                    }

                    $chunkIndex = (int)($meta['chunk_index'] ?? 0);
                    $chunkText = $meta['chunk_text'] ?? $meta['content'] ?? $meta['answer'] ?? '';
                    $documents[$docKey]['chunks'][$chunkIndex] = $chunkText;
                }
            }

            $currentProgress = 45 + (int)(($chunkIdx + 1) / count($chunks) * 30);
            $report('fetch_metadata', min($currentProgress, 75), "Berhasil mengunduh metadata batch " . ($chunkIdx + 1) . " dari " . count($chunks) . "...");
        }

        $report('reconstruct', 80, "Rekonstruksi " . count($documents) . " artikel Knowledge Base...");

        // ── Phase 3: Synchronize with MySQL Database ─────────────────────────
        $stats = [
            'total_vectors' => $totalVectors,
            'total_docs' => count($documents),
            'db_created' => 0,
            'db_updated' => 0,
            'db_unchanged' => 0,
            'categories' => []
        ];

        $docSummary = [];

        if (!$dryRun) {
            $report('sync_mysql', 90, 'Menyimpan dan menyinkronkan data ke tabel database MySQL...');

            DB::beginTransaction();
            try {
                KnowledgeBase::withoutEvents(function () use ($documents, &$stats, &$docSummary) {
                    foreach ($documents as $docKey => $doc) {
                        ksort($doc['chunks']);
                        $assembledContent = trim(implode("\n\n", $doc['chunks']));
                        $title = $doc['title'];
                        $category = $doc['category'] ?: 'pajak';
                        $type = $doc['type'] ?: 'faq';
                        $aiInstructions = $doc['ai_instructions'];

                        $stats['categories'][$category] = ($stats['categories'][$category] ?? 0) + 1;

                        // Match by kb_id if available, or by title
                        $existingKb = null;
                        if ($doc['kb_id']) {
                            $existingKb = KnowledgeBase::find($doc['kb_id']);
                        }
                        if (!$existingKb) {
                            $existingKb = KnowledgeBase::where('title', $title)->first();
                        }

                        $cleanAnswer = strip_tags($assembledContent);
                        $cleanSearch = KnowledgeBase::cleanHtml($title . ' ' . $assembledContent . ' ' . ($aiInstructions ?? ''));

                        if ($existingKb) {
                            $existingKb->update([
                                'title' => $title,
                                'content' => $assembledContent,
                                'answer' => $cleanAnswer,
                                'search_content' => $cleanSearch,
                                'category' => $category,
                                'type' => $type,
                                'ai_instructions' => $aiInstructions,
                                'status' => 'published',
                                'is_active' => true,
                                'published_at' => $existingKb->published_at ?? now(),
                                'quality_score' => $existingKb->quality_score ?: 0.80,
                            ]);
                            $stats['db_updated']++;
                            $docSummary[] = [
                                'id' => $existingKb->id,
                                'title' => $title,
                                'category' => $category,
                                'action' => 'updated'
                            ];
                        } else {
                            $newKb = KnowledgeBase::create([
                                'title' => $title,
                                'content' => $assembledContent,
                                'answer' => $cleanAnswer,
                                'search_content' => $cleanSearch,
                                'category' => $category,
                                'type' => $type,
                                'ai_instructions' => $aiInstructions,
                                'status' => 'published',
                                'is_active' => true,
                                'published_at' => now(),
                                'quality_score' => 0.80,
                                'priority' => 5,
                                'view_count' => 0,
                            ]);
                            $stats['db_created']++;
                            $docSummary[] = [
                                'id' => $newKb->id,
                                'title' => $title,
                                'category' => $category,
                                'action' => 'created'
                            ];
                        }
                    }
                });

                DB::commit();
                \App\Services\OpenAIService::clearResponseCache();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('PineconeFetchService database sync failed: ' . $e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan ke MySQL: ' . $e->getMessage(),
                    'stats' => $stats,
                    'documents' => []
                ];
            }
        } else {
            // Dry run counting
            foreach ($documents as $docKey => $doc) {
                $category = $doc['category'] ?: 'pajak';
                $stats['categories'][$category] = ($stats['categories'][$category] ?? 0) + 1;
                $docSummary[] = [
                    'id' => $doc['kb_id'],
                    'title' => $doc['title'],
                    'category' => $category,
                    'action' => 'preview'
                ];
            }
            $stats['db_created'] = count($documents);
        }

        $report('complete', 100, 'Sinkronisasi Pinecone ke MySQL selesai dengan sukses!');

        return [
            'success' => true,
            'message' => $dryRun
                ? "Dry run berhasil: Ditemukan {$stats['total_docs']} artikel Knowledge Base ({$totalVectors} vektor) di Pinecone."
                : "Sinkronisasi berhasil! {$stats['db_created']} artikel baru dibuat dan {$stats['db_updated']} artikel diperbarui di MySQL.",
            'stats' => $stats,
            'documents' => array_slice($docSummary, 0, 50),
            'dry_run' => $dryRun
        ];
    }
}
