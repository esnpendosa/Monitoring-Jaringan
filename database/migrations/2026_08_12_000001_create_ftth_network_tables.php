<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ===================================
        // 1. OLT (Optical Line Terminal)
        // ===================================
        Schema::create('olts', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('lokasi')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('ip_address')->nullable();
            $table->string('snmp_community')->default('public');
            $table->unsignedInteger('kapasitas_pon')->default(16); // jumlah port PON
            $table->enum('status', ['online', 'warning', 'offline'])->default('online');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        // ===================================
        // 2. Kabel FTTH
        // ===================================
        Schema::create('kabels', function (Blueprint $table) {
            $table->id();
            $table->string('label');                                      // nama/kode kabel
            $table->enum('tipe', ['feeder', 'distribusi', 'drop']);       // jenis kabel
            $table->enum('monitoring_type', ['realtime', 'manual'])->default('manual');
            $table->string('from_type');                                  // 'olt', 'odc', 'odp'
            $table->unsignedBigInteger('from_id');
            $table->string('to_type');                                    // 'odc', 'odp', 'pelanggan'
            $table->unsignedBigInteger('to_id');
            $table->json('geometry');                                     // [[lat,lng],[lat,lng],...] jalur kabel
            $table->unsignedInteger('jumlah_core')->default(1);
            $table->enum('status', ['online', 'warning', 'offline'])->default('online');
            $table->float('redaman_db')->nullable();                      // redaman terakhir terukur (dBm)
            $table->float('titik_putus_meter')->nullable();               // jarak dari from_id ke titik putus
            $table->text('catatan')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();
        });

        // ===================================
        // 3. Pembacaan Sensor RFTS / OTDR
        // ===================================
        Schema::create('rfts_readings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kabel_id');
            $table->foreign('kabel_id')->references('id')->on('kabels')->onDelete('cascade');
            $table->enum('status', ['ok', 'break', 'attenuation_high'])->default('ok');
            $table->float('redaman')->nullable();                         // dBm
            $table->float('jarak_putus_meter')->nullable();
            $table->timestamp('waktu_baca');
            $table->timestamps();
        });

        // ===================================
        // 4. Kolom tambahan di tabel pelanggan
        //    (odp_id, serial_ont, onu_rx_power, last_inform_at)
        // ===================================
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->unsignedBigInteger('odp_id')->nullable()->after('id_router');
            $table->foreign('odp_id')->references('id')->on('odc_odp')->onDelete('set null');
            $table->string('serial_ont')->nullable()->after('odp_id');   // serial number ONT/ONU
            $table->float('onu_rx_power')->nullable()->after('serial_ont'); // RX optical power (dBm)
            $table->timestamp('last_inform_at')->nullable()->after('onu_rx_power'); // last GenieACS inform
        });

        // ===================================
        // 5. Kolom tambahan di tabel odc_odp
        //    (olt_id, kapasitas, status_agregat)
        // ===================================
        Schema::table('odc_odp', function (Blueprint $table) {
            $table->unsignedBigInteger('olt_id')->nullable()->after('id');
            $table->foreign('olt_id')->references('id')->on('olts')->onDelete('set null');
            $table->unsignedInteger('kapasitas_core')->nullable()->after('deskripsi');  // kapasitas core (untuk ODC)
            $table->unsignedInteger('kapasitas_port')->nullable()->after('kapasitas_core'); // kapasitas port (untuk ODP)
            $table->enum('status', ['online', 'warning', 'offline'])->default('online')->after('kapasitas_port');
        });
    }

    public function down(): void
    {
        // Hapus foreign key dulu sebelum drop kolom/tabel
        Schema::table('odc_odp', function (Blueprint $table) {
            $table->dropForeign(['olt_id']);
            $table->dropColumn(['olt_id', 'kapasitas_core', 'kapasitas_port', 'status']);
        });

        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropForeign(['odp_id']);
            $table->dropColumn(['odp_id', 'serial_ont', 'onu_rx_power', 'last_inform_at']);
        });

        Schema::dropIfExists('rfts_readings');
        Schema::dropIfExists('kabels');
        Schema::dropIfExists('olts');
    }
};
