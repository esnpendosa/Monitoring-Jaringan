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
        Schema::create('riwayat_pegawais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('thn_ajaran')->nullable();
            $table->string('unit')->nullable();
            $table->string('jenis_pegawai')->nullable();
            $table->string('jabatan')->nullable();
            $table->string('mapel')->nullable();
            $table->string('status_pegawai')->nullable();
            $table->string('golongan')->nullable();
            $table->string('thn_mulai')->nullable();
            $table->date('tgl_selesai')->nullable();
            $table->date('tgl_sk')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pegawais');
    }
};
