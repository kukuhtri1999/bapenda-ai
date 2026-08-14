<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Services\EmbeddingService;
use App\Services\PineconeService;
use Illuminate\Support\Facades\Log;

class KnowledgeBase extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'question',
        'answer',
        'excerpt',
        'content',
        'ai_instructions',
        'category',
        'type',
        'source_type',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
        'mime_type',
        'images',
        'metadata',
        'keywords',
        'tags',
        'is_active',
        'priority',
        'published_at',
        'status',
        'created_by',
        'updated_by',
        'search_content',
        'quality_score',
        'quality_scored_at',
    ];

    protected $casts = [
        'keywords' => 'array',
        'metadata' => 'array',
        'tags' => 'array',
        'is_active' => 'boolean',
        'priority' => 'integer',
        'view_count' => 'integer',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'images' => 'array',
        'quality_score' => 'float',
        'quality_scored_at' => 'datetime',
    ];

    protected $dates = [
        'published_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Boot method to handle model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->generateSearchContent();
        });

        static::updating(function ($model) {
            $model->generateSearchContent();
        });

        static::created(function ($model) {
            $model->increment('view_count', 0); // Initialize view count
            // Index to vector database after creation
            $model->indexToVectorDatabase();
            // Clear AI response cache to ensure fresh KB context is used
            \App\Services\OpenAIService::clearResponseCache();
        });

        static::updated(function ($model) {
            // Update vector database when model is updated
            $model->updateVectorDatabase();
            // Clear AI response cache to ensure fresh KB context is used
            \App\Services\OpenAIService::clearResponseCache();
        });

        static::deleted(function ($model) {
            // Remove from vector database when deleted
            $model->removeFromVectorDatabase();
            // Clear AI response cache to ensure fresh KB context is used
            \App\Services\OpenAIService::clearResponseCache();
        });
    }

    /**
     * Convert raw HTML/XML field value to clean plain text for indexing and AI context.
     * - Removes <?xml ...?> / <?...?> processing instructions (strip_tags misses these)
     * - Strips all remaining HTML tags
     * - Decodes HTML entities (&middot; &amp; &lt; &#8203; etc.)
     * - Collapses whitespace
     */
    public static function cleanHtml(string $html): string
    {
        // Remove XML / HTML processing instructions like <?xml encoding="UTF-8">
        $text = preg_replace('/<\?[^>]*\?>?/', ' ', $html);
        // Strip remaining HTML tags
        $text = strip_tags($text);
        // Decode all HTML entities to real UTF-8 characters
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // Collapse multiple whitespace / newlines into single space
        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * Generate searchable content from all text fields.
     * Stored in search_content which is covered by a FULLTEXT index on (title, search_content).
     * Called automatically by Eloquent boot events on creating/updating.
     */
    public function generateSearchContent(): void
    {
        $searchContent = collect([
            $this->title,
            // prefer explicit excerpt if present
            $this->attributes['excerpt'] ?? null,
            // clean both body fields — strip HTML tags AND decode entities
            static::cleanHtml((string)($this->content ?? '')),
            static::cleanHtml((string)($this->answer ?? '')),
            // structured metadata
            $this->question,
            $this->category,
            is_array($this->keywords) ? implode(' ', $this->keywords) : ($this->keywords ?? ''),
            // tags may be an array (JSON column) or string
            is_array($this->tags) ? implode(' ', $this->tags) : ($this->tags ?? ''),
        ])->map(fn($v) => trim((string) $v))->filter()->implode(' ');

        $this->search_content = $searchContent;
    }

    /**
     * Relationships
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSourceType($query, $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('title', 'LIKE', "%{$term}%")
                ->orWhere('question', 'LIKE', "%{$term}%")
                ->orWhere('excerpt', 'LIKE', "%{$term}%")
                ->orWhere('content', 'LIKE', "%{$term}%")
                ->orWhere('answer', 'LIKE', "%{$term}%")
                ->orWhere('search_content', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Accessors & Mutators
     */
    public function getExcerptAttribute($value)
    {
        return $value ?: Str::limit(strip_tags($this->content), 200);
    }

    public function getFormattedSizeAttribute()
    {
        if (!$this->file_size) return null;

        $bytes = floatval($this->file_size);
        $units = ['B', 'KB', 'MB', 'GB'];
        $precision = 2;

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    // Removed CSV-based tag mutator/accessor; using JSON cast instead

    /**
     * Helper methods
     */
    public function incrementViewCount()
    {
        $this->increment('view_count');
    }

    public function isManual()
    {
        return $this->source_type === 'manual';
    }

    public function isFile()
    {
        return $this->source_type === 'file';
    }

    public function getFileUrl()
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    /**
     * Static methods for categories and types
     */
    public static function getCategories()
    {
        return [
            'pajak' => 'Pajak Kendaraan',
            'stnk' => 'STNK & BPKB',
            'lokasi' => 'Lokasi & Jam Operasional',
            'syarat' => 'Syarat & Dokumen',
            'tarif' => 'Tarif & Biaya',
            'prosedur' => 'Prosedur & Tata Cara',
            'denda' => 'Denda & Sanksi',
            'online' => 'Layanan Online',
            'umum' => 'Informasi Umum',
        ];
    }

    public static function getTypes()
    {
        return [
            'faq' => 'FAQ (Frequently Asked Questions)',
            'sop' => 'SOP (Standard Operating Procedure)',
            'regulation' => 'Peraturan & Kebijakan',
            'guide' => 'Panduan & Tutorial',
            'announcement' => 'Pengumuman',
            'tambahan_sistem' => 'Koreksi & Override AI',
        ];
    }

    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
            'archived' => 'Archived',
        ];
    }

    /**
     * Vector Database Operations
     */

    /**
     * Index this knowledge base entry to vector database.
     *
     * One KB entry = exactly ONE Pinecone vector.
     * Users are expected to split large documents into separate files before
     * uploading; the system should not fragment a single file into multiple entries.
     *
     * Embedding strategy:
     *   - Input text truncated to 7 000 chars (≈ 5 500 tokens, safely within
     *     text-embedding-3-small's 8 191-token limit).
     *   - metadata.chunk_text stores up to 8 000 chars of contexturalised text
     *     which the AI uses when constructing answers.
     */
    public function indexToVectorDatabase(): bool
    {
        try {
            // Only index active and published entries
            if (!$this->is_active || $this->status !== 'published') {
                Log::info("Skipping vector indexing for KB {$this->id}: inactive or unpublished");
                return true;
            }

            $embeddingService = app(EmbeddingService::class);
            $pineconeService  = app(PineconeService::class);

            $content = $this->getContentForEmbedding();
            if (empty($content)) {
                Log::info("Skipping vector indexing for KB {$this->id}: empty content");
                return true;
            }

            // Build contextual prefix (anchors embedding in document identity)
            $contextPrefix = "[Sumber: {$this->title}]";
            if (!empty($this->category)) {
                $contextPrefix .= "\n[Kategori: {$this->category}]";
            }

            // 7 000 chars for the embedding call; 8 000 chars stored in metadata
            $embeddingText = $contextPrefix . "\n\n" . mb_substr($content, 0, 7000);
            $metadataText  = $contextPrefix . "\n\n" . mb_substr($content, 0, 8000);

            $embedding = $embeddingService->embed($embeddingText);
            if (!$embedding) {
                Log::error("Failed to generate embedding for KB {$this->id}");
                return false;
            }

            // Vector ID is a stable, unique key — 'kb_{id}' — one per entry
            $vector = $embeddingService->createVectorData(
                id: 'kb_' . $this->id,
                embedding: $embedding,
                metadata: [
                    'kb_id'           => $this->id,
                    'title'           => $this->title,
                    'category'        => $this->category,
                    'type'            => $this->type,
                    'chunk_text'      => $metadataText,
                    'ai_instructions' => $this->ai_instructions ?? '',
                    'source_type'     => $this->source_type,
                    'is_active'       => $this->is_active,
                    'status'          => $this->status,
                    'created_at'      => $this->created_at?->toISOString() ?? '',
                    'updated_at'      => $this->updated_at?->toISOString() ?? '',
                ]
            );

            $success = $pineconeService->upsert([$vector]);
            if ($success) {
                Log::info("Indexed KB {$this->id} as single vector kb_{$this->id}");
            }
            return $success;
        } catch (\Exception $e) {
            Log::error("Failed to index KB {$this->id} to vector database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update this entry in vector database
     */
    public function updateVectorDatabase(): bool
    {
        try {
            // Check if content or active status changed
            $contentChanged = $this->isDirty(['title', 'content', 'answer', 'category', 'type', 'ai_instructions']);
            $statusChanged = $this->isDirty(['is_active', 'status']);

            if (!$contentChanged && !$statusChanged) {
                return true; // No relevant changes
            }

            if (!$this->is_active || $this->status !== 'published') {
                // If now inactive or unpublished, remove from vector database
                return $this->removeFromVectorDatabase();
            }

            if ($contentChanged) {
                // Content changed, need to re-index completely
                $this->removeFromVectorDatabase();
                return $this->indexToVectorDatabase();
            }

            if ($statusChanged) {
                // Only status changed, update metadata in existing vectors
                return $this->updateVectorMetadata();
            }

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update KB {$this->id} in vector database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update the single vector for this entry when only status/active changed.
     * Re-uses indexToVectorDatabase since Pinecone upsert is idempotent.
     */
    private function updateVectorMetadata(): bool
    {
        return $this->indexToVectorDatabase();
    }

    /**
     * Remove this entry from vector database
     */
    public function removeFromVectorDatabase(): bool
    {
        try {
            $pineconeService = app(PineconeService::class);

            // Delete all chunks for this KB entry
            return $pineconeService->deleteByFilter([
                'kb_id' => ['$eq' => $this->id]
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to remove KB {$this->id} from vector database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get content for embedding.
     * Only uses UNIQUE fields — does NOT include 'answer' (mirror of content)
     * or 'question' (mirror of title) to avoid tripling content in vector space.
     */
    private function getContentForEmbedding(): string
    {
        // Use the already-clean search_content when available.
        // If absent, fall back to cleaning content/answer on the fly.
        $cleanText = !empty($this->search_content)
            ? $this->search_content
            : static::cleanHtml((string) ($this->content ?: $this->answer ?? ''));

        $parts = array_filter([
            $this->title,
            $this->category ? '[Category: ' . $this->category . ']' : null,
            $cleanText,
        ]);

        return implode("\n\n", $parts);
    }

    /**
     * Manually re-index this entry to vector database
     */
    public function reindexToVectorDatabase(): bool
    {
        $this->removeFromVectorDatabase();
        return $this->indexToVectorDatabase();
    }
}
