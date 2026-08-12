<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('alasan');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->string('status_unit')->default('Pending'); // Pending, Disetujui, Ditolak
            $table->string('status_yayasan')->default('Pending'); // Pending, Disetujui, Ditolak
            $table->string('status_akhir')->default('Pending'); // Pending, Disetujui, Ditolak
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutis');
    }
};
