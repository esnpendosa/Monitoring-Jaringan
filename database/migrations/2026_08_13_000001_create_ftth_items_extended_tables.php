<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===================================
        // 1. FTTH Items — unified table untuk
        //    Tiang Tumpu, Tiang ODP, Tiang ODC,
        //    Joint Closure, HTB, Server/Router, AP
        // ===================================
        Schema::create('ftth_items', function (Blueprint $table) {
            $table->id();
            $table->enum('kategori', [
                'server_router',
                'tiang_tumpu',
                'tiang_odp',
                'tiang_odc',
                'joint_closure',
                'htb',
                'access_point',
            ]);
            $table->string('nama');
            $table->string('kode')->nullable()->unique(); // kode unik item
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('status', ['online', 'warning', 'offline'])->default('online');

            // Field umum
            $table->string('merk')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('ip_address')->nullable();
            $table->date('tanggal_pasang')->nullable();
            $table->text('deskripsi')->nullable();
            $table->string('foto')->nullable();

            // Field khusus Tiang
            $table->float('tinggi_tiang')->nullable(); // meter
            $table->enum('material_tiang', ['beton', 'besi', 'kayu', 'galvanis'])->nullable();
            $table->integer('kapasitas_core')->nullable(); // untuk tiang ODP/ODC

            // Field khusus Server/Router
            $table->string('snmp_community')->nullable()->default('public');
            $table->integer('kapasitas_port')->nullable();

            // Field khusus HTB / AP
            $table->float('frekuensi_ghz')->nullable();
            $table->float('daya_watt')->nullable();
            $table->float('gain_dbi')->nullable();

            // Relasi
            $table->unsignedBigInteger('parent_id')->nullable(); // mis. tiang induk
            $table->string('parent_type')->nullable(); // 'ftth_item', 'olt', 'odc_odp'
            $table->unsignedBigInteger('olt_id')->nullable();
            $table->foreign('olt_id')->references('id')->on('olts')->onDelete('set null');

            $table->string('updated_by')->nullable();
            $table->timestamps();

            $table->index(['kategori', 'status']);
            $table->index(['latitude', 'longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ftth_items');
    }
};
