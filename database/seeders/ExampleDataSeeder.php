<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WajibPajak;
use App\Models\ChatMessage;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class ExampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create 233 wajib_pajak in bulk
        $totalWajib = 233;
        $batch = 200;
        $wajib = [];
        // detect available columns to avoid inserting missing columns
        $wajibColumns = Schema::getColumnListing('wajib_pajak');

        for ($i = 0; $i < $totalWajib; $i++) {
            $w = WajibPajak::factory()->make()->toArray();
            // keep only columns that exist in the table
            $w = Arr::only($w, $wajibColumns);
            $w['created_at'] = now();
            $w['updated_at'] = now();
            $wajib[] = $w;
            if (count($wajib) >= $batch) {
                WajibPajak::insert($wajib);
                $wajib = [];
            }
        }
        if (!empty($wajib)) WajibPajak::insert($wajib);

        // Create 548 chat messages in bulk
        $totalMessages = 548;
        $batchMsg = 500;
        $messages = [];
        $chatColumns = Schema::getColumnListing('chat_messages');

        // Ensure there are some Chat records to satisfy foreign key constraints
        $desiredChats = 800;
        $existingChats = \App\Models\Chat::count();
        if ($existingChats < $desiredChats) {
            $toCreate = $desiredChats - $existingChats;
            $chunks = array_chunk(range(1, $toCreate), 200);
            foreach ($chunks as $chunk) {
                $rows = [];
                foreach ($chunk as $n) {
                    $c = \App\Models\Chat::factory()->make()->toArray();
                    $c = Arr::only($c, Schema::getColumnListing('chats'));
                    // normalize datetime and array fields
                    foreach ($c as $ck => $cv) {
                        if ($cv instanceof \DateTimeInterface) {
                            $c[$ck] = $cv->format('Y-m-d H:i:s');
                        } elseif (is_string($cv) && preg_match('/T\d{2}:\d{2}:\d{2}/', $cv)) {
                            try {
                                $c[$ck] = Carbon::parse($cv)->format('Y-m-d H:i:s');
                            } catch (\Throwable $e) {
                                // leave as-is
                            }
                        } elseif (is_array($cv) || is_object($cv)) {
                            $c[$ck] = json_encode($cv, JSON_UNESCAPED_UNICODE);
                        }
                    }
                    $c['created_at'] = now()->format('Y-m-d H:i:s');
                    $c['updated_at'] = $c['created_at'];
                    $rows[] = $c;
                }
                \App\Models\Chat::insert($rows);
            }
        }

        $chatIds = \App\Models\Chat::pluck('id')->toArray();

        for ($i = 0; $i < $totalMessages; $i++) {
            $m = ChatMessage::factory()->make()->toArray();
            $m = Arr::only($m, $chatColumns);
            // Ensure we only insert user-role messages. If the factory produced an
            // assistant row, move that text into the `answer` column and convert
            // the row into a user message. This aligns with the new schema where
            // assistant replies are stored inline in `answer` on the user message.
            $originalRole = $m['role'] ?? null;
            $originalContent = $m['content'] ?? null;
            // convert to user role
            $m['role'] = 'user';
            if ($originalRole === 'assistant') {
                // preserve the assistant text into `answer` if not already present
                if (empty($m['answer']) && !empty($originalContent)) {
                    $m['answer'] = $originalContent;
                }
                // ensure content contains a user-like message; if factory didn't
                // provide one, insert a short placeholder question (harmless seed data)
                if (empty($m['content']) || $m['content'] === $originalContent) {
                    $m['content'] = 'Permintaan contoh tentang layanan Samsat (isi contoh).';
                }
            }
            // assign a valid chat_id to avoid FK constraint errors
            if (empty($m['chat_id']) || !in_array($m['chat_id'], $chatIds)) {
                $m['chat_id'] = $chatIds[array_rand($chatIds)];
            }
            $created = now()->subMinutes(rand(0, 60 * 24 * 365));
            $m['created_at'] = $created->format('Y-m-d H:i:s');
            $m['updated_at'] = $m['created_at'];

            // Ensure sent_at and any array/object fields are converted to strings
            if (isset($m['sent_at'])) {
                if ($m['sent_at'] instanceof \DateTimeInterface) {
                    $m['sent_at'] = $m['sent_at']->format('Y-m-d H:i:s');
                } elseif (is_string($m['sent_at'])) {
                    // parse ISO strings like 2025-02-21T17:22:21.000000Z
                    try {
                        $m['sent_at'] = Carbon::parse($m['sent_at'])->format('Y-m-d H:i:s');
                    } catch (\Throwable $e) {
                        // fallback: leave as-is (will likely cause DB error)
                    }
                }
            }
            foreach ($m as $k => $v) {
                if (is_array($v) || is_object($v)) {
                    $m[$k] = json_encode($v, JSON_UNESCAPED_UNICODE);
                }
            }
            $messages[] = $m;
            if (count($messages) >= $batchMsg) {
                ChatMessage::insert($messages);
                $messages = [];
            }
        }
        if (!empty($messages)) ChatMessage::insert($messages);
    }
}
