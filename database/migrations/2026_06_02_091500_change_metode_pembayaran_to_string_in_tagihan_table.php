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
        Schema::table('tagihan', function (Blueprint $table) {
            $table->string('metode_pembayaran')->nullable()->change();
            $table->string('status')->default('unpaid')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tagihan', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['otomatis', 'manual', 'cash'])->nullable()->change();
            $table->enum('status', ['unpaid', 'paid', 'cancelled'])->default('unpaid')->change();
        });
    }
};
