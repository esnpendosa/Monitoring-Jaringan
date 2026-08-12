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
        // 1. Add pin_fingerspot to users table
        Schema::table('users', function (Blueprint $table) {
            $table->string('pin_fingerspot')->nullable()->unique()->after('id_role');
        });

        // 2. Create absensis table
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('pin')->nullable()->index();
            $table->date('tgl');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('status_kehadiran')->nullable();
            $table->string('lokasi')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tgl']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('absensis');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('pin_fingerspot');
        });
    }
};
