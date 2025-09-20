<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Services\PDFParserService;
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
            'file' => $request->source_type === 'file' ? 'required|file|mimes:pdf,doc,docx,txt,md|max:10240' : 'nullable|file|mimes:pdf,doc,docx,txt,md|max:10240',
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
        // Ensure DB columns from older migration are filled: question/answer
        // some migrations use question/answer, newer ones use title/content.
        $data['question'] = $data['title'] ?? ($data['question'] ?? null);
        $data['answer'] = $data['content'] ?? ($data['answer'] ?? null);
        // Normalize tags to JSON array for DB JSON column
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
            if ($file->getMimeType() === 'application/pdf') {
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

            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('knowledge-base', $fileName, 'public');

            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();

            // Extract content from text files if content is empty
            if (empty($data['content']) && in_array($file->getMimeType(), ['text/plain', 'text/markdown'])) {
                $data['content'] = file_get_contents($file->getRealPath());
            }
        }

        // Validate that content exists after file processing for file uploads
        if ($request->source_type === 'file' && empty($data['content'])) {
            $errorMessage = 'Could not extract content from the uploaded file. Please ensure the file contains readable text.';
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => ['content' => [$errorMessage]],
                ], 422);
            }
            return back()->withErrors(['content' => $errorMessage])->withInput();
        }

        // Process embedded base64 images in HTML content (Quill) and move to storage
        if (!empty($data['content'])) {
            [$processedHtml, $images] = $this->extractAndStoreEmbeddedImages($data['content']);
            $data['content'] = $processedHtml;
            if (!empty($images)) {
                $data['images'] = $images;
            }
        }

        // Check if document is large and needs chunking
        if (!empty($data['content']) && strlen($data['content']) > 1500) {
            return $this->handleLargeDocument($data);
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
    private function handleLargeDocument(array $data)
    {
        $chunkingService = app(DocumentChunkingService::class);

        // Chunk the document content
        $chunks = $chunkingService->chunkDocument($data['content'], $data['title']);

        $createdEntries = [];
        $isMainEntry = true;

        foreach ($chunks as $chunk) {
            // Prepare data for this chunk
            $chunkData = $data;
            $chunkData['content'] = $chunk['content'];

            // Update title and metadata for chunks
            if ($chunk['total_chunks'] > 1) {
                if ($isMainEntry) {
                    // First chunk keeps the original title
                    $chunkData['title'] = $data['title'];
                    $chunkData['answer'] = $chunk['chunk_summary'];
                } else {
                    // Subsequent chunks get numbered titles
                    $chunkData['title'] = $data['title'] . " - Part " . ($chunk['chunk_index'] + 1);
                    $chunkData['answer'] = $chunk['chunk_summary'];
                }
            }

            // Add chunk metadata
            $chunkData['metadata'] = array_merge($data['metadata'] ?? [], [
                'is_chunked' => true,
                'chunk_index' => $chunk['chunk_index'],
                'total_chunks' => $chunk['total_chunks'],
                'char_count' => $chunk['char_count'],
                'word_count' => $chunk['word_count'],
                'original_title' => $data['title'],
                'chunk_summary' => $chunk['chunk_summary']
            ]);

            // Update search content with chunk-specific content
            $chunkData['search_content'] = $data['search_content'] . ' ' . $chunk['content'];

            // Set published_at if status is published and no date specified
            if ($chunkData['status'] === 'published' && (empty($chunkData['published_at']))) {
                $chunkData['published_at'] = now();
            }

            // Create the chunk entry
            $knowledgeBase = KnowledgeBase::create($chunkData);
            $createdEntries[] = $knowledgeBase;

            $isMainEntry = false;
        }

        $totalChunks = count($createdEntries);
        $firstEntry = $createdEntries[0];

        return redirect()
            ->route('knowledge-base.show', $firstEntry)
            ->with('success', "Large document successfully processed and split into {$totalChunks} manageable chunks for optimal search performance.");
    }
}
