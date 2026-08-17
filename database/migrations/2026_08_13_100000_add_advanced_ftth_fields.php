<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom ke pelanggan untuk Baseline Redaman
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->float('baseline_rx_power')->nullable()->after('onu_rx_power')
                ->comment('RX power saat aktivasi — baseline untuk deteksi degradasi');
            $table->timestamp('baseline_set_at')->nullable()->after('baseline_rx_power');
        });

        // Tabel settings Telegram
        Schema::create('telegram_settings', function (Blueprint $table) {
            $table->id();
            $table->string('bot_token')->nullable();
            $table->string('chat_id')->nullable();
            $table->boolean('enabled')->default(false);
            $table->boolean('notify_onu_offline')->default(true);
            $table->boolean('notify_odp_full')->default(true);
            $table->boolean('notify_kabel_offline')->default(true);
            $table->integer('offline_threshold_minutes')->default(5);
            $table->timestamps();
        });

        // Tabel log ping
        Schema::create('ping_logs', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address');
            $table->string('hostname')->nullable();
            $table->boolean('is_reachable')->default(false);
            $table->float('avg_rtt_ms')->nullable();
            $table->text('raw_output')->nullable();
            $table->unsignedBigInteger('pelanggan_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('ip_address');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ping_logs');
        Schema::dropIfExists('telegram_settings');
        Schema::table('pelanggan', function (Blueprint $table) {
            $table->dropColumn(['baseline_rx_power', 'baseline_set_at']);
        });
    }
};
