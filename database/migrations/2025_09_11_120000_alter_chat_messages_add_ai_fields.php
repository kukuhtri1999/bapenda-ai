<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    Schema::table('chat_messages', function (Blueprint $table) {
      // Add nullable AI fields
      $table->text('answer')->nullable()->after('content');
      $table->string('topic', 100)->nullable()->after('answer');
      $table->enum('sentiment', ['positive', 'neutral', 'negative'])->nullable()->after('topic');

      $table->index('topic');
      $table->index('sentiment');
    });

    // Extend enum values for role to include 'context' if not present
    // Note: Works for MySQL; adjust if using different DB.
    try {
      DB::statement("ALTER TABLE chat_messages MODIFY COLUMN role ENUM('user','assistant','system','context') NOT NULL");
    } catch (\Throwable $e) {
      // ignore if DB driver doesn't support or already modified
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    // We won't drop columns to avoid data loss; keep it simple and safe.
    // If needed, implement a full down() depending on environment policies.
  }
};
