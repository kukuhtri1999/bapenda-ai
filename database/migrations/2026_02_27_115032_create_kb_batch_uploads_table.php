<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_batch_uploads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('status')->default('pending'); // pending | processing | completed | completed_with_errors | failed
            $table->integer('total_files')->default(0);
            $table->integer('processed')->default(0);
            $table->integer('failed')->default(0);
            $table->string('default_category')->nullable();
            $table->string('default_type')->default('regulation');
            $table->string('default_status')->default('published');
            $table->json('files'); // [{original_name, temp_path, mime_type, size, status, error, kb_id}]
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_batch_uploads');
    }
};
