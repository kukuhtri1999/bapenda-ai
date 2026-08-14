<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('knowledge_gaps', function (Blueprint $table) {
            $table->id();
            $table->text('query');
            $table->string('normalized_query', 255)->index();
            $table->enum('source', ['low_confidence', 'fallback', 'flagged_chat', 'manual'])->default('low_confidence');
            $table->decimal('similarity_score', 5, 4)->nullable();
            $table->integer('frequency')->default(1);
            $table->string('session_id', 100)->nullable()->index();
            $table->enum('status', ['pending', 'resolved', 'dismissed'])->default('pending')->index();
            $table->foreignId('draft_kb_id')->nullable()->constrained('knowledge_bases')->nullOnDelete();
            $table->json('suggested_draft')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_gaps');
    }
};
