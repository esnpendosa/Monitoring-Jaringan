<?php

namespace App\Http\Controllers;

use App\Models\Tagihan;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Notification;

class PaymentController extends Controller
{
    protected $mikrotikService;

    public function __construct(MikrotikService $mikrotikService)
    {
        $this->mikrotikService = $mikrotikService;
        
        $serverKey = \App\Models\Setting::get('midtrans_server_key');
        if (empty($serverKey)) {
            $serverKey = config('services.midtrans.server_key');
        }
        
        $isProduction = \App\Models\Setting::get('midtrans_is_production');
        if ($isProduction === null) {
            $isProduction = config('services.midtrans.is_production');
        } else {
            $isProduction = $isProduction == '1';
        }

        Config::$serverKey = trim($serverKey);
        Config::$isProduction = $isProduction;
        Config::$isSanitized = config('services.midtrans.is_sanitized', true);
        Config::$is3ds = config('services.midtrans.is_3ds', true);
    }

    public function getSnapToken(Tagihan $tagihan)
    {
        $adminFee = (int) \App\Models\Setting::get('payment_fee', 0);
        $totalAmount = (int) $tagihan->jumlah + $adminFee;

        $params = [
            'transaction_details' => [
                'order_id' => 'INV-' . $tagihan->id_tagihan . '-' . time(),
                'gross_amount' => $totalAmount,
            ],
            'item_details' => [
                [
                    'id' => 'BILL-' . $tagihan->id_tagihan,
                    'price' => (int) $tagihan->jumlah,
                    'quantity' => 1,
                    'name' => 'Tagihan WiFi Periode ' . $tagihan->bulan . '/' . $tagihan->tahun,
                ],
            ],
            'customer_details' => [
                'first_name' => $tagihan->pelanggan->nama_pelanggan,
                'email' => $tagihan->pelanggan->kode_pelanggan . '@rozitech.net', // Placeholder
                'phone' => $tagihan->pelanggan->no_wa,
            ],
        ];

        // Add admin fee as item if exists
        if ($adminFee > 0) {
            $params['item_details'][] = [
                'id' => 'FEE',
                'price' => $adminFee,
                'quantity' => 1,
                'name' => 'Biaya Admin',
            ];
        }

        try {
            $snapToken = Snap::getSnapToken($params);
            $tagihan->update(['snap_token' => $snapToken]);
            return response()->json(['token' => $snapToken]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function callback(Request $request)
    {
        $notif = new Notification();

        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $order_id = $notif->order_id;
        $fraud = $notif->fraud_status;

        // Extract ID from order_id (INV-ID-TIME)
        $parts = explode('-', $order_id);
        $tagihanId = $parts[1];
        $tagihan = Tagihan::find($tagihanId);

        if (!$tagihan) {
            return response()->json(['message' => 'Tagihan not found'], 404);
        }

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    $tagihan->update(['status' => 'unpaid']);
                } else {
                    $this->markAsPaid($tagihan, 'Midtrans (' . $type . ')');
                }
            }
        } else if ($transaction == 'settlement') {
            $this->markAsPaid($tagihan, 'Midtrans (' . $type . ')');
        } else if ($transaction == 'pending') {
            $tagihan->update(['status' => 'unpaid']);
        } else if ($transaction == 'deny' || $transaction == 'expire' || $transaction == 'cancel') {
            $tagihan->update(['status' => 'cancelled']);
        }

        return response()->json(['status' => 'success']);
    }

    public function payById($kode_pelanggan)
    {
        $pelanggan = \App\Models\Pelanggan::where('kode_pelanggan', $kode_pelanggan)->firstOrFail();
        
        // Find latest unpaid bill
        $tagihan = $pelanggan->tagihan()->where('status', 'unpaid')->latest()->first();
        
        if (!$tagihan) {
            return view('content.payment.no_bill', compact('pelanggan'));
        }

        return view('content.payment.quick_pay', compact('pelanggan', 'tagihan'));
    }

    protected function markAsPaid(Tagihan $tagihan, $paymentType = 'Midtrans')
    {
        $tagihan->update([
            'status' => 'paid',
            'paid_at' => now(),
            'metode_pembayaran' => $paymentType
        ]);

        // Log the activity
        try {
            \App\Helpers\ActivityLogger::log(
                'Sistem (Midtrans) berhasil memverifikasi otomatis tagihan #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') sebesar Rp ' . number_format($tagihan->jumlah, 0, ',', '.') . ' via ' . $paymentType,
                'tagihan',
                'Midtrans Gateway',
                'System'
            );
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Gagal mencatat log aktivitas verifikasi otomatis Midtrans: " . $e->getMessage());
        }

        $pelanggan = $tagihan->pelanggan;
        if ($pelanggan && $pelanggan->id_router) {
            // Re-enable Mikrotik Service
            $success = $this->mikrotikService->setSecretStatus($pelanggan->router, $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan, $pelanggan->mikrotik_type, false, $pelanggan->ip_address);
            if ($success) {
                $pelanggan->update(['is_active' => true, 'is_isolated' => false]);
            }
        }

        // Kirim Notifikasi WA setelah response (non-blocking)
        // Midtrans butuh response cepat, jangan block dengan pengiriman WA
        if ($pelanggan && $pelanggan->no_wa) {
            $tid = $tagihan->id_tagihan;
            app()->terminating(function () use ($tid) {
                try {
                    $t = \App\Models\Tagihan::find($tid);
                    if ($t) (new \App\Services\WhatsappClient())->sendReceipt($t);
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Gagal kirim nota bayar Midtrans: ' . $e->getMessage());
                }
            });
        }
    }
}
