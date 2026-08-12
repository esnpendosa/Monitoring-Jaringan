<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\NotificationHelper;

class Tagihan extends Model
{
    use HasFactory;

    protected $table = 'tagihan';
    protected $primaryKey = 'id_tagihan';

    protected $fillable = [
        'id_pelanggan',
        'bulan',
        'tahun',
        'jumlah',
        'status',
        'metode_pembayaran',
        'bukti_bayar',
        'catatan_admin',
        'snap_token',
        'payment_url',
        'paid_at',
        'bayar_di_awal',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'bayar_di_awal' => 'boolean',
    ];

    protected static function booted()
    {
        static::saving(function ($tagihan) {
            if ($tagihan->status === 'paid' && $tagihan->paid_at) {
                $paidDate = \Carbon\Carbon::parse($tagihan->paid_at);
                $paidYearMonth = $paidDate->format('Y-m');
                $billYearMonth = sprintf('%04d-%02d', $tagihan->tahun, $tagihan->bulan);
                
                if ($paidYearMonth < $billYearMonth) {
                    $tagihan->bayar_di_awal = true;
                }
            }
        });

        static::updated(function ($tagihan) {
            if ($tagihan->status === 'paid') {
                $upgrade = \App\Models\PackageUpgrade::where('id_tagihan', $tagihan->id_tagihan)
                    ->where('status', 'pending')
                    ->first();
                if ($upgrade) {
                    $upgrade->update(['status' => 'completed']);
                    
                    $pelanggan = $tagihan->pelanggan;
                    if ($pelanggan) {
                        $pelanggan->update([
                            'paket' => $upgrade->paket_baru,
                            'harga_layanan' => $upgrade->harga_baru,
                            'is_active' => true,
                        ]);

                        if ($pelanggan->id_router) {
                            try {
                                $mikrotikService = app(\App\Services\MikrotikService::class);
                                $username = $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan;
                                $mikrotikService->setSecretStatus(
                                    $pelanggan->router,
                                    $username,
                                    $pelanggan->mikrotik_type,
                                    false,
                                    $pelanggan->ip_address,
                                    $pelanggan->paket
                                );
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to sync upgraded package to Mikrotik: " . $e->getMessage());
                            }
                        }

                        // Send WA notifications
                        try {
                            $waClient = new \App\Services\WhatsappClient();
                            if ($pelanggan->no_wa && $pelanggan->wa_active && \App\Models\Setting::get('wa_billing_notification_enabled', '1') == '1') {
                                $custMsg = "🎉 *UPGRADE PAKET WIFI BERHASIL*\n\n";
                                $custMsg .= "Halo *" . $pelanggan->nama_pelanggan . "* (" . $pelanggan->kode_pelanggan . "),\n";
                                $custMsg .= "Pembayaran upgrade paket Anda telah diverifikasi.\n";
                                $custMsg .= "Paket Anda telah berhasil di-upgrade:\n";
                                $custMsg .= "• Paket Baru: *" . $upgrade->paket_baru . "*\n";
                                $custMsg .= "• Biaya Layanan: *Rp " . number_format($upgrade->harga_baru, 0, ',', '.') . "/bulan*\n\n";
                                $custMsg .= "Layanan internet Anda telah otomatis disesuaikan. Terima kasih nggih!";
                                $waClient->sendMessage($pelanggan->no_wa, ['text' => $custMsg], true);
                            }

                            // To admin
                            $adminNum = \App\Models\Setting::get('wa_admin_number'); 
                            if (empty($adminNum)) {
                                $adminNum = env('WHATSAPP_ADMIN_NUMBER');
                            }
                            if ($adminNum) {
                                $adminMsg = "🔔 *LAPORAN UPGRADE PAKET WIFI*\n\n";
                                $adminMsg .= "Pelanggan: *" . $pelanggan->nama_pelanggan . "* (" . $pelanggan->kode_pelanggan . ")\n";
                                $adminMsg .= "Upgrade: *" . $upgrade->paket_lama . "* ➔ *" . $upgrade->paket_baru . "*\n";
                                $adminMsg .= "Status: *BERHASIL & SINKRON MIKROTIK*\n";
                                $waClient->sendMessage($adminNum, ['text' => $adminMsg], true);
                            }
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error("Failed to send WA notification for package upgrade: " . $e->getMessage());
                        }

                        // ── In-App Notification ──────────────────────────────
                        NotificationHelper::sendToRole('Admin', 'upgrade_paket',
                            'Upgrade Paket Selesai',
                            "{$pelanggan->nama_pelanggan} ({$pelanggan->kode_pelanggan}) upgrade " .
                            "{$upgrade->paket_lama} → {$upgrade->paket_baru} sudah selesai.",
                            ['icon' => 'bx-trending-up', 'color' => 'success', 'action_url' => route('upgrade-paket.index')]
                        );
                        NotificationHelper::sendToRole('Manajer', 'upgrade_paket',
                            'Upgrade Paket Selesai',
                            "{$pelanggan->nama_pelanggan} ({$pelanggan->kode_pelanggan}) upgrade " .
                            "{$upgrade->paket_lama} → {$upgrade->paket_baru} sudah selesai.",
                            ['icon' => 'bx-trending-up', 'color' => 'success', 'action_url' => route('upgrade-paket.index')]
                        );
                    }
                }
            }
        });
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }
}
