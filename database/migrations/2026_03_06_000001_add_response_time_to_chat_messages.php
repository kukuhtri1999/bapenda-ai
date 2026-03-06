<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Add response_time_seconds and answer_text columns to chat_messages.
   * - response_time_seconds: how many seconds the AI took to respond (float)
   * - answer_text is already stored in `answer` column (added earlier)
   */
  public function up(): void
  {
    Schema::table('chat_messages', function (Blueprint $table) {
      // Store AI response duration in seconds (precision 2)
      $table->decimal('response_time_seconds', 8, 2)
        ->nullable()
        ->after('sentiment')
        ->comment('Time in seconds the AI took to generate the answer');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('chat_messages', function (Blueprint $table) {
      $table->dropColumn('response_time_seconds');
    });
  }
};
