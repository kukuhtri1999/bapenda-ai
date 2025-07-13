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
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->unique(); // Session ID for anonymous users
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade'); // Optional logged in user
            $table->string('title')->nullable(); // Chat title/subject
            $table->string('status')->default('active'); // active, closed, archived
            $table->json('metadata')->nullable(); // Additional data like user agent, IP, etc
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('session_id');
            $table->index('last_activity_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
