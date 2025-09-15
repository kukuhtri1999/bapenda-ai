<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'source_type' => 'required|in:manual,file',
            'tags' => 'nullable|string|max:500',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',

            // File upload validation
            'file' => 'nullable|file|mimes:pdf,doc,docx,txt,md|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        // Ensure DB columns from older migration are filled: question/answer
        // some migrations use question/answer, newer ones use title/content.
        $data['question'] = $data['title'] ?? ($data['question'] ?? null);
        $data['answer'] = $data['content'] ?? ($data['answer'] ?? null);
        // Normalize tags: controller accepts string or array; store as JSON string if array
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        // Handle file upload for file source type
        if ($request->source_type === 'file' && $request->hasFile('file')) {
            $file = $request->file('file');
            $fileName = time() . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('knowledge-base', $fileName, 'public');

            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();
            $data['mime_type'] = $file->getMimeType();

            // Extract content from file if possible
            if (in_array($file->getMimeType(), ['text/plain', 'text/markdown'])) {
                $data['content'] = file_get_contents($file->getRealPath());
            }
        }

        // Set published_at if status is published and no date specified
        if ($data['status'] === 'published' && (empty($data['published_at']))) {
            $data['published_at'] = now();
        }

        $knowledgeBase = KnowledgeBase::create($data);

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
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string|max:50',
            'type' => 'required|string|max:50',
            'tags' => 'nullable|string|max:500',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string|max:100',
            'priority' => 'nullable|integer|min:0|max:100',
            'is_active' => 'boolean',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = $validator->validated();
        $data['question'] = $data['title'] ?? ($data['question'] ?? null);
        $data['answer'] = $data['content'] ?? ($data['answer'] ?? null);
        if (isset($data['tags']) && is_array($data['tags'])) {
            $data['tags'] = json_encode($data['tags']);
        }
        $data['updated_by'] = Auth::id();

        // Set published_at if status changed to published and no date specified
        if ($data['status'] === 'published' && $knowledgeBase->status !== 'published' && (empty($data['published_at']))) {
            $data['published_at'] = now();
        }

        $knowledgeBase->update($data);

        return redirect()
            ->route('knowledge-base.show', $knowledgeBase)
            ->with('success', 'Knowledge base entry updated successfully.');
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
}
