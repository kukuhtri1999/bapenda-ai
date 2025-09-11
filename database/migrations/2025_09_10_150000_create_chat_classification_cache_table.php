<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('chat_classification_cache', function (Blueprint $table) {
      $table->id();
      $table->string('text_hash', 64)->index();
      $table->string('category', 64);
      $table->string('sentiment', 16);
      $table->float('confidence')->default(0);
      $table->string('snippet', 220)->nullable();
      $table->timestamp('last_used_at')->nullable()->index();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('chat_classification_cache');
  }
};
