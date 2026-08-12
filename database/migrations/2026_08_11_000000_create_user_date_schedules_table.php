<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel untuk mengelola penugasan karyawan di tanggal-tanggal tertentu
     * Misalnya: karyawan piket, wajib masuk di hari libur, WFH, atau libur khusus.
     */
    public function up(): void
    {
        Schema::create('user_date_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tgl');
            $table->string('status_wajib')->default('wajib_masuk');
            // 'wajib_masuk' = harus hadir di tanggal ini (override jadwal)
            // 'libur_khusus' = libur di tanggal ini (override jadwal mingguan)
            // 'wfh'          = WFH di tanggal ini
            $table->string('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique(['user_id', 'tgl']);
            $table->index('tgl');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_date_schedules');
    }
};
