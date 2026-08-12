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
        Schema::table('biodatas', function (Blueprint $table) {
            $table->date('tgl_menikah')->nullable();
            $table->string('status_pernikahan')->nullable();
            $table->integer('jumlah_anak')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('biodatas', function (Blueprint $table) {
            $table->dropColumn(['tgl_menikah', 'status_pernikahan', 'jumlah_anak']);
        });
    }
};
