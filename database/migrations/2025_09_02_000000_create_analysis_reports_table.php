<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
  public function up(): void
  {
    Schema::create('analysis_reports', function (Blueprint $table) {
      $table->id();
      $table->dateTime('start_date');
      $table->dateTime('end_date');
      $table->unsignedBigInteger('chat_count')->default(0);
      $table->json('summary_json')->nullable();
      $table->string('status')->default('completed'); // pending, running, completed, failed
      $table->text('notes')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('analysis_reports');
  }
};
