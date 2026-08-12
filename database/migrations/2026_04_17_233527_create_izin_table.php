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
        Schema::create('izins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('jenis_izin'); // Sakit, Izin, Qubul, dll
            $table->date('tgl_mulai');
            $table->date('tgl_selesai')->nullable();
            $table->text('alasan');
            $table->string('lampiran')->nullable();
            $table->string('status')->default('Pending'); // Pending, Disetujui, Ditolak
            $table->text('keterangan_admin')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('izin');
    }
};
