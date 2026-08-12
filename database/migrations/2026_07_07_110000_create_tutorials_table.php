<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutorials', function (Blueprint $table) {
            $table->id();
            $table->string('judul');
            $table->string('slug')->unique();
            $table->string('kategori')->default('Modem'); // Modem, Router, WiFi, Umum
            $table->string('thumbnail')->nullable();      // Path gambar cover
            $table->text('ringkasan')->nullable();        // Deskripsi singkat
            $table->longText('konten');                   // Rich text HTML (TinyMCE)
            $table->integer('urutan')->default(0);
            $table->boolean('is_published')->default(false);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tutorials');
    }
};
