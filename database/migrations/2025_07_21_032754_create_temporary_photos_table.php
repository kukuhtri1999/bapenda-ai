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
        Schema::create('temporary_photos', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index();
            $table->string('original_filename');
            $table->string('original_path');
            $table->string('edited_filename');
            $table->string('edited_path');
            $table->integer('file_size');
            $table->string('mime_type');
            $table->json('edit_details')->nullable(); // Store crop, rotation, temperature details
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temporary_photos');
    }
};
