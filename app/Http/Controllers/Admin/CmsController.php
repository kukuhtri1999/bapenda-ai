<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HomepageContent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CmsController extends Controller
{
    /**
     * Display the CMS management page.
     */
    public function index()
    {
        $groupedContents = HomepageContent::getAdminGrouped();
        $totalItems = HomepageContent::count();

        return Inertia::render('Admin/Cms/Index', [
            'contents' => $groupedContents,
            'stats' => [
                'totalItems' => $totalItems,
                'sectionsCount' => count($groupedContents),
                'lastUpdated' => HomepageContent::latest('updated_at')->value('updated_at'),
            ]
        ]);
    }

    /**
     * Update CMS content fields in batch.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.key' => 'required|string',
            'items.*.value' => 'nullable',
        ]);

        try {
            foreach ($validated['items'] as $item) {
                $content = HomepageContent::where('key', $item['key'])->first();
                if ($content) {
                    $value = $item['value'];
                    if ($content->type === 'json' && is_array($value)) {
                        $value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    }
                    $content->update(['value' => $value]);
                }
            }

            return back()->with('success', 'Konten beranda dan footer berhasil diperbarui!');
        } catch (\Exception $e) {
            Log::error('CMS update failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal memperbarui konten: ' . $e->getMessage());
        }
    }

    /**
     * Upload an image file for CMS banners/mascot/logos.
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        try {
            $file = $request->file('image');
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file->getClientOriginalName());
            
            // Store directly in public/images/cms
            $destinationPath = public_path('images/cms');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);
            $url = '/images/cms/' . $filename;

            return response()->json([
                'success' => true,
                'url' => $url,
                'message' => 'Gambar berhasil diunggah!'
            ]);
        } catch (\Exception $e) {
            Log::error('CMS image upload failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah gambar: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Reset all homepage & footer contents to seeder defaults.
     */
    public function resetDefaults()
    {
        try {
            Artisan::call('db:seed', ['--class' => 'HomepageContentSeeder', '--force' => true]);
            return back()->with('success', 'Konten berhasil direset ke pengaturan bawaan!');
        } catch (\Exception $e) {
            Log::error('CMS reset failed: ' . $e->getMessage());
            return back()->with('error', 'Gagal mereset konten: ' . $e->getMessage());
        }
    }
}
