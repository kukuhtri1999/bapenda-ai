<?php

namespace App\Http\Controllers;

use App\Models\KnowledgeBase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Jobs\ProcessDocumentJob;

class KnowledgeBaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = KnowledgeBase::with(['creator', 'updater'])
            ->orderBy('created_at', 'desc');

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

        if ($request->filled('status')) {
            $query->where('processing_status', $request->status);
        }

        $knowledgeBases = $query->paginate(15)->withQueryString();

        // Get available categories for filter
        $categories = KnowledgeBase::whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        return Inertia::render('KnowledgeBase/Index', [
            'knowledgeBases' => $knowledgeBases,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category', 'type', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('KnowledgeBase/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'type' => 'required|in:manual,document',
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'content' => 'required_if:type,manual|string',
            'file' => 'required_if:type,document|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $data = [
            'title' => $request->title,
            'category' => $request->category,
            'type' => $request->type,
            'is_active' => true,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ];

        if ($request->type === 'manual') {
            // Manual entry
            $data['content'] = $request->content;
            $data['processing_status'] = 'completed';
        } else {
            // Document upload
            $file = $request->file('file');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('knowledge-base', $fileName, 'public');

            $data['file_path'] = $filePath;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_type'] = $file->getClientOriginalExtension();
            $data['file_size'] = $file->getSize();
            $data['processing_status'] = 'pending';
            $data['content'] = ''; // Will be filled by processing job
        }

        $knowledgeBase = KnowledgeBase::create($data);

        // If document, dispatch processing job
        if ($request->type === 'document') {
            ProcessDocumentJob::dispatch($knowledgeBase);
        }

        return redirect()->route('knowledge-base.index')
            ->with('success', 'Knowledge base entry created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(KnowledgeBase $knowledgeBase): Response
    {
        $knowledgeBase->load(['creator', 'updater']);

        return Inertia::render('KnowledgeBase/Show', [
            'knowledgeBase' => $knowledgeBase,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(KnowledgeBase $knowledgeBase): Response
    {
        return Inertia::render('KnowledgeBase/Edit', [
            'knowledgeBase' => $knowledgeBase,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, KnowledgeBase $knowledgeBase): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'content' => 'required|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $knowledgeBase->update([
            'title' => $request->title,
            'category' => $request->category,
            'content' => $request->content,
            'is_active' => $request->boolean('is_active', true),
            'updated_by' => Auth::id(),
        ]);

        return redirect()->route('knowledge-base.index')
            ->with('success', 'Knowledge base entry updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        // Delete associated file if exists
        $knowledgeBase->deleteFile();

        // Delete the record
        $knowledgeBase->delete();

        return redirect()->route('knowledge-base.index')
            ->with('success', 'Knowledge base entry deleted successfully.');
    }

    /**
     * Toggle the active status of the knowledge base entry.
     */
    public function toggleStatus(KnowledgeBase $knowledgeBase)
    {
        $knowledgeBase->update([
            'is_active' => !$knowledgeBase->is_active,
            'updated_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => true,
            'is_active' => $knowledgeBase->is_active,
        ]);
    }

    /**
     * Reprocess a document (for failed processing)
     */
    public function reprocess(KnowledgeBase $knowledgeBase): RedirectResponse
    {
        if (!$knowledgeBase->isDocument() || !$knowledgeBase->hasFile()) {
            return redirect()->back()
                ->with('error', 'Only document entries with files can be reprocessed.');
        }

        $knowledgeBase->update([
            'processing_status' => 'pending',
            'processing_error' => null,
            'updated_by' => Auth::id(),
        ]);

        ProcessDocumentJob::dispatch($knowledgeBase);

        return redirect()->back()
            ->with('success', 'Document reprocessing initiated.');
    }

    /**
     * Download the original file
     */
    public function download(KnowledgeBase $knowledgeBase)
    {
        if (!$knowledgeBase->hasFile()) {
            abort(404, 'File not found.');
        }

        return Storage::download($knowledgeBase->file_path, $knowledgeBase->file_name);
    }
}
