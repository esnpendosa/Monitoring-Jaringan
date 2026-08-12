<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->string('unit')->nullable();
            $table->string('dokumen_pendukung')->nullable();
            $table->string('ket_dokumen')->nullable();
            $table->text('catatan')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cutis', function (Blueprint $table) {
            $table->dropColumn(['unit', 'dokumen_pendukung', 'ket_dokumen', 'catatan']);
        });
    }
};
