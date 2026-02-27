<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use App\Models\KbBatchUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\PDFParserService;
use App\Services\DocxParserService;
use App\Services\DocumentChunkingService;
use Illuminate\Support\Facades\Log;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = KnowledgeBase::query()
            ->with(['creator', 'updater'])
            ->latest();

        // Apply filters
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->filled('category')) {
            $query->category($request->category);
        }

        if ($request->filled('type')) {
            $query->type($request->type);
        }

        if ($request->filled('source_type')) {
            $query->sourceType($request->source_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $knowledgeBases = $query->paginate(15)->withQueryString();

        return Inertia::render('KnowledgeBase/Index', [
            'knowledgeBases' => $knowledgeBases,
            'filters' => $request->only(['search', 'category', 'type', 'source_type', 'status', 'is_active']),
            'categories' => KnowledgeBase::getCategories(),
            'types' => KnowledgeBase::getTypes(),
            'statuses' => KnowledgeBase::getStatuses(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('KnowledgeBase/Create', [
            'categories' => KnowledgeBase::getCategories(),
            'types' => KnowledgeBase::getTypes(),
            'statuses' => KnowledgeBase::getStatuses(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Raise PHP limits at runtime for large file uploads (100+ page PDFs).
        // These are belt-and-suspenders on top of .htaccess / php.ini settings.
        @ini_set('max_execution_time', 300);
        @ini_set('max_input_time', 300);
        @ini_set('memory_limit', '512M');
        @set_time_limit(300);

        // Debugging help: log session and CSRF presence (temporary)
        try {
            \Illuminate\Support\Facades\Log::info('KB.store debug', [
                'session_id' => session()->getId(),
                'session_cookie' => (bool) $request->cookie(session()->getName()),
                'x_csrf_header' => (bool) $request->header('X-CSRF-TOKEN')
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => $request->source_type === 'file' ? 'nullable|string' : 'required|string',
            'category' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'source_type' => 'required|in:manual,file',
            'tags' => 'nullable', // allow string or array; normalized below
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',

            // File upload validation
            'file' => $request->source_type === 'file' ? 'required|file|mimes:pdf,doc,docx,txt,md|max:51200' : 'nullable|file|mimes:pdf,doc,docx,txt,md|max:51200',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        // Normalize tags to JSON array for DB JSON column
        // NOTE: question/answer are filled AFTER file extraction below
        if (isset($data['tags'])) {
            if (is_string($data['tags'])) {
                $arr = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
                $data['tags'] = $arr;
            } elseif (is_array($data['tags'])) {
                $data['tags'] = array_values(array_filter(array_map('trim', $data['tags'])));
            }
        }
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Handle file upload for file source type
        if ($request->source_type === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');

            // Check if it's a PDF and extract text
            if ($file->getMimeType() === 'application/pdf' || strtolower($file->getClientOriginalExtension()) === 'pdf') {
                try {
                    $pdfParser = app(PDFParserService::class);
                    $pdfData = $pdfParser->processPDFForKnowledgeBase($file);

                    // Update data with PDF content
                    if (empty($data['title']) || $data['title'] === $file->getClientOriginalName()) {
                        $data['title'] = $pdfData['title'];
                    }
                    if (empty($data['content'])) {
                        $data['content'] = $pdfData['content'];
                    }
                    $data['metadata'] = array_merge($data['metadata'] ?? [], $pdfData['metadata']);
                } catch (\Exception $e) {
                    Log::error('PDF processing failed: ' . $e->getMessage());
                    $errorMessage = 'Failed to process PDF file: ' . $e->getMessage();
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['file' => [$errorMessage]],
                        ], 422);
                    }
                    return back()->withErrors(['file' => $errorMessage])->withInput();
                }
            }

            // Check if it's a DOCX or DOC and extract text
            $docExtension = strtolower($file->getClientOriginalExtension());
            if (in_array($docExtension, ['docx', 'doc'])) {
                try {
                    $docxParser = app(DocxParserService::class);
                    $extractedText = $docxParser->extractText($file);

                    if ($extractedText) {
                        if (empty($data['content'])) {
                            $data['content'] = $extractedText;
                        }
                        $docMeta = $docxParser->extractMetadata($file);
                        if (!empty($docMeta['title']) && (empty($data['title']) || $data['title'] === $file->getClientOriginalName())) {
                            $data['title'] = $docMeta['title'];
                        }
                        $data['metadata'] = array_merge($data['metadata'] ?? [], array_filter($docMeta));
                    } else {
                        Log::warning('DocxParserService returned empty text for: ' . $file->getClientOriginalName());
                    }
                } catch (\Exception $e) {
                    Log::error('DOCX processing failed: ' . $e->getMessage());
                    $errorMessage = 'Failed to process Word document: ' . $e->getMessage();
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['file' => [$errorMessage]],
                        ], 422);
                    }
                    return back()->withErrors(['file' => $errorMessage])->withInput();
                }
            }

            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('knowledge-base', $fileName, 'public');

            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();

            // Extract content from text files if content is empty
            $isTextFile = in_array($file->getMimeType(), ['text/plain', 'text/markdown', 'text/x-markdown'])
                || in_array(strtolower($file->getClientOriginalExtension()), ['txt', 'md']);
            if (empty($data['content']) && $isTextFile) {
                $data['content'] = file_get_contents($file->getRealPath());
            }
        }

        // Validate that content exists after file processing for file uploads.
        // Treat both null and empty-string as failure (empty string crashes DB NOT NULL).
        $contentIsEmpty = empty($data['content']) || trim((string) $data['content']) === '';
        if ($request->source_type === 'file' && $contentIsEmpty) {
            $errorMessage = 'Could not extract readable text from the uploaded file. '
                . 'If this is a scanned or image-based PDF, please convert it to a text-selectable PDF '
                . 'or paste the content manually using the Manual Entry option.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['file' => [$errorMessage]],
                ], 422);
            }
            return back()->withErrors(['file' => $errorMessage])->withInput();
        }

        // NOW set question/answer after content has been fully resolved from file
        // (These are legacy NOT NULL columns — we make them mirror title/content)
        $data['question'] = $data['title'] ?? null;
        $data['answer']   = $data['content'] ?? null;

        // Process embedded base64 images in HTML content (Quill) and move to storage
        if (!empty($data['content'])) {
            [$processedHtml, $images] = $this->extractAndStoreEmbeddedImages($data['content']);
            $data['content'] = $processedHtml;
            if (!empty($images)) {
                $data['images'] = $images;
            }
        }

        // Set published_at if status is published and no date specified
        if ($data['status'] === 'published' && (empty($data['published_at']))) {
            $data['published_at'] = now();
        }

        $knowledgeBase = KnowledgeBase::create($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Knowledge base entry created successfully.',
                'knowledge_base' => $knowledgeBase,
                'redirect' => route('knowledge-base.show', $knowledgeBase)
            ], 201);
        }

        return redirect()
            ->route('knowledge-base.show', $knowledgeBase)
            ->with('success', 'Knowledge base entry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(KnowledgeBase $knowledgeBase)
    {
        $knowledgeBase->load(['creator', 'updater']);
        $knowledgeBase->incrementViewCount();

        return Inertia::render('KnowledgeBase/Show', [
            'knowledgeBase' => $knowledgeBase,
            'categories' => KnowledgeBase::getCategories(),
            'types' => KnowledgeBase::getTypes(),
            'statuses' => KnowledgeBase::getStatuses(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KnowledgeBase $knowledgeBase)
    {
        return Inertia::render('KnowledgeBase/Edit', [
            'knowledgeBase' => $knowledgeBase,
            'categories' => KnowledgeBase::getCategories(),
            'types' => KnowledgeBase::getTypes(),
            'statuses' => KnowledgeBase::getStatuses(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase)
    {
        // Debugging help: log session and CSRF presence (temporary)
        try {
            \Illuminate\Support\Facades\Log::info('KB.update debug', [
                'session_id' => session()->getId(),
                'session_cookie' => (bool) $request->cookie(session()->getName()),
                'x_csrf_header' => (bool) $request->header('X-CSRF-TOKEN')
            ]);
        } catch (\Throwable $e) {
            // ignore logging failures
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'tags' => 'nullable', // allow string or array
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            // Add file validation for updates
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,md|max:10240',
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['question'] = $data['title'] ?? ($data['question'] ?? null);
        $data['answer'] = $data['content'] ?? ($data['answer'] ?? null);
        if (isset($data['tags'])) {
            if (is_string($data['tags'])) {
                $arr = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
                $data['tags'] = $arr;
            } elseif (is_array($data['tags'])) {
                $data['tags'] = array_values(array_filter(array_map('trim', $data['tags'])));
            }
        }
        $data['updated_by'] = Auth::id();

        // Set published_at if status changed to published and no date specified
        if ($data['status'] === 'published' && $knowledgeBase->status !== 'published' && (empty($data['published_at']))) {
            $data['published_at'] = now();
        }

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            // Check if it's a PDF and extract text
            if ($file->getMimeType() === 'application/pdf') {
                try {
                    $pdfParser = app(PDFParserService::class);
                    $pdfData = $pdfParser->processPDFForKnowledgeBase($file);

                    // Update data with PDF content
                    $data['content'] = $pdfData['content'];
                    $data['metadata'] = array_merge($data['metadata'] ?? [], $pdfData['metadata']);
                } catch (\Exception $e) {
                    Log::error('PDF processing failed: ' . $e->getMessage());
                    $errorMessage = 'Failed to process PDF file: ' . $e->getMessage();
                    if ($request->expectsJson()) {
                        return response()->json([
                            'success' => false,
                            'errors' => ['file' => [$errorMessage]],
                        ], 422);
                    }
                    return back()->withErrors(['file' => $errorMessage])->withInput();
                }
            }

            // Delete old file if exists
            if ($knowledgeBase->file_path && Storage::disk('public')->exists($knowledgeBase->file_path)) {
                Storage::disk('public')->delete($knowledgeBase->file_path);
            }

            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('knowledge-base', $fileName, 'public');

            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();

            // Extract content from text files
            if (in_array($file->getMimeType(), ['text/plain', 'text/markdown'])) {
                $data['content'] = file_get_contents($file->getRealPath());
            }
        }

        // Process embedded base64 images and replace in content
        if (!empty($data['content'])) {
            [$processedHtml, $images] = $this->extractAndStoreEmbeddedImages($data['content']);
            $data['content'] = $processedHtml;
            if (!empty($images)) {
                $data['images'] = $images;
            }
        }

        $knowledgeBase->update($data);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Knowledge base entry updated successfully.',
                'knowledge_base' => $knowledgeBase->fresh(),
                'redirect' => route('knowledge-base.show', $knowledgeBase)
            ]);
        }

        return redirect()
            ->route('knowledge-base.show', $knowledgeBase)
            ->with('success', 'Knowledge base entry updated successfully.');
    }

    /**
     * Extract base64 images from HTML, store to public disk, return [html, images]
     * images: array of {url, path, name, size}
     */
    private function extractAndStoreEmbeddedImages(string $html): array
    {
        $images = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        if (!$loaded) return [$html, $images];

        $imgs = $dom->getElementsByTagName('img');
        // Because live NodeList changes as we replace, iterate snapshot
        $toProcess = [];
        foreach ($imgs as $img) {
            $toProcess[] = $img;
        }

        foreach ($toProcess as $img) {
            if (!($img instanceof \DOMElement)) continue;
            $src = $img->getAttribute('src');
            if (strpos($src, 'data:image') === 0) {
                // Parse base64 data URL
                if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.*)$/', $src, $m)) {
                    $mime = $m[1];
                    $dataBase64 = $m[2];
                    $binary = base64_decode($dataBase64);
                    if ($binary !== false) {
                        $ext = explode('/', $mime)[1] ?? 'png';
                        $filename = 'kb/' . date('Y/m/') . uniqid('img_') . '.' . $ext;
                        Storage::disk('public')->put($filename, $binary);
                        $url = asset('storage/' . $filename);
                        $size = strlen($binary);
                        $images[] = [
                            'url' => $url,
                            'path' => $filename,
                            'name' => basename($filename),
                            'size' => $size,
                            'mime' => $mime,
                        ];
                        // Replace src with stored URL
                        $img->setAttribute('src', $url);
                    }
                }
            }
        }

        $newHtml = $dom->saveHTML();
        // Remove the meta charset added by loadHTML
        $newHtml = preg_replace('/^<\?xml.*?\?>/i', '', $newHtml);
        return [$newHtml, $images];
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KnowledgeBase $knowledgeBase)
    {
        // Delete associated file if exists
        if ($knowledgeBase->file_path && Storage::disk('public')->exists($knowledgeBase->file_path)) {
            Storage::disk('public')->delete($knowledgeBase->file_path);
        }

        $knowledgeBase->delete();

        return redirect()
            ->route('knowledge-base.index')
            ->with('success', 'Knowledge base entry deleted successfully.');
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(KnowledgeBase $knowledgeBase)
    {
        $knowledgeBase->update([
            'is_active' => !$knowledgeBase->is_active,
            'updated_by' => Auth::id(),
        ]);

        return back()->with('success', 'Status updated successfully.');
    }

    /**
     * Bulk actions
     */
    public function bulkAction(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'action' => 'required|in:delete,activate,deactivate,publish,archive',
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:knowledge_bases,id',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        $knowledgeBases = KnowledgeBase::whereIn('id', $request->ids);

        switch ($request->action) {
            case 'delete':
                $knowledgeBases->delete();
                $message = 'Selected entries deleted successfully.';
                break;
            case 'activate':
                $knowledgeBases->update(['is_active' => true, 'updated_by' => Auth::id()]);
                $message = 'Selected entries activated successfully.';
                break;
            case 'deactivate':
                $knowledgeBases->update(['is_active' => false, 'updated_by' => Auth::id()]);
                $message = 'Selected entries deactivated successfully.';
                break;
            case 'publish':
                $knowledgeBases->update([
                    'status' => 'published',
                    'published_at' => now(),
                    'updated_by' => Auth::id()
                ]);
                $message = 'Selected entries published successfully.';
                break;
            case 'archive':
                $knowledgeBases->update(['status' => 'archived', 'updated_by' => Auth::id()]);
                $message = 'Selected entries archived successfully.';
                break;
        }

        return back()->with('success', $message);
    }

    /**
     * Download file
     */
    public function downloadFile(KnowledgeBase $knowledgeBase)
    {
        if (!$knowledgeBase->file_path || !Storage::disk('public')->exists($knowledgeBase->file_path)) {
            abort(404, 'File not found.');
        }

        $filePath = Storage::disk('public')->path($knowledgeBase->file_path);

        return response()->download($filePath, $knowledgeBase->file_name);
    }

    /**
     * Search API endpoint for RAG integration
     */
    public function searchForRag(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|max:500',
            'limit' => 'nullable|integer|min:1|max:50',
            'category' => 'nullable|string',
            'type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => 'Invalid parameters'], 400);
        }

        $query = KnowledgeBase::query()
            ->active()
            ->published()
            ->search($request->query);

        if ($request->filled('category')) {
            $query->category($request->category);
        }

        if ($request->filled('type')) {
            $query->type($request->type);
        }

        $results = $query
            ->select(['id', 'title', 'excerpt', 'content', 'category', 'type', 'keywords', 'view_count'])
            ->orderBy('priority', 'desc')
            ->orderBy('view_count', 'desc')
            ->limit($request->input('limit', 10))
            ->get();

        return response()->json([
            'results' => $results,
            'query' => $request->query,
            'total' => $results->count(),
        ]);
    }

    /**
     * Export data for vector database
     */
    public function exportForVectorDb()
    {
        $knowledgeBases = KnowledgeBase::active()
            ->published()
            ->select(['id', 'title', 'excerpt', 'content', 'category', 'type', 'keywords', 'search_content'])
            ->get();

        $exportData = $knowledgeBases->map(function ($kb) {
            return [
                'id' => $kb->id,
                'text' => "{$kb->title}\n\n{$kb->excerpt}\n\n{$kb->content}",
                'metadata' => [
                    'title' => $kb->title,
                    'category' => $kb->category,
                    'type' => $kb->type,
                    'keywords' => $kb->keywords,
                    'source' => 'knowledge_base',
                ],
            ];
        });

        return response()->json([
            'data' => $exportData,
            'total' => $exportData->count(),
            'exported_at' => now()->toISOString(),
        ]);
    }

    /**
     * Handle large documents by chunking them into smaller pieces
     */
    private function handleLargeDocument(array $data, ?\Illuminate\Http\Request $request = null)
    {
        $chunkingService = app(DocumentChunkingService::class);

        // Chunk the document content
        $chunks = $chunkingService->chunkDocument($data['content'], $data['title']);

        // Build a base search_content from title + original content if not already set
        $baseSearchContent = $data['search_content'] ?? ($data['title'] . ' ' . strip_tags($data['content']));

        $createdEntries = [];
        $isMainEntry = true;

        foreach ($chunks as $chunk) {
            // Prepare data for this chunk
            $chunkData = $data;
            $chunkData['content'] = $chunk['content'];

            // chunk_summary may be absent on single-chunk path — guard with fallback
            $chunkSummary = $chunk['chunk_summary'] ?? Str::limit(strip_tags($chunk['content']), 150);

            // Always keep question/answer in sync with title/content for this chunk
            $chunkData['question'] = $chunkData['title'] ?? $data['title'];

            // Update title and metadata for chunks
            if ($chunk['total_chunks'] > 1) {
                if ($isMainEntry) {
                    // First chunk keeps the original title
                    $chunkData['title']    = $data['title'];
                    $chunkData['question'] = $data['title'];
                    $chunkData['answer']   = $chunk['content']; // full chunk content, not just summary
                } else {
                    // Subsequent chunks get numbered titles
                    $chunkData['title']    = $data['title'] . ' - Part ' . ($chunk['chunk_index'] + 1);
                    $chunkData['question'] = $chunkData['title'];
                    $chunkData['answer']   = $chunk['content'];
                }
            } else {
                // Single chunk — mirror full content
                $chunkData['answer'] = $chunk['content'];
            }

            // Add chunk metadata
            $chunkData['metadata'] = array_merge($data['metadata'] ?? [], [
                'is_chunked' => $chunk['total_chunks'] > 1,
                'chunk_index' => $chunk['chunk_index'],
                'total_chunks' => $chunk['total_chunks'],
                'char_count' => $chunk['char_count'],
                'word_count' => $chunk['word_count'],
                'original_title' => $data['title'],
                'chunk_summary' => $chunkSummary,
            ]);

            // Build search content for this chunk
            $chunkData['search_content'] = trim($baseSearchContent . ' ' . $chunk['content']);

            // Set published_at if status is published and no date specified
            if ($chunkData['status'] === 'published' && empty($chunkData['published_at'])) {
                $chunkData['published_at'] = now();
            }

            // Create the chunk entry
            $knowledgeBase = KnowledgeBase::create($chunkData);
            $createdEntries[] = $knowledgeBase;

            $isMainEntry = false;
        }

        $totalChunks = count($createdEntries);
        $firstEntry = $createdEntries[0];
        $message = $totalChunks > 1
            ? "Large document successfully processed and split into {$totalChunks} chunks for optimal search performance."
            : 'Knowledge base entry created successfully.';

        // Return JSON for axios/API requests (e.g. from the Vue frontend)
        if ($request && $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'knowledge_base' => $firstEntry,
                'total_chunks' => $totalChunks,
                'redirect' => route('knowledge-base.index'),
            ], 201);
        }

        return redirect()
            ->route('knowledge-base.show', $firstEntry)
            ->with('success', $message);
    }

    /**
     * Rebuild Pinecone vector database (clear and reindex all data)
     */
    public function syncPinecone(Request $request)
    {
        try {
            $dryRun = $request->boolean('dry_run', false);
            $confirm = $request->boolean('confirm', false);

            // For actual rebuild, require confirmation due to destructive nature
            if (!$dryRun && !$confirm) {
                return response()->json([
                    'success' => false,
                    'message' => 'Confirmation required for vector database rebuild',
                    'requires_confirmation' => true
                ], 422);
            }

            // Run the rebuild command programmatically
            $exitCode = \Artisan::call('kb:sync-pinecone', [
                '--dry-run' => $dryRun,
                '--force' => !$dryRun // Use force for actual rebuilds to skip confirmation
            ]);

            $output = \Artisan::output();

            // Parse the output to extract rebuild statistics
            $stats = $this->parseRebuildOutput($output);

            return response()->json([
                'success' => $exitCode === 0,
                'message' => $exitCode === 0 ? 'Vector database rebuilt successfully' : 'Rebuild completed with errors',
                'stats' => $stats,
                'output' => $output,
                'dry_run' => $dryRun
            ]);
        } catch (\Exception $e) {
            Log::error('Pinecone sync API error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Sync failed: ' . $e->getMessage(),
                'stats' => null
            ], 500);
        }
    }

    /**
     * Parse rebuild command output to extract statistics
     */
    private function parseRebuildOutput(string $output): array
    {
        $stats = [
            'db_entries' => 0,
            'vectors_cleared' => 0,
            'vectors_indexed' => 0,
            'errors' => 0
        ];

        // Extract numbers from output using regex
        if (preg_match('/Found (\d+) active KB entries/', $output, $matches)) {
            $stats['db_entries'] = (int)$matches[1];
        }

        if (preg_match('/Current vectors in Pinecone: (\d+)/', $output, $matches)) {
            $stats['vectors_cleared'] = (int)$matches[1]; // For dry run, show what would be cleared
        }

        if (preg_match('/Cleared (\d+) vectors/', $output, $matches)) {
            $stats['vectors_cleared'] = (int)$matches[1];
        }

        if (preg_match('/Successfully indexed: (\d+)/', $output, $matches)) {
            $stats['vectors_indexed'] = (int)$matches[1];
        }

        if (preg_match('/Errors: (\d+)/', $output, $matches)) {
            $stats['errors'] = (int)$matches[1];
        }

        return $stats;
    }

    // =========================================================================
    // Batch File Upload
    // =========================================================================

    /**
     * Step 1 — Upload all files, persist to disk, create a batch record.
     * Returns immediately with batch_id; the frontend processes files
     * one-by-one via batchUploadProcessFile().
     */
    public function batchUploadInit(Request $request)
    {
        @ini_set('max_execution_time', 120);
        @ini_set('memory_limit', '512M');

        $validator = Validator::make($request->all(), [
            'files'            => 'required|array|min:1|max:20',
            'files.*'          => 'file|mimes:pdf,doc,docx|max:51200',
            'default_category' => 'required|string|max:50',
            'default_type'     => 'required|string|max:50',
            'default_status'   => 'required|in:draft,published,archived',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors(),
            ], 422);
        }

        $storedFiles = [];
        foreach ($request->file('files') as $file) {
            $tmpName = uniqid('kbbatch_', true) . '.' . $file->getClientOriginalExtension();
            $tmpPath = $file->storeAs('kb-batch-tmp', $tmpName, 'local');

            $storedFiles[] = [
                'original_name' => $file->getClientOriginalName(),
                'temp_path'     => $tmpPath,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'extension'     => strtolower($file->getClientOriginalExtension()),
                'status'        => 'pending',
                'error'         => null,
                'kb_id'         => null,
                'kb_title'      => null,
            ];
        }

        $batch = KbBatchUpload::create([
            'user_id'          => Auth::id(),
            'status'           => 'pending',
            'total_files'      => count($storedFiles),
            'processed'        => 0,
            'failed'           => 0,
            'default_category' => $request->default_category,
            'default_type'     => $request->default_type,
            'default_status'   => $request->default_status,
            'files'            => $storedFiles,
        ]);

        return response()->json([
            'success'     => true,
            'batch_id'    => $batch->id,
            'total_files' => $batch->total_files,
            'files'       => array_map(fn($f) => [
                'original_name' => $f['original_name'],
                'size'          => $f['size'],
                'status'        => 'pending',
            ], $storedFiles),
        ]);
    }

    /**
     * Step 2 — Process a single file from the batch (called once per file).
     * The frontend calls this sequentially, updating per-file progress.
     */
    public function batchUploadProcessFile(Request $request, $batchId, $fileIndex)
    {
        @ini_set('max_execution_time', 300);
        @ini_set('memory_limit', '512M');

        $batch     = KbBatchUpload::findOrFail($batchId);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $files     = $batch->files;
        $fileIndex = (int) $fileIndex;

        if (!isset($files[$fileIndex])) {
            return response()->json(['success' => false, 'message' => 'File index not found'], 404);
        }

        if ($files[$fileIndex]['status'] !== 'pending') {
            return response()->json([
                'success'    => true,
                'message'    => 'Already processed',
                'file_index' => $fileIndex,
                'file_status' => $files[$fileIndex]['status'],
            ]);
        }

        // Mark as processing
        $files[$fileIndex]['status'] = 'processing';
        $batch->update(['files' => $files, 'status' => 'processing']);

        $fileInfo = $files[$fileIndex];
        $tmpPath  = storage_path('app/' . $fileInfo['temp_path']);

        try {
            if (!file_exists($tmpPath)) {
                throw new \Exception('Temporary file not found on server.');
            }

            $ext      = $fileInfo['extension'];
            $content  = '';
            $title    = pathinfo($fileInfo['original_name'], PATHINFO_FILENAME);
            $metadata = ['source_file' => $fileInfo['original_name']];

            // ── Extract text ──────────────────────────────────────────────────
            if ($ext === 'pdf') {
                $fakeFile  = new \Illuminate\Http\UploadedFile($tmpPath, $fileInfo['original_name'], $fileInfo['mime_type'], null, true);
                $pdfParser = app(PDFParserService::class);
                $pdfData   = $pdfParser->processPDFForKnowledgeBase($fakeFile);
                $content   = $pdfData['content'];
                $title     = !empty($pdfData['title']) ? $pdfData['title'] : $title;
                $metadata  = array_merge($metadata, $pdfData['metadata'] ?? []);
            } elseif (in_array($ext, ['doc', 'docx'])) {
                $docxParser = app(DocxParserService::class);
                $content    = $ext === 'docx'
                    ? ($docxParser->extractFromDocx($tmpPath) ?? '')
                    : ($docxParser->extractFromDoc($tmpPath)  ?? '');
                $fakeFile   = new \Illuminate\Http\UploadedFile($tmpPath, $fileInfo['original_name'], $fileInfo['mime_type'], null, true);
                $docMeta    = $docxParser->extractMetadata($fakeFile);
                if (!empty($docMeta['title'])) {
                    $title = $docMeta['title'];
                }
                $metadata = array_merge($metadata, array_filter($docMeta));
            } else {
                throw new \Exception('Unsupported file type: ' . $ext);
            }

            if (empty(trim($content))) {
                throw new \Exception('Could not extract readable text. The file may be scanned/image-based.');
            }

            // ── Move to permanent storage ─────────────────────────────────────
            $permanentName = time() . '_' . Str::slug(pathinfo($fileInfo['original_name'], PATHINFO_FILENAME)) . '.' . $ext;
            $permanentPath = Storage::disk('public')->putFileAs(
                'knowledge-base',
                new \Illuminate\Http\File($tmpPath),
                $permanentName
            );

            // ── Create KB entry ───────────────────────────────────────────────
            $kbStatus    = $batch->default_status;
            $publishedAt = $kbStatus === 'published' ? now() : null;
            $excerpt     = mb_substr(strip_tags($content), 0, 300);

            $kb = KnowledgeBase::create([
                'title'        => $title,
                'question'     => $title,
                'content'      => $content,
                'answer'       => $content,
                'excerpt'      => $excerpt,
                'category'     => $batch->default_category,
                'type'         => $batch->default_type,
                'source_type'  => 'file',
                'file_path'    => $permanentPath,
                'file_name'    => $fileInfo['original_name'],
                'file_size'    => $fileInfo['size'],
                'mime_type'    => $fileInfo['mime_type'],
                'metadata'     => $metadata,
                'is_active'    => true,
                'status'       => $kbStatus,
                'published_at' => $publishedAt,
                'created_by'   => $batch->user_id,
                'updated_by'   => $batch->user_id,
            ]);

            // Cleanup temp file
            @unlink($tmpPath);

            $files[$fileIndex]['status']    = 'done';
            $files[$fileIndex]['kb_id']     = $kb->id;
            $files[$fileIndex]['kb_title']  = $kb->title;
            $files[$fileIndex]['temp_path'] = null;

            $batch->increment('processed');
        } catch (\Exception $e) {
            Log::error("Batch upload file #{$fileIndex} failed: " . $e->getMessage());
            $files[$fileIndex]['status'] = 'failed';
            $files[$fileIndex]['error']  = $e->getMessage();
            if (!empty($tmpPath) && file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
            $batch->increment('processed');
            $batch->increment('failed');
        }

        // Determine overall batch status
        $fresh       = $batch->fresh();
        $batchStatus = $fresh->processed >= $fresh->total_files
            ? ($fresh->failed > 0 ? 'completed_with_errors' : 'completed')
            : 'processing';

        $batch->update(['files' => $files, 'status' => $batchStatus]);

        return response()->json([
            'success'      => $files[$fileIndex]['status'] === 'done',
            'file_status'  => $files[$fileIndex]['status'],
            'file_index'   => $fileIndex,
            'kb_id'        => $files[$fileIndex]['kb_id']    ?? null,
            'kb_title'     => $files[$fileIndex]['kb_title'] ?? null,
            'error'        => $files[$fileIndex]['error']    ?? null,
            'batch_status' => $batchStatus,
            'processed'    => $fresh->processed,
            'total_files'  => $fresh->total_files,
            'failed'       => $fresh->failed,
        ]);
    }

    /**
     * Return current status of a batch — polled by the frontend.
     */
    public function batchUploadStatus($batchId)
    {
        $batch = KbBatchUpload::findOrFail($batchId);

        if ($batch->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success'     => true,
            'batch_id'    => $batch->id,
            'status'      => $batch->status,
            'total_files' => $batch->total_files,
            'processed'   => $batch->processed,
            'failed'      => $batch->failed,
            'progress'    => $batch->progress,
            'files'       => array_map(fn($f) => [
                'original_name' => $f['original_name'],
                'size'          => $f['size'],
                'status'        => $f['status'],
                'error'         => $f['error']    ?? null,
                'kb_id'         => $f['kb_id']    ?? null,
                'kb_title'      => $f['kb_title'] ?? null,
            ], $batch->files ?? []),
        ]);
    }
}
