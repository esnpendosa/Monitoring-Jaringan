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
        Schema::create('biodatas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('kode_pegawai')->nullable();
            $table->string('nik')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->text('alamat')->nullable();
            $table->string('agama')->nullable();
            $table->string('gol_darah')->nullable();
            $table->string('no_wa')->nullable();
            $table->string('rekening')->nullable();
            $table->date('tgl_masuk')->nullable();
            $table->string('status_sertifikat')->nullable();
            $table->string('status_pegawai')->nullable();
            $table->date('purna_tugas')->nullable();
            $table->string('pendidikan_terakhir')->nullable();
            $table->json('riwayat_pendidikan')->nullable();
            $table->json('keluarga')->nullable();
            $table->timestamps();
        });

        Schema::create('dokumens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('tipe'); // KTP, KK, Ijazah, etc.
            $table->string('file_path');
            $table->string('status')->default('Menunggu'); // Menunggu, Disetujui, Ditolak
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('cutis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tgl_mulai');
            $table->date('tgl_selesai');
            $table->text('alasan');
            $table->string('status')->default('Menunggu'); // Menunggu, Disetujui, Ditolak
            $table->timestamps();
        });

        Schema::create('sk_permohonans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->string('file_sk')->nullable();
            $table->string('status')->default('Menunggu');
            $table->timestamps();
        });

        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('pin')->index();
            $table->date('tgl');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('status_kehadiran')->nullable(); // Hadir, Terlambat, dll
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
        Schema::dropIfExists('sk_permohonans');
        Schema::dropIfExists('cutis');
        Schema::dropIfExists('dokumens');
        Schema::dropIfExists('biodatas');
    }
};
