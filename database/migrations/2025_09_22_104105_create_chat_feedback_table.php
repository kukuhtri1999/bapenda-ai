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
        Schema::create('chat_feedback', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('nama')->nullable();
            $table->string('nopol')->nullable();
            $table->string('nomer_wa')->nullable();
            $table->integer('rating')->comment('1-5 stars rating');
            $table->text('feedback_text')->nullable();
            $table->json('chat_summary')->nullable()->comment('Summary of chat conversation');
            $table->timestamp('chat_ended_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_feedback');
    }
};
