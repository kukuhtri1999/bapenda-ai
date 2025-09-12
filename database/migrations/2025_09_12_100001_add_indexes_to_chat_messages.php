<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('chat_messages', 'topic')) {
                // column exists based on model; skip adding
            }
            $table->index('sent_at', 'chat_messages_sent_at_idx');
            $table->index('sentiment', 'chat_messages_sentiment_idx');
            $table->index('topic', 'chat_messages_topic_idx');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropIndex('chat_messages_sent_at_idx');
            $table->dropIndex('chat_messages_sentiment_idx');
            $table->dropIndex('chat_messages_topic_idx');
        });
    }
};
