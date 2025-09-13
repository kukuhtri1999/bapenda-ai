<?php

namespace App\Imports;

use App\Models\ChatMessage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class ChatMessagesImport implements ToCollection, WithHeadingRow, WithChunkReading
{
  private int $imported = 0;
  private int $skipped = 0;
  private array $errors = [];

  public function collection(Collection $rows)
  {
    $allowedRoles = ['user', 'assistant', 'context'];
    $allowedSentiments = ['positive', 'neutral', 'negative'];
    $allowedTopics = config('analytics.categories', []);

    foreach ($rows as $index => $row) {
      $rowNumber = $index + 2; // heading row is #1 when WithHeadingRow
      try {
        $chatId = $row['chat_id'] ?? null;
        $role = isset($row['role']) ? strtolower(trim((string)$row['role'])) : 'user';
        $content = $row['content'] ?? null;
        $answer = $row['answer'] ?? null;
        $topic = $row['topic'] ?? null;
        $sentiment = isset($row['sentiment']) ? strtolower(trim((string)$row['sentiment'])) : null;
        $metadata = $row['metadata'] ?? null;
        $sentAt = $row['sent_at'] ?? null;
        // If an imported row is an assistant-only message, convert it into a
        // user message and move the assistant text into `answer`. This keeps
        // the DB focused on user-originated rows (assistant replies are
        // represented inline in `answer`).
        if ($role === 'assistant') {
          // move assistant content into answer
          if (empty($answer) && !empty($content)) {
            $answer = (string) $content;
          }
          // ensure the row represents a user message; provide a harmless
          // placeholder if no user content is present.
          if (empty($content)) {
            $content = 'Permintaan contoh tentang layanan Samsat (isi contoh).';
          }
          $role = 'user';
        }

        if (empty($chatId) || empty($role) || empty($content) || empty($sentAt)) {
          $this->skipped++;
          $this->errors[] = "Row {$rowNumber}: missing required fields (chat_id, role, content, sent_at)";
          continue;
        }

        // Normalize role
        if (!in_array($role, $allowedRoles, true)) {
          $role = 'user';
        }

        // Normalize topic to machine key (lowercase, underscores) and map to fallback when not allowed
        if (is_string($topic) && trim($topic) !== '') {
          $topicNorm = strtolower(trim((string)$topic));
          $topicNorm = preg_replace('/\s+/', '_', $topicNorm);
          $topicNorm = preg_replace('/[^a-z0-9_]/', '', $topicNorm);
          $topic = in_array($topicNorm, $allowedTopics, true) ? $topicNorm : 'lain_lain';
        } else {
          $topic = null; // keep null if not provided
        }

        // Validate sentiment
        if ($sentiment !== null && !in_array($sentiment, $allowedSentiments, true)) {
          $sentiment = null;
        }

        // Validate/parse sent_at
        try {
          $sentAtParsed = Carbon::parse((string)$sentAt);
        } catch (\Throwable $e) {
          $this->skipped++;
          $this->errors[] = "Row {$rowNumber}: invalid sent_at '{$sentAt}'";
          continue;
        }

        // Normalize metadata to array if provided
        if (is_string($metadata) && trim($metadata) !== '') {
          $meta = json_decode($metadata, true);
          $metadata = is_array($meta) ? $meta : null;
        } elseif (empty($metadata)) {
          $metadata = null;
        }

        ChatMessage::create([
          'chat_id' => (int) $chatId,
          'role' => $role,
          'content' => (string) $content,
          'answer' => $answer !== null && $answer !== '' ? (string) $answer : null,
          'topic' => $topic !== null && $topic !== '' ? (string) $topic : null,
          'sentiment' => $sentiment !== null && $sentiment !== '' ? (string) $sentiment : null,
          'metadata' => $metadata,
          'sent_at' => $sentAtParsed,
        ]);

        $this->imported++;
      } catch (\Throwable $e) {
        $this->skipped++;
        $this->errors[] = "Row {$rowNumber}: " . $e->getMessage();
        Log::warning('Import skipped row: ' . $e->getMessage());
        continue;
      }
    }
  }

  public function chunkSize(): int
  {
    return 500; // process 500 rows per chunk to control memory
  }

  public function getImportedCount(): int
  {
    return $this->imported;
  }
  public function getSkippedCount(): int
  {
    return $this->skipped;
  }
  public function getErrors(): array
  {
    return $this->errors;
  }
}
