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
        Schema::table('riwayat_pegawais', function (Blueprint $table) {
            $table->string('file_sk')->nullable()->after('tgl_sk');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('riwayat_pegawais', function (Blueprint $table) {
            $table->dropColumn('file_sk');
        });
    }
};
