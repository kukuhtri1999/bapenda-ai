<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KnowledgeBase;
use App\Services\PDFParserService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ReextractPdfContent extends Command
{
  protected $signature = 'kb:reextract-pdfs
                            {--id= : Re-extract a single KB entry by ID}
                            {--dry-run : Show what would be processed without making changes}
                            {--force : Skip confirmation prompt}
                            {--reindex : Re-index to Pinecone vector DB after extraction}';

  protected $description = 'Re-extract full text content from stored PDF files and update knowledge base entries';

  public function handle(): int
  {
    $dryRun   = $this->option('dry-run');
    $forceOpt = $this->option('force');
    $reindex  = $this->option('reindex');
    $singleId = $this->option('id');

    $this->info($dryRun ? '🔍 DRY RUN: analysing PDF entries (no changes will be made)' : '📄 Re-extracting PDF content from stored files...');
    $this->newLine();

    // Build query
    $query = KnowledgeBase::withTrashed(false)
      ->whereNotNull('file_path')
      ->where(function ($q) {
        $q->where('mime_type', 'application/pdf')
          ->orWhere('file_name', 'like', '%.pdf')
          ->orWhere('file_path', 'like', '%.pdf');
      });

    if ($singleId) {
      $query->where('id', (int) $singleId);
    }

    $entries = $query->orderBy('id')->get();

    if ($entries->isEmpty()) {
      $this->warn('No PDF entries found matching criteria.');
      return 0;
    }

    $this->info("Found {$entries->count()} PDF entries to process.");
    $this->newLine();

    if (!$dryRun && !$forceOpt) {
      if (!$this->confirm("This will overwrite the 'content' field for {$entries->count()} KB entries. Continue?", false)) {
        $this->info('Cancelled.');
        return 0;
      }
    }

    $pdfParser = app(PDFParserService::class);

    $success   = 0;
    $skipped   = 0;
    $failed    = 0;
    $emptyFile = 0;

    $bar = $this->output->createProgressBar($entries->count());
    $bar->setFormat(' %current%/%max% [%bar%] %percent:3s%% — %message%');
    $bar->setMessage('Starting...');
    $bar->start();

    foreach ($entries as $kb) {
      $bar->setMessage("KB #{$kb->id}: {$kb->title}");
      $bar->advance();

      // ── Resolve file path ─────────────────────────────────────────────
      $storedPath = $kb->file_path;
      $diskPath   = Storage::disk('public')->path($storedPath);

      if (!Storage::disk('public')->exists($storedPath)) {
        $this->newLine();
        $this->warn("  ↳ SKIP — file not found on disk: {$storedPath}");
        $skipped++;
        continue;
      }

      if ($dryRun) {
        $size = Storage::disk('public')->size($storedPath);
        $this->newLine();
        $this->line("  ↳ WOULD re-extract: {$kb->file_name} (" . round($size / 1024) . " KB)");
        $success++;
        continue;
      }

      // ── Extract text ──────────────────────────────────────────────────
      try {
        $extractedText = $pdfParser->extractTextPreservingStructureFromPath($diskPath);

        if (empty(trim((string) $extractedText))) {
          $this->newLine();
          $this->warn("  ↳ EMPTY — no text could be extracted from {$kb->file_name} (possibly scanned/image PDF)");
          $emptyFile++;
          continue;
        }

        // ── Build new excerpt ─────────────────────────────────────────
        $plainText   = strip_tags($extractedText);
        $newExcerpt  = mb_strlen($plainText) > 300 ? mb_substr($plainText, 0, 297) . '...' : $plainText;
        $oldLen      = mb_strlen((string) $kb->content);
        $newLen      = mb_strlen($extractedText);

        // ── Update the record (skip event firing for speed) ───────────
        // Use query builder to suppress model events; we'll fire them below if --reindex
        KnowledgeBase::withoutEvents(function () use ($kb, $extractedText, $newExcerpt) {
          $kb->content  = $extractedText;
          $kb->answer   = $extractedText;   // keep legacy mirror in sync
          $kb->excerpt  = $newExcerpt;
          $kb->question = $kb->title;        // keep legacy mirror in sync

          // Regenerate search_content
          $kb->generateSearchContent();
          $kb->save();
        });

        $this->newLine();
        $this->info("  ↳ UPDATED #{$kb->id} — chars: {$oldLen} → {$newLen}");

        // ── Optionally re-index ───────────────────────────────────────
        if ($reindex) {
          try {
            $kb->refresh(); // reload from DB to get fresh attributes
            $kb->removeFromVectorDatabase();
            $kb->indexToVectorDatabase();
            $this->line("       ✓ Re-indexed to Pinecone");
          } catch (\Exception $e) {
            $this->warn("       ✗ Pinecone index failed: " . $e->getMessage());
          }
        }

        $success++;
      } catch (\Exception $e) {
        $this->newLine();
        $this->error("  ↳ ERROR #{$kb->id}: " . $e->getMessage());
        Log::error("kb:reextract-pdfs — failed on KB #{$kb->id}: " . $e->getMessage());
        $failed++;
      }
    }

    $bar->finish();
    $this->newLine(2);

    // ── Summary ───────────────────────────────────────────────────────────
    $this->line('════════════════════════════════════════');
    if ($dryRun) {
      $this->info("DRY RUN completed — {$success} files would be re-extracted.");
    } else {
      $this->info("✅ Updated  : {$success}");
      $this->warn("⚠️  Empty    : {$emptyFile} (scanned/image PDFs — skipped)");
      $this->line("⏭️  Skipped  : {$skipped} (file missing on disk)");
      if ($failed) {
        $this->error("❌ Errors   : {$failed}");
      }
      $this->newLine();

      if ($success > 0 && !$reindex) {
        $this->info('💡 Content updated in MySQL. Run the following to push changes to Pinecone:');
        $this->line('   php artisan kb:sync-pinecone --force');
      } elseif ($success > 0 && $reindex) {
        $this->info('✅ Pinecone vectors updated individually (--reindex was set).');
        $this->info('   You can also run a full rebuild: php artisan kb:sync-pinecone --force');
      }
    }

    return $failed > 0 ? 1 : 0;
  }
}
