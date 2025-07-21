<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use App\Models\TemporaryPhoto;
use Illuminate\Support\Facades\Log;

class CleanupExpiredPhotos implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting cleanup of expired photos');

        $expiredPhotos = TemporaryPhoto::expired()->get();
        $deletedCount = 0;

        foreach ($expiredPhotos as $photo) {
            try {
                // Delete files from storage
                if ($photo->original_path && Storage::disk('public')->exists($photo->original_path)) {
                    Storage::disk('public')->delete($photo->original_path);
                }

                if ($photo->edited_path && Storage::disk('public')->exists($photo->edited_path)) {
                    Storage::disk('public')->delete($photo->edited_path);
                }

                // Delete database record
                $photo->delete();
                $deletedCount++;
            } catch (\Exception $e) {
                Log::error('Failed to delete expired photo: ' . $e->getMessage(), [
                    'photo_id' => $photo->id,
                    'original_path' => $photo->original_path,
                    'edited_path' => $photo->edited_path
                ]);
            }
        }

        Log::info("Cleanup completed. Deleted {$deletedCount} expired photos.");
    }
}
