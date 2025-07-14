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
        Schema::create('tambahan_biaya', function (Blueprint $table) {
            $table->id();
            $table->string('label_biaya');
            $table->decimal('harga_biaya', 15, 2);
            $table->foreignId('id_data_pkb')->constrained('data_pkb')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tambahan_biaya');
    }
};
