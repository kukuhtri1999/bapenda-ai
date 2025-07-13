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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['user', 'assistant', 'system']); // Message sender type
            $table->text('content'); // Message content
            $table->json('metadata')->nullable(); // Additional data like tokens used, response time, etc
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['chat_id', 'sent_at']);
            $table->index('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
