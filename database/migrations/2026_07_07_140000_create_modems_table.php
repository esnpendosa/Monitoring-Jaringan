<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modems', function (Blueprint $table) {
            $table->id();
            $table->string('nama');           // e.g. Huawei HG8245H5
            $table->string('merek');          // e.g. Huawei
            $table->string('model');          // e.g. HG8245H5
            $table->string('tipe')->nullable(); // e.g. EPON, GPON, Router
            $table->string('image_path')->nullable();
            $table->text('deskripsi')->nullable();
            $table->text('spesifikasi')->nullable(); // JSON or plain text
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modems');
    }
};
