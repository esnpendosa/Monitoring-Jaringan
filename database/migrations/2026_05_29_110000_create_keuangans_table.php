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
        Schema::create('keuangans', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['pengeluaran', 'psb'])->default('pengeluaran'); // 'pengeluaran' = Expenses, 'psb' = Pasang Baru
            $table->string('kategori'); // e.g. 'Gaji Pegawai', 'Pembelian Alat', 'PSB Pasang Baru', 'Lain-lain'
            $table->decimal('jumlah', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->date('tanggal');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keuangans');
    }
};
