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
        Schema::create('package_upgrades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pelanggan');
            $table->unsignedBigInteger('id_tagihan')->nullable();
            $table->string('paket_lama');
            $table->decimal('harga_lama', 15, 2);
            $table->string('paket_baru');
            $table->decimal('harga_baru', 15, 2);
            $table->string('status')->default('pending'); // pending, paid, completed, cancelled
            $table->timestamps();

            $table->foreign('id_pelanggan')->references('id_pelanggan')->on('pelanggan')->onDelete('cascade');
            $table->foreign('id_tagihan')->references('id_tagihan')->on('tagihan')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_upgrades');
    }
};
