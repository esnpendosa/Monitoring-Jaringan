<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappClient
{
    protected $baseUrl;

    public function __construct()
    {
        $port = config('services.whatsapp_bot.port', env('BOT_PORT', 3000));
        $this->baseUrl = "http://127.0.0.1:{$port}";
    }

    protected function secret()
    {
        // Gunakan config() agar bekerja saat config cache aktif, fallback ke env() langsung
        return config('services.whatsapp_bot.secret', env('BOT_SECRET', 'rozitech-bot-secret-2024'));
    }

    public function sendMessage($phone, $data, $async = false)
    {
        if ($this->shouldBlockMessage($phone)) {
            return false;
        }
        Log::info("WhatsappClient: Attempting to send message to $phone via " . $this->baseUrl . ($async ? " (async)" : ""));
        try {
            $message = is_array($data) ? ($data['text'] ?? '') : $data;
            
            $response = Http::timeout(5) // Kurangi timeout: jika bot mati, tidak blokir lama
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/send-message', [
                    'phone'   => $phone,
                    'message' => $message,
                    'async'   => true // Selalu async agar bot langsung balas tanpa tunggu delivery
                ]);

            if (!$response->successful()) {
                Log::error("WhatsappClient Error Response: " . $response->status() . " - " . $response->body());
                return false;
            }

            Log::info("WhatsappClient: Message sent successfully to $phone");
            return true;
        } catch (\Exception $e) {
            Log::error("WhatsappClient Connection Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendFile($phone, $fileContent, $filename, $mimetype = 'application/pdf', $caption = '', $async = false)
    {
        if ($this->shouldBlockMessage($phone)) {
            return false;
        }
        Log::info("WhatsappClient: Attempting to send file $filename to $phone" . ($async ? " (async)" : ""));
        $payload = [
            'phone'    => $phone,
            'media'    => base64_encode($fileContent),
            'filename' => $filename,
            'mimetype' => $mimetype,
            'caption'  => $caption,
            'async'    => true, // Selalu async agar bot langsung balas tanpa tunggu delivery WA
        ];

        // Coba 2x: attempt pertama, jika gagal retry sekali lagi
        for ($attempt = 1; $attempt <= 2; $attempt++) {
            try {
                $response = Http::timeout(10) // Kurangi dari 90s ke 10s karena async=true
                    ->withHeaders(['X-Bot-Secret' => $this->secret()])
                    ->post($this->baseUrl . '/send-message', $payload);

                if ($response->successful()) {
                    Log::info("WhatsappClient: File $filename sent successfully to $phone (attempt $attempt)");
                    return true;
                }

                Log::warning("WhatsappClient File Send attempt $attempt failed ({$response->status()}): " . $response->body());
            } catch (\Exception $e) {
                Log::error("WhatsappClient File Send Error (attempt $attempt): " . $e->getMessage());
                if ($attempt === 2) return false;
                sleep(1); // Kurangi dari 3 detik ke 1 detik
            }
        }

        return false;
    }

    public function sendFileUrl($phone, $url, $filename, $mimetype = 'application/pdf', $caption = '', $async = false)
    {
        if ($this->shouldBlockMessage($phone)) {
            return false;
        }
        Log::info("WhatsappClient: Attempting to send file via URL $url to $phone" . ($async ? " (async)" : ""));
        try {
            $response = Http::timeout(90) // Naikkan timeout untuk file besar
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/send-message', [
                    'phone'    => $phone,
                    'url'      => $url,
                    'filename' => $filename,
                    'mimetype' => $mimetype,
                    'caption'  => $caption,
                    'async'    => $async
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error("WhatsappClient File URL Send Error: " . $e->getMessage());
            return false;
        }
    }

    public function sendReceipt(\App\Models\Tagihan $tagihan, $isManual = false)
    {
        $pelanggan = $tagihan->pelanggan;
        if (!$pelanggan || !$pelanggan->no_wa) return false;
        if ($this->shouldBlockMessage($pelanggan->no_wa)) {
            return false;
        }

        $monthName = date('F', mktime(0, 0, 0, $tagihan->bulan, 10));
        $amount = number_format($tagihan->jumlah, 0, ',', '.');
        $paidAt = $tagihan->paid_at ? $tagihan->paid_at->format('Y-m-d H:i') : now()->format('Y-m-d H:i');
        
        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('content.billing.receipt-pdf', compact('tagihan'))
            ->setOption('isRemoteEnabled', true);
        $pdfContent = $pdf->output();
        $filename = 'Nota-' . $pelanggan->kode_pelanggan . '-' . $tagihan->bulan . '-' . $tagihan->tahun . '.pdf';

        // Save to public storage for URL access (Manual save to avoid finfo error)
        try {
            $storagePath = 'bukti_bayar/' . $filename;
            $fullPath = storage_path('app/public/' . $storagePath);
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0755, true);
            }
            file_put_contents($fullPath, $pdfContent);
        } catch (\Exception $e) {
            Log::warning("WhatsappClient: Could not save copy of PDF to storage: " . $e->getMessage());
        }

        $monthIndo = [
            'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret', 
            'April' => 'April', 'May' => 'Mei', 'June' => 'Juni', 
            'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September', 
            'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
        ];
        $monthNameIndo = $monthIndo[$monthName] ?? $monthName;
        $amountK = round($tagihan->jumlah / 1000);

        $caption = "Terima kasih pelanggan *{$pelanggan->kode_pelanggan}* atas pembayaran tagihan internet periode *{$monthNameIndo}* sebesar *{$amountK} ribu*. Semoga segala urusan juga rezekinya senantiasa dimudahkan dan dilancarkan selalu. Aamiin";

        // Kirim dengan async=true agar bot langsung respond tanpa tunggu delivery WA
        // Ini mencegah timeout saat antrian pengiriman panjang
        return $this->sendFile($pelanggan->no_wa, $pdfContent, $filename, 'application/pdf', $caption, true);
    }

    public function getSessions()
    {
        try {
            $response = Http::timeout(5)->get($this->baseUrl . '/sessions');
            return $response->json();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function startSession($id)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/session/start', ['id' => $id]);
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getPairingCode($id, $phone)
    {
        try {
            $response = Http::timeout(35) // bot butuh ~18 detik
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/session/pairing', [
                    'id'    => $id,
                    'phone' => $phone
                ]);
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function stopSession($id)
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/session/stop', ['id' => $id]);
            return $response->json();
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function sendStatus($message, $mediaBase64 = null, $mimetype = 'image/jpeg', $caption = '', $statusJidList = [])
    {
        Log::info("WhatsappClient: Attempting to update WA Status");
        try {
            $payload = [
                'message' => $message,
                'statusJidList' => $statusJidList,
            ];
            
            if ($mediaBase64) {
                $payload['media'] = $mediaBase64;
                $payload['mimetype'] = $mimetype;
                $payload['caption'] = $caption ?: $message;
            }

            $response = Http::timeout(30)
                ->withHeaders(['X-Bot-Secret' => $this->secret()])
                ->post($this->baseUrl . '/send-status', $payload);

            if (!$response->successful()) {
                Log::error("WhatsappClient Status Error Response: " . $response->status() . " - " . $response->body());
                return false;
            }

            Log::info("WhatsappClient: WA Status updated successfully");
            return true;
        } catch (\Exception $e) {
            Log::error("WhatsappClient Status Connection Error: " . $e->getMessage());
            return false;
        }
    }
    public function shouldBlockMessage($phone)
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        if (empty($cleanPhone)) {
            return false;
        }

        // Try to match the customer in the database by their no_wa
        $customer = \App\Models\Pelanggan::where(function($q) use ($cleanPhone) {
            $q->where('no_wa', 'like', '%' . $cleanPhone . '%')
              ->orWhere('no_wa', 'like', '%' . substr($cleanPhone, 2) . '%');
        })->first();

        // Block if customer is deactivated manually (is_active is false and is_isolated is false)
        if ($customer && !$customer->is_active && !$customer->is_isolated) {
            Log::info("WhatsappClient: Blocking message to manually deactivated customer: {$customer->nama_pelanggan} ({$phone})");
            return true;
        }

        return false;
    }
}
