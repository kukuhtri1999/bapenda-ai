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
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, integer, boolean, json, float
            $table->string('group')->default('general'); // general, chat, system, notification, etc
            $table->string('label');
            $table->text('description')->nullable();
            $table->json('options')->nullable(); // For select, radio, checkbox options
            $table->string('validation_rules')->nullable(); // Laravel validation rules
            $table->integer('sort_order')->default(0);
            $table->boolean('is_public')->default(false); // If setting can be accessed by public API
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['group', 'is_active']);
            $table->index(['key', 'is_active']);
            $table->index('sort_order');

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // Insert default settings
        DB::table('app_settings')->insert([
            [
                'key' => 'chat.max_messages_per_session',
                'value' => '50',
                'type' => 'integer',
                'group' => 'chat',
                'label' => 'Max Chat per Session',
                'description' => 'Maksimum jumlah pesan chat yang diizinkan per sesi. Setelah mencapai batas, user harus membuat sesi baru.',
                'validation_rules' => 'required|integer|min:5|max:200',
                'sort_order' => 1,
                'is_public' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'chat.session_timeout_minutes',
                'value' => '60',
                'type' => 'integer',
                'group' => 'chat',
                'label' => 'Session Timeout (Menit)',
                'description' => 'Waktu timeout sesi chat dalam menit. Sesi akan otomatis berakhir setelah tidak ada aktivitas.',
                'validation_rules' => 'required|integer|min:15|max:1440',
                'sort_order' => 2,
                'is_public' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'chat.require_user_info',
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'chat',
                'label' => 'Wajib Info User',
                'description' => 'Apakah user wajib mengisi nama, nomor polisi, dan nomor WhatsApp sebelum memulai chat.',
                'validation_rules' => 'required|boolean',
                'sort_order' => 3,
                'is_public' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'general.app_name',
                'value' => 'Bapenda AI Samsat Lamongan',
                'type' => 'string',
                'group' => 'general',
                'label' => 'Nama Aplikasi',
                'description' => 'Nama aplikasi yang ditampilkan di frontend.',
                'validation_rules' => 'required|string|max:100',
                'sort_order' => 1,
                'is_public' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'general.maintenance_mode',
                'value' => 'false',
                'type' => 'boolean',
                'group' => 'general',
                'label' => 'Mode Maintenance',
                'description' => 'Aktifkan mode maintenance untuk menghentikan akses user sementara.',
                'validation_rules' => 'required|boolean',
                'sort_order' => 2,
                'is_public' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
