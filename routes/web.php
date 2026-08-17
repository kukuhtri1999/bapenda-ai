<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WajibPajakController;
use App\Http\Controllers\PhotoEditingController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\KnowledgeBaseController;
use App\Http\Controllers\AppSettingController;
use App\Http\Controllers\Admin\ChatImportController;
use App\Http\Controllers\Admin\FeedbackController;
use App\Http\Controllers\Admin\LotreImportController;

Route::get('/', function () {
    $cms = \App\Models\HomepageContent::getAllGrouped();
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
        'cms' => $cms,
    ]);
});

// Custom Login Route (Active when ADMIN_LOGIN_PATH is set in .env)
$customLoginPath = config('auth.custom_login_path');
if (!empty($customLoginPath) && $customLoginPath !== 'login') {
    Route::get('/' . $customLoginPath, function () {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    })->middleware(['guest'])->name('custom.login');
}

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/products', function () {
        return Inertia::render('Products/Index');
    })->name('products.index');

    Route::get('/chat', function () {
        return Inertia::render('Chat/Index');
    })->name('chat');

    // ─── API endpoints for User Management (AJAX / modal-driven) ───────────
    Route::prefix('api/admin/users')->group(function () {
        Route::get('/stats',                  [UserManagementController::class, 'stats']);
        Route::get('/',                       [UserManagementController::class, 'apiIndex']);
        Route::post('/',                      [UserManagementController::class, 'apiStore']);
        Route::get('/{id}',                   [UserManagementController::class, 'apiShow']);
        Route::put('/{id}',                   [UserManagementController::class, 'apiUpdate']);
        Route::delete('/{id}',                [UserManagementController::class, 'apiDestroy']);
        Route::post('/{id}/change-password',  [UserManagementController::class, 'changePassword']);
        Route::post('/{id}/toggle-status',    [UserManagementController::class, 'toggleStatus']);
        Route::post('/{id}/restore',          [UserManagementController::class, 'apiRestore']);
        Route::delete('/{id}/force-delete',   [UserManagementController::class, 'apiForceDelete']);
    });
    Route::get('/api/admin/roles', [UserManagementController::class, 'getRoles']);

    // ─── API endpoint for chat-history detail ────────────────────────────────
    Route::get('/api/admin/chat-history/show/{id}', [App\Http\Controllers\Admin\ChatHistoryController::class, 'show'])->name('admin.chat-history.show');

    // User Management Routes
    Route::resource('users', UserManagementController::class);
    Route::post('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

    // Knowledge Base Routes
    Route::resource('knowledge-base', KnowledgeBaseController::class);
    Route::post('/knowledge-base/{knowledgeBase}/toggle-status', [KnowledgeBaseController::class, 'toggleStatus'])->name('knowledge-base.toggle-status');
    Route::post('/knowledge-base/bulk-action', [KnowledgeBaseController::class, 'bulkAction'])->name('knowledge-base.bulk-action');
    Route::post('/knowledge-base/sync-pinecone', [KnowledgeBaseController::class, 'syncPinecone'])->name('knowledge-base.sync-pinecone');
    Route::post('/knowledge-base/fetch-pinecone', [KnowledgeBaseController::class, 'fetchFromPinecone'])->name('knowledge-base.fetch-pinecone');
    Route::get('/knowledge-base/{knowledgeBase}/download', [KnowledgeBaseController::class, 'downloadFile'])->name('knowledge-base.download');
    Route::post('/knowledge-base/{knowledgeBase}/score', [KnowledgeBaseController::class, 'computeScore'])->name('knowledge-base.score');
    Route::post('/knowledge-base/{knowledgeBase}/enhance', [KnowledgeBaseController::class, 'enhanceWithAI'])->name('knowledge-base.enhance');
    // Batch upload routes
    Route::post('/knowledge-base/batch/init', [KnowledgeBaseController::class, 'batchUploadInit'])->name('knowledge-base.batch-init');
    Route::post('/knowledge-base/batch/{batchId}/process/{fileIndex}', [KnowledgeBaseController::class, 'batchUploadProcessFile'])->name('knowledge-base.batch-process');
    Route::get('/knowledge-base/batch/{batchId}/status', [KnowledgeBaseController::class, 'batchUploadStatus'])->name('knowledge-base.batch-status');

    // App Settings Routes
    Route::resource('settings', AppSettingController::class);
    Route::put('/settings/{setting}/value', [AppSettingController::class, 'updateValue'])->name('settings.update-value');
    Route::post('/settings/bulk-update', [AppSettingController::class, 'bulkUpdate'])->name('settings.bulk-update');
    Route::delete('/settings/cache', [AppSettingController::class, 'clearCache'])->name('settings.clear-cache');

    // Admin AI Analytics UI
    Route::get('/admin/analytics', [App\Http\Controllers\Admin\AnalyticsController::class, 'indexPage'])->name('admin.analytics');

    // Admin Wajib Pajak listing
    Route::get('/admin/wajib-pajak', [App\Http\Controllers\Admin\WajibPajakController::class, 'index'])->name('admin.wajib-pajak.index');
    Route::get('/admin/wajib-pajak/list', [App\Http\Controllers\Admin\WajibPajakController::class, 'list'])->name('admin.wajib-pajak.list');

    // AI Chat History page
    Route::get('/admin/chat-history', [App\Http\Controllers\Admin\ChatHistoryController::class, 'indexPage'])->name('admin.chat-history.index');

    // Admin Feedback Management
    Route::get('/admin/feedback', [FeedbackController::class, 'index'])->name('admin.feedback.index');
    Route::get('/admin/feedback/{feedback}', [FeedbackController::class, 'show'])->name('admin.feedback.show');
    Route::post('/admin/feedback/{feedback}/draft-kb', [FeedbackController::class, 'draftKnowledgeBase'])->name('admin.feedback.draft-kb');
    Route::get('/admin/feedback/export/csv', [FeedbackController::class, 'export'])->name('admin.feedback.export');

    // Admin Knowledge Gaps Management
    Route::get('/admin/knowledge-gaps', [App\Http\Controllers\Admin\KnowledgeGapController::class, 'index'])->name('admin.knowledge-gaps.index');
    Route::post('/admin/knowledge-gaps/{knowledgeGap}/draft', [App\Http\Controllers\Admin\KnowledgeGapController::class, 'generateDraft'])->name('admin.knowledge-gaps.draft');
    Route::post('/admin/knowledge-gaps/{knowledgeGap}/dismiss', [App\Http\Controllers\Admin\KnowledgeGapController::class, 'dismiss'])->name('admin.knowledge-gaps.dismiss');
    Route::post('/admin/knowledge-gaps/{knowledgeGap}/resolve', [App\Http\Controllers\Admin\KnowledgeGapController::class, 'resolve'])->name('admin.knowledge-gaps.resolve');
    Route::delete('/admin/knowledge-gaps/{knowledgeGap}', [App\Http\Controllers\Admin\KnowledgeGapController::class, 'destroy'])->name('admin.knowledge-gaps.destroy');

    // Admin RAG Evaluation Suite
    Route::get('/admin/rag-evaluation', [App\Http\Controllers\Admin\RagEvaluationController::class, 'index'])->name('admin.rag-evaluation.index');
    Route::post('/admin/rag-evaluation/run', [App\Http\Controllers\Admin\RagEvaluationController::class, 'runBenchmark'])->name('admin.rag-evaluation.run');
    Route::post('/admin/rag-evaluation/tests', [App\Http\Controllers\Admin\RagEvaluationController::class, 'storeTest'])->name('admin.rag-evaluation.tests.store');
    Route::put('/admin/rag-evaluation/tests/{ragEvalTest}', [App\Http\Controllers\Admin\RagEvaluationController::class, 'updateTest'])->name('admin.rag-evaluation.tests.update');
    Route::delete('/admin/rag-evaluation/tests/{ragEvalTest}', [App\Http\Controllers\Admin\RagEvaluationController::class, 'destroyTest'])->name('admin.rag-evaluation.tests.destroy');

    // Admin CMS (Homepage & Footer)
    Route::get('/admin/cms', [\App\Http\Controllers\Admin\CmsController::class, 'index'])->name('admin.cms.index');
    Route::post('/admin/cms/update', [\App\Http\Controllers\Admin\CmsController::class, 'update'])->name('admin.cms.update');
    Route::post('/admin/cms/upload-image', [\App\Http\Controllers\Admin\CmsController::class, 'uploadImage'])->name('admin.cms.upload-image');
    Route::post('/admin/cms/reset-defaults', [\App\Http\Controllers\Admin\CmsController::class, 'resetDefaults'])->name('admin.cms.reset-defaults');

    // Admin Chat Import (XLSX) routes
    Route::get('/admin/chat-import', function () {
        return Inertia::render('Admin/ChatImport/Index');
    })->name('admin.chat-import');
    Route::post('/admin/chat-import', [ChatImportController::class, 'upload'])->name('admin.chat-import.upload');

    // Admin Lotre Management routes (requires auth)
    Route::prefix('admin/lotre')->group(function () {
        // Import routes
        Route::get('/import', [LotreImportController::class, 'index'])->name('admin.lotre.import');
        Route::post('/import/preview', [LotreImportController::class, 'preview'])->name('admin.lotre.preview');
        Route::post('/import', [LotreImportController::class, 'upload'])->name('admin.lotre.upload');
        Route::post('/import/clear', [LotreImportController::class, 'clearAll'])->name('admin.lotre.clear');
        Route::get('/import/template', [LotreImportController::class, 'downloadTemplate'])->name('admin.lotre.template');

        // Settings routes
        Route::get('/settings', [LotreImportController::class, 'settings'])->name('admin.lotre.settings');
        Route::post('/settings', [LotreImportController::class, 'updateSettings'])->name('admin.lotre.update-settings');
        Route::get('/search-participants', [LotreImportController::class, 'searchParticipants'])->name('admin.lotre.search-participants');
        Route::post('/set-predetermined-winners', [LotreImportController::class, 'setPredeterminedWinners'])->name('admin.lotre.set-predetermined-winners');
        Route::post('/clear-predetermined-winners', [LotreImportController::class, 'clearPredeterminedWinners'])->name('admin.lotre.clear-predetermined-winners');
    });
});

// Public Wajib Pajak routes (entry point for chat)
Route::get('/wajib-pajak', [WajibPajakController::class, 'showForm'])->name('wajib-pajak.form');
Route::post('/api/wajib-pajak/start-chat', [WajibPajakController::class, 'startChatSession'])->name('wajib-pajak.start-chat');
Route::post('/api/wajib-pajak/clear-session', [WajibPajakController::class, 'clearSession'])->name('wajib-pajak.clear-session');
Route::get('/api/check-wajib-pajak-session', [WajibPajakController::class, 'checkSession'])->name('wajib-pajak.check-session');

// Public lottery page
Route::get('/lotre-undian', [App\Http\Controllers\LotreController::class, 'index'])->name('lotre.index');

// Public chat route (accessible without login for public service) - REQUIRES WAJIB PAJAK DATA
Route::middleware('ensure.wajib.pajak')->get('/customer-service', function () {
    return Inertia::render('Chat/Index', [
        'wajibPajakData' => session('wajib_pajak_data'),
        'cms' => \App\Models\HomepageContent::getAllGrouped(),
    ]);
})->name('customer-service');

// Secret Photo Editing Routes
Route::get('/edit-foto/login', [PhotoEditingController::class, 'showLogin'])->name('photo.login');

Route::middleware('secret.photo.access')->group(function () {
    Route::get('/edit-foto', [PhotoEditingController::class, 'index'])->name('photo.edit');
    Route::post('/edit-foto/upload', [PhotoEditingController::class, 'upload'])->name('photo.upload');
    Route::get('/edit-foto/photos', [PhotoEditingController::class, 'getPhotos'])->name('photo.list');
    Route::get('/edit-foto/progress', [PhotoEditingController::class, 'getUploadProgress'])->name('photo.progress');
    Route::get('/edit-foto/download', [PhotoEditingController::class, 'downloadAll'])->name('photo.download');
    Route::delete('/edit-foto/photos/{id}', [PhotoEditingController::class, 'deletePhoto'])->name('photo.delete');
});

// Fast CSRF token refresh endpoint for Axios / SPA / PWA auto-healing
Route::get('/refresh-csrf', function () {
    return response()->json([
        'success' => true,
        'csrf_token' => csrf_token(),
    ]);
})->name('refresh-csrf');

