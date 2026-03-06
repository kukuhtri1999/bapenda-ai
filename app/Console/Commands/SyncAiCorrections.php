<?php

namespace App\Console\Commands;

use App\Models\KnowledgeBase;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

/**
 * SyncAiCorrections
 * ─────────────────────────────────────────────────────────────────────────────
 * Reads TAMBAHAN_KB_PENGETAHUAN_AI.md and upserts each bullet-point item
 * into the knowledge_bases table as type='tambahan_sistem'.
 *
 * Run after every edit to the markdown file:
 *   php artisan kb:sync-corrections
 *   php artisan kb:sync-corrections --file=path/to/file.md
 *
 * How it tracks rows: uses a hash of the item text stored in metadata->source_hash.
 * New items → inserted. Changed items → updated. Removed items → set inactive.
 * ─────────────────────────────────────────────────────────────────────────────
 */
class SyncAiCorrections extends Command
{
  protected $signature = 'kb:sync-corrections
                            {--file= : Path to the markdown corrections file (relative to project root or absolute)}
                            {--dry-run : Preview changes without writing to the database}';

  protected $description = 'Sync TAMBAHAN_KB_PENGETAHUAN_AI.md corrections into the knowledge base as type=tambahan_sistem';

  private const TYPE     = 'tambahan_sistem';
  private const CATEGORY = 'Koreksi Sistem';
  private const STATUS   = 'published';

  public function handle(): int
  {
    $filePath = $this->option('file')
      ?: base_path('.specstory/TAMBAHAN_KB_PENGETAHUAN_AI.md');

    if (!file_exists($filePath)) {
      $this->error("File not found: {$filePath}");
      return Command::FAILURE;
    }

    $dryRun = (bool) $this->option('dry-run');
    $lines  = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    // ── Parse bullet-point lines ──────────────────────────────────────
    $items = [];
    foreach ($lines as $line) {
      $text = trim($line);
      // Accept lines starting with - • * or nothing (plain lines)
      if (preg_match('/^[-•*]\s+(.+)/', $text, $m)) {
        $text = trim($m[1]);
      } elseif (str_starts_with($text, '#')) {
        // Skip heading lines
        continue;
      }
      if (strlen($text) < 5) continue;
      $items[] = $text;
    }

    if (empty($items)) {
      $this->warn("No bullet-point items found in {$filePath}.");
      return Command::SUCCESS;
    }

    $this->info("Found " . count($items) . " correction(s) in file.");

    // ── Existing corrections from DB ──────────────────────────────────
    $existing = KnowledgeBase::withTrashed()
      ->where('type', self::TYPE)
      ->get()
      ->keyBy(fn($kb) => $kb->metadata['source_hash'] ?? '');

    $seenHashes   = [];
    $created      = 0;
    $updated      = 0;

    foreach ($items as $index => $text) {
      $hash  = md5($text);
      $seenHashes[] = $hash;
      $title = 'Koreksi AI #' . ($index + 1) . ': ' . mb_substr($text, 0, 60) . (mb_strlen($text) > 60 ? '…' : '');

      if (isset($existing[$hash])) {
        // Already exists — ensure active and restore if soft-deleted
        $kb = $existing[$hash];
        if ($kb->deleted_at || !$kb->is_active) {
          if (!$dryRun) {
            $kb->restore();
            $kb->update(['is_active' => true, 'status' => self::STATUS]);
          }
          $this->line("  [RESTORED] {$title}");
          $updated++;
        }
        continue;
      }

      // New item
      $this->line("  [INSERT]   {$title}");
      if (!$dryRun) {
        $entry = new KnowledgeBase([
          'title'         => $title,
          'question'      => $text,
          'answer'        => $text,
          'content'       => $text,
          'excerpt'       => mb_substr($text, 0, 200),
          'category'      => self::CATEGORY,
          'type'          => self::TYPE,
          'source_type'   => 'manual',
          'status'        => self::STATUS,
          'is_active'     => true,
          'priority'      => 100, // high priority so it surfaces in any search
          'published_at'  => now(),
          'metadata'      => [
            'source_file'  => basename($filePath),
            'source_hash'  => $hash,
            'item_index'   => $index,
            'is_correction' => true,
          ],
          'keywords'      => ['koreksi', 'aturan', 'samsat', 'induk', 'ketentuan'],
        ]);
        $entry->save();
      }
      $created++;
    }

    // ── Deactivate removed items ──────────────────────────────────────
    $deactivated = 0;
    foreach ($existing as $hash => $kb) {
      if (!in_array($hash, $seenHashes, true) && $kb->is_active) {
        $this->line("  [DEACTIVATE] {$kb->title}");
        if (!$dryRun) {
          $kb->update(['is_active' => false]);
        }
        $deactivated++;
      }
    }

    $mode = $dryRun ? ' (DRY RUN — no changes written)' : '';
    $this->info("Done{$mode}: +{$created} created, {$updated} restored, {$deactivated} deactivated.");

    // Rebuild search_content for new entries
    if (!$dryRun && $created > 0) {
      $this->info("Rebuilding search_content…");
      KnowledgeBase::where('type', self::TYPE)
        ->where('is_active', true)
        ->chunk(50, function ($rows) {
          foreach ($rows as $kb) {
            $kb->generateSearchContent();
            $kb->saveQuietly();
          }
        });
    }

    return Command::SUCCESS;
  }
}
