<?php

namespace App\Http\Controllers;

use App\Models\Pelanggan;
use App\Models\Tagihan;
use App\Services\MikrotikService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\WhatsappTraining;
use App\Models\BotResponse;
use App\Models\OdcOdp;
use Illuminate\Support\Str;


class WhatsappController extends Controller
{
    protected $mikrotik;

    public function __construct(MikrotikService $mikrotik)
    {
        $this->mikrotik = $mikrotik;
    }

    /**
     * Webhook for receiving messages from Baileys/Node.js Bot
     */
    public function webhook(Request $request)
    {
        Log::info('WhatsApp Webhook v3 (Group & Keyword Focus) - Time: ' . now());
        
        $remoteJid = $request->remoteJid;
        $isGroup = $request->isGroup ?? (str_contains($remoteJid, '@g.us'));
        $message = trim(strtolower($request->message ?? ''));
        $type = $request->type; // chat, image, etc.
        $sender = $request->sender; // e.g. 62812345678@s.whatsapp.net
        $phoneNumber = explode('@', $sender)[0];
        $cleanPhone = substr($phoneNumber, -10);

        // Check if customer is manually deactivated
        $cleanNumForCheck = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (!empty($cleanNumForCheck)) {
            $customer = \App\Models\Pelanggan::where('no_wa', 'like', '%' . $cleanNumForCheck . '%')
                ->orWhere('no_wa', 'like', '%' . substr($cleanNumForCheck, 2) . '%')
                ->first();
            if ($customer && !$customer->is_active && !$customer->is_isolated) {
                Log::info("WhatsappController Webhook: Ignoring message from manually deactivated customer: {$customer->nama_pelanggan} ({$phoneNumber})");
                return response()->json(['status' => 'ignored_manually_deactivated_customer']);
            }
        }

        // Auto-update BotResponse ID 6 response text to include the new instruction
        $laporResponse = \App\Models\BotResponse::find(6);
        if ($laporResponse && !str_contains($laporResponse->response, 'TROUBLE KODE_PELANGGAN')) {
            $laporResponse->update([
                'response' => "Mohon maaf atas ketidaknyamanannya kak 🙏\n\nSilakan coba langkah awal berikut:\n✅ Cek lampu modem — pastikan tidak ada lampu LOS yang merah.\n✅ Cabut adaptor modem dari listrik, tunggu 30–60 detik, lalu pasang kembali.\n✅ Tunggu hingga modem menyala normal (sekitar 2–3 menit).\n\nJika kendala internet masih sama silahkan ketik *TROUBLE KODE_PELANGGAN* (Contoh: *TROUBLE KTR01*) lalu share lokasi untuk penanganan Tim teknisi kami kak, terima kasih 😊"
            ]);
        }

        $lowerMsg = trim(strtolower($request->message ?? ''));

        // ── FLOW LOCATION SHARE RECEIVED ──
        if (str_starts_with($lowerMsg, 'lokasi_share:')) {
            $coords = str_replace('lokasi_share:', '', $lowerMsg);
            $parts = explode(',', $coords);
            if (count($parts) === 2) {
                $lat = trim($parts[0]);
                $lng = trim($parts[1]);
                
                // Check if this user has a pending trouble report in Cache
                $troubleData = Cache::get("trouble_report_{$sender}");
                
                if ($troubleData) {
                    // Update customer's latitude & longitude in database!
                    $customer = \App\Models\Pelanggan::find($troubleData['customer_id']);
                    if ($customer) {
                        $customer->update([
                            'latitude' => $lat,
                            'longitude' => $lng
                        ]);
                    }
                    
                    // Format beautiful forward message to WhatsApp Group
                    $groupJid = "120363154234417705@g.us";
                    
                    $msgGroup = "⚠️ *LAPORAN GANGGUAN PELANGGAN* ⚠️\n";
                    $msgGroup .= "--------------------------------------\n";
                    $msgGroup .= "Pelanggan : *{$troubleData['customer_name']}* ({$troubleData['customer_code']})\n";
                    $msgGroup .= "No. HP    : {$troubleData['phone']}\n";
                    $msgGroup .= "Status    : *GANGGUAN / OFFLINE*\n";
                    $msgGroup .= "Waktu     : " . now()->format('H:i:s d/m/Y') . "\n";
                    $msgGroup .= "--------------------------------------\n";
                    $msgGroup .= "🗺️ *Lokasi Google Maps:*\n";
                    $msgGroup .= "https://www.google.com/maps?q={$lat},{$lng}\n\n";
                    $msgGroup .= "Mohon Tim Teknisi segera merespon dan melakukan penanganan di lapangan. Terima kasih.";
                    
                    // Send to group!
                    try {
                        $waClient = new \App\Services\WhatsappClient();
                        $waClient->sendMessage($groupJid, ['text' => $msgGroup]);
                    } catch (\Exception $e) {
                        Log::error("Failed to forward trouble report to group: " . $e->getMessage());
                    }
                    
                    // Clear cache state
                    Cache::forget("trouble_report_{$sender}");
                    
                    $reply = "Terima kasih banyak Kak! 🙏\n\nLokasi Anda berhasil diverifikasi dan laporan gangguan Anda telah **diteruskan otomatis ke Tim Teknisi kami di Grup Perbaikan Internet**.\n\nPetugas kami akan segera meluncur ke lokasi Anda 🔧";
                    $this->saveBotReply($remoteJid, $reply);
                    return response()->json(['reply' => $reply]);
                }
            }
        }

        // ── FLOW GANGGUAN / TROUBLE REPORT ──
        if (preg_match('/^(masih\s+)?(trouble|troble|trobel|gangguan)\s+(.+)$/i', $lowerMsg, $matches)) {
            $customerCode = trim($matches[3]);
            
            // Check if customer exists in DB
            $customer = \App\Models\Pelanggan::where('kode_pelanggan', $customerCode)
                ->orWhere('id_pelanggan', $customerCode)
                ->orWhere('mikrotik_username', $customerCode)
                ->first();
                
            if ($customer) {
                // Store in Cache for 30 minutes
                Cache::put("trouble_report_{$sender}", [
                    'customer_id' => $customer->id_pelanggan,
                    'customer_code' => $customer->kode_pelanggan,
                    'customer_name' => $customer->nama_pelanggan,
                    'phone' => $phoneNumber,
                    'timestamp' => now()->toDateTimeString()
                ], 1800);
                
                $reply = "Baik Kak *{$customer->nama_pelanggan}*,\nLaporan gangguan Anda telah kami catat.\n\n📌 *Langkah terakhir:* Silakan **Share Lokasi (Bagikan Lokasi)** Anda sekarang di chat ini agar Tim Teknisi kami dapat langsung menuju ke lokasi Anda untuk penanganan.\n\nTerima kasih 🙏";
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            } else {
                $reply = "Maaf Kak, ID/Kode Pelanggan *{$customerCode}* tidak terdaftar di sistem kami.\n\nSilakan cek kembali kode Anda (Contoh: *TROUBLE KTR01*) atau ketik *menu* untuk bantuan.";
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            }
        }

        // 0. Handle Admin Training Command (!train)
        if ($this->handleTrainingCommand($request->message, $remoteJid, $request->pushName, true)) {
            return response()->json(['status' => 'trained']);
        }

        // 1. Logic for Ping/Cek/Lokasi (Hardcoded Commands)
        $lowerMsg = strtolower($message);
        
        // Cek PING/CEK (Kecuali 'cek tagihan')
        if ((str_starts_with($lowerMsg, 'ping') || str_starts_with($lowerMsg, 'cek ')) && !str_starts_with($lowerMsg, 'cek tagihan')) {
            $targetStr = trim(str_ireplace(['ping', 'cek id ', 'cek '], '', $message));
            if (!empty($targetStr) && strlen($targetStr) <= 25) {
                return $this->handlePingCommand($targetStr, $remoteJid, $cleanPhone);
            }
        }

        // Cek LOKASI (GIS)
        if (str_starts_with($lowerMsg, 'lokasi ')) {
            $query = trim(str_ireplace('lokasi ', '', $message));
            if (!empty($query)) return $this->handleLokasiCommand($query, $remoteJid, 'gis');
        }

        // Cek LOK (Manual URL)
        if (str_starts_with($lowerMsg, 'lok ')) {
            $query = trim(str_ireplace('lok ', '', $message));
            if (!empty($query)) return $this->handleLokasiCommand($query, $remoteJid, 'url');
        }

        // 2. Keyword Matching from Database (Primary Logic)
        $botResponse = $this->findKeywordResponse($message, $isGroup);
        if ($botResponse) {
            $finalReply = $botResponse->response;
            
            // Ganti Placeholder
            $finalReply = str_replace(
                ['{Nama Customer}', '{Nama}', '{PushName}', '{Nomor WA}'], 
                [$request->pushName ?? 'Pelanggan', $request->pushName ?? 'Pelanggan', $request->pushName ?? 'Pelanggan', $cleanPhone], 
                $finalReply
            );

            // Handle Menu Response
            if ($botResponse->is_menu) {
                return $this->showMenu($remoteJid, $botResponse->id);
            }

            // Handle Special Placeholders
            if (str_contains($finalReply, '{cek_tagihan}')) {
                $finalReply = $this->handleCekTagihanPlaceholder($finalReply, $message, $remoteJid);
            }

            $formatted = $this->formatForWhatsapp($finalReply);
            $this->saveBotReply($remoteJid, $formatted);
            return response()->json(['reply' => $formatted]);
        }

        // 3. Logic for Image (Proof of payment)
        $isImage = ($type == 'image' || $type == 'imageMessage' || $request->has('media'));
        if ($isImage && $request->has('media')) {
            return $this->handleImageVerification($request, $remoteJid, $message);
        }

        // 4. Fallback (AI or Default)
        $botNumber = env('WHATSAPP_BOT_NUMBER', '');
        $isMentioned = false;
        if ($request->has('mentionedJid')) {
            foreach ($request->mentionedJid as $jid) {
                if (str_contains($jid, $botNumber)) {
                    $isMentioned = true;
                    break;
                }
            }
        }

        // Jika di Grup: Hanya balas jika di-mention
        // Jika di Chat Pribadi: Selalu balas (AI atau Default)
        // 4. Fallback (AI or Default) - SEKARANG DIBUAT PASIF
        // Hanya membalas jika di-mention (di grup) atau jika pesan mengandung kata sapaan/bantuan
        $greetings = ['halo', 'hi', 'p', 'bot', 'help', 'menu', 'bantuan', 'tanya', 'siapa'];
        $isGreeting = in_array($lowerMsg, $greetings);

        if (($isGroup && $isMentioned) || (!$isGroup && $isGreeting)) {
            // AI Fallback (Hanya jika diaktifkan di .env)
            if (env('WHATSAPP_AI_ENABLED', true)) {
                $aiReply = $this->getAiResponse($message, $remoteJid, $request->pushName ?? 'Pelanggan');
                if ($aiReply) {
                    $this->saveBotReply($remoteJid, $aiReply);
                    return response()->json(['reply' => $aiReply]);
                }
            }

            // Default Fallback dari Database (Hanya jika diinginkan)
            if (env('WHATSAPP_DEFAULT_REPLY_ENABLED', false)) {
                $fallback = \App\Models\BotResponse::where('keyword', 'like', '%default%')->where('is_active', true)->first();
                $reply = $fallback ? $this->formatForWhatsapp($fallback->response) : "🤖 *RT RW NET BOT*\nKetik *menu* untuk melihat bantuan.";
                
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            }
        }

        // Jika tidak ada keyword yang cocok dan bukan mention/sapaan, diam saja (Silent Mode)
        return response()->json(['status' => 'ignored_no_match']);
    }

    /**
     * Helper to find response in DB
     */
    private function findKeywordResponse($message, $isGroup)
    {
        if (empty($message)) return null;

        $botResponses = \App\Models\BotResponse::where('is_active', true)
            ->where(function($q) use ($isGroup) {
                if ($isGroup) {
                    $q->where('group_enabled', true)->orWhere('keyword', 'like', '%default%');
                }
            })
            ->get();

        $bestMatch = null;
        $highestSimilarity = 0;

        foreach ($botResponses as $bot) {
            $keywords = array_filter(array_map('trim', explode(',', strtolower($bot->keyword))));
            
            foreach ($keywords as $kw) {
                // 1. Exact Match
                if ($message === $kw) return $bot;

                // 2. Exact Match in exact match mode
                if ($bot->is_exact_match && $message === $kw) return $bot;

                if (!$bot->is_exact_match) {
                    // 3. Word Boundary Match (Better for matching words in sentences)
                    if (strlen($kw) >= 3) {
                        if (preg_match('/\b' . preg_quote($kw, '/') . '\b/i', $message)) return $bot;
                    }
                    
                    // 4. Case-insensitive Exact Match for short keywords
                    if (strlen($kw) < 3 && $message === $kw) return $bot;

                    // 5. Fuzzy Match (Levenshtein) - Only for short messages to avoid false positives
                    if (strlen($message) < 20 && strlen($kw) > 3) {
                        $sim = 0;
                        similar_text($message, $kw, $sim);
                        if ($sim > 85 && $sim > $highestSimilarity) {
                            $highestSimilarity = $sim;
                            $bestMatch = $bot;
                        }
                    }
                }
            }
        }
        return $bestMatch;
    }

    private function handlePingCommand($targetStr, $remoteJid, $cleanPhone)
    {
        Log::info("PING_DEBUG: TargetStr='$targetStr', Message='ping'");

        $targetPelanggan = Pelanggan::where(function($q) use ($targetStr) {
                                        $q->where('kode_pelanggan', '=', $targetStr)
                                          ->orWhere('mikrotik_username', '=', $targetStr)
                                          ->orWhere('ip_address', '=', $targetStr);
                                        if (is_numeric($targetStr)) $q->orWhere('id_pelanggan', '=', $targetStr);
                                    })->first();
        
        if ($targetPelanggan && $targetPelanggan->id_router) {
            $router = $targetPelanggan->router;
        } else {
            $router = \App\Models\Router::first();
        }

        $host = $targetStr;
        $pelangganInfo = "";
        
        if ($targetPelanggan && $router) {
            $mUser = $targetPelanggan->mikrotik_username ?: $targetPelanggan->kode_pelanggan;
            
            // 1. Try to get IP from Mikrotik Active List
            $activeIp = $this->mikrotik->getPelangganActiveIp($router, $mUser, $targetPelanggan->mikrotik_type);
            
            if ($activeIp && $activeIp !== 'ROUTER_OFFLINE') {
                $host = $activeIp;
            } 
            // 2. Fallback for Static IP or Offline PPPOE (using DB IP)
            elseif (!empty($targetPelanggan->ip_address)) {
                $host = $targetPelanggan->ip_address;
            }

            // Pelanggan Info for the message
            $pelangganInfo = "Pelanggan: " . $targetPelanggan->nama_pelanggan . " (" . $targetPelanggan->kode_pelanggan . ")\n";
            if (!empty($targetPelanggan->latitude) && !empty($targetPelanggan->longitude)) {
                $pelangganInfo .= "📍 Maps: https://www.google.com/maps?q={$targetPelanggan->latitude},{$targetPelanggan->longitude}\n";
            }
        }
        
        try {
            $results = null;
            if ($router) {
                $results = $this->mikrotik->ping($router, $host);
            }
            
            if ($results && is_array($results)) {
                $received = 0;
                $avgTime = 0;
                $times = [];

                foreach ($results as $res) {
                    // Check for various ways Mikrotik indicates success
                    $status = strtolower($res['status'] ?? '');
                    $hasTime = isset($res['time']);
                    
                    if ($status === 'ok' || str_contains($status, 'received') || $hasTime) {
                        $received++;
                        if ($hasTime) {
                            // Extract numeric value from time (e.g. "15ms" -> 15)
                            $timeVal = (int) preg_replace('/[^0-9]/', '', $res['time']);
                            if ($timeVal > 0) $times[] = $timeVal;
                        }
                    }
                }

                $isSuccess = ($received > 0);
                $avgTime = !empty($times) ? round(array_sum($times) / count($times)) : 0;

                $reply = "📍 *Hasil Ping (Mikrotik)*\n--------------------------\n";
                if ($pelangganInfo) $reply .= $pelangganInfo;
                $reply .= "Target: $targetStr ($host)\n";
                
                if ($targetPelanggan) {
                    $hasUnpaid = DB::table('tagihan')->where('id_pelanggan', $targetPelanggan->id_pelanggan)->whereIn('status', ['unpaid', 'belum_bayar'])->exists();
                    if ($targetPelanggan->is_active == 0 || $hasUnpaid) {
                        $reply .= "Status: 🔴 *TERISOLIR (BELUM BAYAR)*\n";
                    } else {
                        if ($isSuccess) {
                            $reply .= "Status: ✅ *ONLINE*\n";
                            $reply .= "Latency: {$avgTime}ms\n";
                            $reply .= "Packets: {$received}/4 Received\n";
                        } else {
                            $reply .= "Status: ❌ *OFFLINE (Request Timeout)*\n";
                        }
                    }
                    Cache::put('last_check_' . $remoteJid, $targetPelanggan->id_pelanggan, 3600);
                } else {
                    if ($isSuccess) {
                        $reply .= "Status: ✅ *REACHABLE*\n";
                        $reply .= "Latency: {$avgTime}ms\n";
                    } else {
                        $reply .= "Status: ❌ *UNREACHABLE*\n";
                    }
                }

                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            }
        } catch (\Exception $e) {
            Log::error("Ping Error: " . $e->getMessage());
        }

        // --- DATABASE FALLBACK JIKA KONEKSI ROUTER GAGAL ATAU PING TIDAK MERESPON ---
        if ($targetPelanggan) {
            $hasUnpaid = DB::table('tagihan')->where('id_pelanggan', $targetPelanggan->id_pelanggan)->whereIn('status', ['unpaid', 'belum_bayar'])->exists();
            
            $reply = "📍 *Hasil Ping (Mikrotik - Fallback)*\n--------------------------\n";
            if ($pelangganInfo) $reply .= $pelangganInfo;
            $reply .= "Target: $targetStr ($host)\n";
            
            if ($targetPelanggan->is_active == 0 || $hasUnpaid) {
                $reply .= "Status: 🔴 *TERISOLIR (BELUM BAYAR)*\n";
                $reply .= "\n⚠️ _Catatan: Koneksi ke Router MikroTik sedang offline atau terganggu, namun status penagihan Anda saat ini terdeteksi belum bayar (Isolir)._";
            } else {
                $reply .= "Status: 🟡 *DATABASE ACTIVE (ROUTER OFFLINE)*\n";
                $reply .= "\n⚠️ _Catatan: Koneksi ke Router MikroTik sedang offline atau terganggu, sehingga kami tidak dapat mengukur ping/koneksi modem Anda saat ini. Namun, status administrasi Anda aktif._";
            }
            
            $this->saveBotReply($remoteJid, $reply);
            return response()->json(['reply' => $reply]);
        }

        return response()->json(['reply' => "⚠️ Maaf Kak, target \"$targetStr\" ($host) tidak merespon.\n\nHal ini bisa disebabkan karena:\n1. Perangkat pelanggan mati/cabut power.\n2. Kabel dropcore putus.\n3. Router sedang sibuk.\n\nSilakan coba lagi beberapa saat lagi."]);
    }

    private function handleLokasiCommand($query, $remoteJid, $mode = 'gis')
    {
        $originalQuery = $query;
        $query = strtolower(trim($query));
        $typeFilter = null;

        // Deteksi jika user mengetik "odp [nama]" atau "odc [nama]"
        if (str_starts_with($query, 'odp ')) {
            $typeFilter = 'ODP';
            $query = trim(substr($query, 4));
        } elseif (str_starts_with($query, 'odc ')) {
            $typeFilter = 'ODC';
            $query = trim(substr($query, 4));
        }

        // 1. Prioritaskan pencarian EXACT pada Kode Pelanggan (Hanya jika tidak ada filter ODC/ODP)
        if (!$typeFilter) {
            $exactPelanggan = Pelanggan::where('kode_pelanggan', $query)
                ->orWhere('kode_pelanggan', strtoupper($query))
                ->first();

            if ($exactPelanggan) {
                $p = $exactPelanggan;
                if ($mode === 'url' && !empty($p->maps_url)) {
                    $mapsUrl = $p->maps_url;
                    $reply = "📍 *Link Google Maps Pelanggan*\n\nNama: *{$p->nama_pelanggan}*\nKode: *{$p->kode_pelanggan}*\nAlamat: {$p->alamat}\n\n🗺️ *Link Google Maps:*\n$mapsUrl";
                } else {
                    $mapsUrl = "https://www.google.com/maps?q={$p->latitude},{$p->longitude}";
                    $reply = "📍 *Lokasi Pelanggan (GIS)*\n\nNama: *{$p->nama_pelanggan}*\nKode: *{$p->kode_pelanggan}*\nAlamat: {$p->alamat}\n\n🗺️ *Google Maps (Koordinat):*\n$mapsUrl";
                }
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            }
        }

        // 2. Pencarian EXACT pada ODC/ODP
        $queryOdc = OdcOdp::query();
        if ($typeFilter) $queryOdc->where('tipe', $typeFilter);
        
        $exactOdc = $queryOdc->where(function($q) use ($query) {
            $q->where('nama', $query)
              ->orWhere('nama', strtoupper($query))
              ->orWhere('nama', 'like', "%-$query") // ODP-AD-20
              ->orWhere('nama', str_replace(' ', '-', $query)) // odp-ad20 -> odp-ad-20
              ->orWhere('nama', str_replace(' ', '', $query));
        })->first();

        if ($exactOdc) {
            $item = $exactOdc;
            $mapsUrl = "https://www.google.com/maps?q={$item->latitude},{$item->longitude}";
            $reply = "🏗️ *Lokasi Infrastruktur ({$item->tipe})*\n\nNama: *{$item->nama}*\nTipe: *{$item->tipe}*\nDeskripsi: {$item->deskripsi}\n\n🗺️ *Google Maps:*\n$mapsUrl";
            $this->saveBotReply($remoteJid, $reply);
            return response()->json(['reply' => $reply]);
        }

        // 3. Pencarian Partial
        $resPelanggan = collect();
        if (!$typeFilter) {
            $resPelanggan = Pelanggan::where('nama_pelanggan', 'like', "%$query%")
                ->orWhere('kode_pelanggan', 'like', "%$query%")
                ->limit(5)->get();
        }

        $queryPartialOdc = OdcOdp::query();
        if ($typeFilter) $queryPartialOdc->where('tipe', $typeFilter);
        $resOdc = $queryPartialOdc->where('nama', 'like', "%$query%")->limit(5)->get();

        if ($resPelanggan->isEmpty() && $resOdc->isEmpty()) {
            $reply = "❌ Tidak ada pelanggan atau infrastruktur yang cocok dengan *$originalQuery*.";
            $this->saveBotReply($remoteJid, $reply);
            return response()->json(['reply' => $reply]);
        }

        // Jika hanya 1 hasil total
        if ($resPelanggan->count() + $resOdc->count() === 1) {
            if ($resPelanggan->isNotEmpty()) {
                $p = $resPelanggan->first();
                if ($mode === 'url' && !empty($p->maps_url)) {
                    $mapsUrl = $p->maps_url;
                    $reply = "📍 *Link Google Maps Pelanggan*\n\nNama: *{$p->nama_pelanggan}*\nKode: *{$p->kode_pelanggan}*\n\n🗺️ *Link Google Maps:*\n$mapsUrl";
                } else {
                    $mapsUrl = "https://www.google.com/maps?q={$p->latitude},{$p->longitude}";
                    $reply = "📍 *Lokasi Pelanggan*\n\nNama: *{$p->nama_pelanggan}*\nKode: *{$p->kode_pelanggan}*\n\n🗺️ *Google Maps (Koordinat):*\n$mapsUrl";
                }
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            } else {
                $item = $resOdc->first();
                $mapsUrl = "https://www.google.com/maps?q={$item->latitude},{$item->longitude}";
                $reply = "🏗️ *Lokasi Infrastruktur ({$item->tipe})*\n\nNama: *{$item->nama}*\n\n🗺️ *Google Maps:*\n$mapsUrl";
                $this->saveBotReply($remoteJid, $reply);
                return response()->json(['reply' => $reply]);
            }
        }

        // Jika banyak hasil, tampilkan list
        $reply = "🔍 *Hasil Pencarian: \"$originalQuery\"*\n\n";
        if ($resPelanggan->isNotEmpty()) {
            $reply .= "*--- Pelanggan ---*\n";
            foreach ($resPelanggan as $p) {
                $hasLok = ($p->latitude && $p->longitude) ? '✅' : '❌';
                $reply .= "• *{$p->nama_pelanggan}* ({$p->kode_pelanggan}) $hasLok\n";
            }
            $reply .= "\n";
        }
        if ($resOdc->isNotEmpty()) {
            $reply .= "*--- Infrastruktur ---*\n";
            foreach ($resOdc as $item) {
                $reply .= "• [{$item->tipe}] *{$item->nama}* ✅\n";
            }
        }
        $reply .= "\n_Ketik nama yang lebih spesifik jika ingin langsung mendapatkan link lokasi._";
        
        $this->saveBotReply($remoteJid, $reply);
        return response()->json(['reply' => $reply]);
    }

    private function handleImageVerification($request, $remoteJid, $message)
    {
        $lastCheckedId = Cache::get('last_check_' . $remoteJid);
        $tagihan = null;
        if ($lastCheckedId) $tagihan = Tagihan::where('id_pelanggan', $lastCheckedId)->whereIn('status', ['unpaid', 'belum_bayar'])->latest()->first();

        if (!$tagihan) {
            $ocrText = strtolower($request->ocrText ?? '');
            $receiptKeywords = ['transfer', 'jumlah', 'rp', 'berhasil', 'sukses', 'bank', 'bri', 'dana'];
            $looksLikeReceipt = false;
            foreach ($receiptKeywords as $kw) {
                if (str_contains($ocrText, $kw)) {
                    $looksLikeReceipt = true;
                    break;
                }
            }

            if (!$looksLikeReceipt) {
                return response()->json(['status' => 'ignored_not_a_receipt']);
            }

            return response()->json(['reply' => "Maaf Kak, saya tidak menemukan tagihan aktif untuk Anda. Ketik *cek tagihan [KODE]* dulu ya sebelum kirim bukti bayar!"]);
        }

        $imageData = base64_decode($request->media);
        $fileName = 'bukti_' . $tagihan->id_tagihan . '_' . time() . '.jpg';
        if (!file_exists(storage_path('app/public/bukti_bayar'))) mkdir(storage_path('app/public/bukti_bayar'), 0777, true);
        file_put_contents(storage_path('app/public/bukti_bayar/' . $fileName), $imageData);
        $tagihan->update(['bukti_bayar' => 'bukti_bayar/' . $fileName]);

        // ──────────────────────────────────────────────────────────────────────
        // OCR: Deteksi apakah gambar adalah STRUK TRANSFER BANK / E-WALLET
        // ──────────────────────────────────────────────────────────────────────
        $ocrText = strtolower($request->ocrText ?? '');
        $cleanOcr = preg_replace('/[^0-9]/', '', $ocrText);
        $targetAmount = (int)$tagihan->jumlah;

        // Keyword khas struk transfer bank (tidak umum muncul di chat WA biasa)
        $bankReceiptKeywords = [
            'rekening tujuan', 'no rekening', 'rekening sumber', 'no rek',
            'no transaksi', 'id transaksi', 'ref', 'referensi', 'kode transaksi',
            'tanggal transaksi', 'tgl transaksi', 'waktu transaksi',
            'transfer berhasil', 'transaksi berhasil', 'pembayaran berhasil',
            'berhasil', 'debit', 'kredit', 'm-transfer', 'sumber akun',
        ];

        // Nama bank & e-wallet resmi Indonesia (LENGKAP termasuk Jago, Neobank, dll)
        $bankNames = [
            // Bank Konvensional
            'bri', 'bca', 'mandiri', 'bni', 'bsi', 'cimb', 'btn', 'danamon',
            'permata', 'maybank', 'ocbc', 'panin', 'mega', 'bukopin', 'btpn',
            // Neobank / Digital Bank
            'jago', 'jenius', 'blu', 'motion', 'seabank', 'superbank',
            'neo bank', 'neobank', 'allo bank', 'allobank',
            // E-Wallet
            'dana', 'ovo', 'gopay', 'shopeepay', 'linkaja', 'sakuku',
            'livin', 'brimo', 'flip', 'mybukalapak',
        ];

        $receiptKeywordCount = 0;
        foreach ($bankReceiptKeywords as $kw) {
            if (str_contains($ocrText, $kw)) {
                $receiptKeywordCount++;
            }
        }

        $hasBankName = false;
        $detectedBank = null;
        foreach ($bankNames as $bank) {
            if (str_contains($ocrText, $bank)) {
                $hasBankName = true;
                $detectedBank = strtoupper($bank);
                break;
            }
        }

        // ── Pengecekan Nominal ──────────────────────────────────────────────
        // Skenario 1 (Normal): Pelanggan transfer TEPAT sesuai tagihan
        //   → cek apakah nominal tagihan ada di teks OCR
        // Skenario 2 (Gabungan): Satu transfer untuk beberapa pelanggan
        //   → transfer amount bisa lebih besar dari tagihan (mis: 200.000 untuk BA4+BC39)
        //   → cek apakah ADA angka di struk yang >= nominal tagihan
        // ──────────────────────────────────────────────────────────────────────
        $amountMatched = false;
        if ($targetAmount >= 1000) {
            $targetStr = (string)$targetAmount;

            // Skenario 1: nominal exact match
            if (str_contains($cleanOcr, $targetStr)) {
                $amountMatched = true;
            } else {
                // Skenario 2: cari semua angka >= 4 digit di struk,
                // lolos jika ada yang >= tagihan (berarti transfer mencakup tagihan ini)
                preg_match_all('/\d{4,}/', $cleanOcr, $numMatches);
                foreach ($numMatches[0] as $num) {
                    if ((int)$num >= $targetAmount) {
                        $amountMatched = true;
                        break;
                    }
                }
            }
        }

        $isValidReceipt = $amountMatched && $hasBankName && ($receiptKeywordCount >= 1);

        \Log::info("OCR Verifikasi Tagihan #{$tagihan->id_tagihan}: amountMatched=" . ($amountMatched ? 'true' : 'false') .
                   ", hasBankName=" . ($hasBankName ? 'true' : 'false') .
                   ", receiptKeywordCount=$receiptKeywordCount" .
                   ", targetAmount=$targetAmount" .
                   ", ocrLength=" . strlen($ocrText));

        if ($isValidReceipt) {
            $paymentMethod = 'Transfer ' . ($detectedBank ?: 'Bank');
            $tagihan->update(['status' => 'paid', 'paid_at' => now(), 'metode_pembayaran' => $paymentMethod]);
            
            // Log the activity
            try {
                \App\Helpers\ActivityLogger::log(
                    'Sistem (WhatsApp Bot) berhasil memverifikasi otomatis tagihan #' . $tagihan->id_tagihan . ' (' . ($tagihan->pelanggan ? $tagihan->pelanggan->nama_pelanggan : 'Umum') . ') sebesar Rp ' . number_format($tagihan->jumlah, 0, ',', '.'),
                    'tagihan',
                    'WhatsApp Bot',
                    'System'
                );
            } catch (\Exception $e) {
                \Log::error("Gagal mencatat log aktivitas verifikasi otomatis WhatsApp: " . $e->getMessage());
            }
            
            // Auto Re-Enable Layanan (Un-Isolir)
            $pelanggan = $tagihan->pelanggan;
            if ($pelanggan && $pelanggan->id_router) {
                try {
                    $mikrotik = app(\App\Services\MikrotikService::class);
                    $mUser = $pelanggan->mikrotik_username ?: $pelanggan->kode_pelanggan;
                    $success = $mikrotik->setSecretStatus($pelanggan->router, $mUser, $pelanggan->mikrotik_type, false, $pelanggan->ip_address);
                    if ($success) {
                        $pelanggan->update(['is_active' => true]);
                    }
                } catch (\Exception $e) {
                    \Log::error("Gagal un-isolir otomatis: " . $e->getMessage());
                }
            }

            // Simpan tagihan ID untuk dipakai di closure (hindari capture object besar)
            $tagihanId = $tagihan->id_tagihan;

            // Kirim nota PDF SETELAH response dikirim ke bot agar tidak terjadi
            // circular timeout (bot nunggu Laravel → Laravel nunggu bot → deadlock)
            app()->terminating(function () use ($tagihanId) {
                try {
                    $t = \App\Models\Tagihan::find($tagihanId);
                    if ($t) {
                        $waClient = new \App\Services\WhatsappClient();
                        $waClient->sendReceipt($t);
                    }
                } catch (\Exception $e) {
                    \Log::error("Failed to send receipt (afterResponse): " . $e->getMessage());
                }
            });

            return response()->json(['reply' => "✅ VERIFIKASI OTOMATIS BERHASIL!\n\nTerima kasih, pembayaran sebesar Rp " . number_format($tagihan->jumlah, 0, ',', '.') . " telah kami terima. Layanan Anda kini sudah aktif kembali.\n\nNota digital sedang dikirim ke nomor ini."]);
        }

        return response()->json(['reply' => "⚠️ Bukti transfer telah kami terima. Namun, sistem kami perlu melakukan pengecekan manual untuk memastikan validitasnya. Mohon tunggu sebentar nggih Kak, admin kami akan segera mengonfirmasi."]);
    }

    private function handleCekTagihanPlaceholder($finalReply, $message, $remoteJid)
    {
        // Ekstrak kode pelanggan, lalu uppercase agar cocok dengan DB (A75, bukan a75)
        $customerCode = strtoupper(trim(str_ireplace('cek tagihan', '', $message)));
        $customer = null;

        if ($customerCode) {
            // Gunakan UPPER() di DB agar pencarian case-insensitive (A75 = a75 = A75)
            $customer = \App\Models\Pelanggan::whereRaw('UPPER(kode_pelanggan) = ?', [$customerCode])
                ->orWhereRaw('UPPER(mikrotik_username) = ?', [$customerCode])
                ->first();
        } else {
            // Bersihkan nomor WA pengirim untuk pencarian otomatis
            $phoneStr = explode('@', $remoteJid)[0];
            $cleanNum = preg_replace('/[^0-9]/', '', $phoneStr);
            
            if (!empty($cleanNum)) {
                // Cari pelanggan dengan nomor WA yang cocok (baik dengan 62, 0, atau potongan kode negara)
                $customer = \App\Models\Pelanggan::where('no_wa', 'like', '%' . $cleanNum . '%')
                    ->orWhere('no_wa', 'like', '%' . substr($cleanNum, 2) . '%')
                    ->first();
            }
        }

        if ($customer) {
            Cache::put('last_check_' . $remoteJid, $customer->id_pelanggan, 3600);
            $bills = $customer->tagihan()->where('status', '!=', 'paid')->get();
            if ($bills->count() > 0) {
                $info = "Halo " . $customer->nama_pelanggan . ",\n\n*Tagihan Anda:*\n";
                $total = 0;
                foreach ($bills as $bill) {
                    $info .= "• " . $bill->bulan . " " . $bill->tahun . ": Rp " . number_format($bill->jumlah, 0, ',', '.') . "\n";
                    $total += $bill->jumlah;
                }
                $info .= "\n*Total: Rp " . number_format($total, 0, ',', '.') . "*\n\nKirim bukti bayar kesini ya!";
                return str_replace('{cek_tagihan}', $info, $finalReply);
            }
            return str_replace('{cek_tagihan}', "Lunas! Anda tidak memiliki tagihan aktif.", $finalReply);
        }

        if ($customerCode) {
            return str_replace('{cek_tagihan}', "Kode *$customerCode* tidak ditemukan.", $finalReply);
        }

        return str_replace('{cek_tagihan}', "Ketik *cek tagihan [KODE]*\nContoh: *cek tagihan AD20*", $finalReply);
    }

    public function train(Request $request)
    {
        $messages = $request->messages;
        if (!$messages || !is_array($messages)) {
            return response()->json(['success' => false, 'message' => 'No messages provided']);
        }

        $successCount = 0;
        foreach ($messages as $m) {
            try {
                // Normalize keys (handle both camelCase and snake_case)
                $remoteJid = $m['remote_jid'] ?? $m['remoteJid'] ?? null;

                /* Simpan pesan grup untuk training data AI context */
                // if ($remoteJid && str_contains($remoteJid, '@g.us')) {
                //     continue;
                // }

                $messageContent = $m['message'] ?? null;
                $isFromMe = $m['is_from_me'] ?? $m['isFromMe'] ?? false;
                $pushName = $m['pushName'] ?? $m['sender_name'] ?? 'Unknown';

                if (!$remoteJid || !$messageContent) continue;

                // LOG UNTUK DEBUG
                \Log::info("Training Data Incoming: From $pushName - " . substr($messageContent, 0, 20) . "...");

                // Pastikan timestamp bukan array/objek yang tersisa
                $timestamp = $m['timestamp'] ?? time();
                if (is_array($timestamp) || is_object($timestamp)) {
                    $timestamp = time(); 
                }

                $client = new \App\Services\WhatsappClient();

                // === FITUR PELATIHAN AI (KHUSUS ADMIN) ===
                if ($this->handleTrainingCommand($messageContent, (string)$remoteJid, (string)$pushName, false)) {
                    continue;
                }

                \App\Models\WhatsappTraining::updateOrCreate(
                    [
                        'remote_jid' => (string)$remoteJid,
                        'timestamp' => \Carbon\Carbon::createFromTimestamp((int)$timestamp)->toDateTimeString(),
                        'message' => (string)$messageContent
                    ],
                    [
                        'type' => (string)($m['type'] ?? 'chat'),
                        'is_from_me' => (bool)$isFromMe,
                        'sender_name' => (string)$pushName,
                    ]
                );
                $successCount++;
            } catch (\Exception $e) {
                \Log::error("Gagal simpan training: " . $e->getMessage());
                continue;
            }
        }

        return response()->json(['success' => true, 'count' => $successCount]);
    }

    private function getAiResponse($message, $remoteJid, $pushName = 'Pelanggan')
    {
        $apiKey = env('OPENROUTER_API_KEY');
        $envModel = env('OPENROUTER_MODEL', 'google/gemini-2.0-flash-lite-preview-02-05:free');
        $models = array_unique(array_merge([$envModel], [
            'google/gemini-2.0-flash-exp:free',
            'qwen/qwen-2.5-72b-instruct:free',
            'deepseek/deepseek-chat:free',
            'meta-llama/llama-3.1-8b-instruct:free'
        ]));

        if (!$apiKey) return null;

        // Load SOP Knowledge
        $sopPath = storage_path('app/sop_knowledge.txt');
        $sopContent = file_exists($sopPath) ? file_get_contents($sopPath) : 'Bantu pelanggan Rozitech dengan ramah.';

        // Load Dynamic Data (Bot Responses from DB)
        $dynamicBotData = BotResponse::where('is_active', true)->get()->map(function($item) {
            return "[Keyword: {$item->keyword}] JAWABAN: {$item->response}";
        })->implode("\n");

        // Load Context from Training Data (History)
        $history = WhatsappTraining::where('remote_jid', $remoteJid)
            ->orderBy('timestamp', 'desc')
            ->limit(10)
            ->get()
            ->reverse();

        // Load Dynamic Network Stats (REALTIME from DB)
        $pelangganCount = Pelanggan::count();
        $odcCount = OdcOdp::where('tipe', 'ODC')->count();
        $odpCount = OdcOdp::where('tipe', 'ODP')->count();
        $networkSummary = "DATA JARINGAN REALTIME:\n- Total Pelanggan: $pelangganCount\n- Total ODC: $odcCount\n- Total ODP: $odpCount\n\nJika pelanggan tanya lokasi, sarankan ketik 'lok [nama]' atau 'lokasi [nama]'.";

        $adminNum = env('WHATSAPP_ADMIN_NUMBER');
        $systemInstruction = "Anda adalah R-Care, Customer Service AI resmi dari Rozitech (https://rozitech.co.id).
        Tugas Anda adalah melayani pelanggan Rozitech Network (Layanan Internet/WiFi).

        PENTING:
        - FOKUS HANYA PADA LAYANAN INTERNET ROZITECH.
        - JANGAN menyebutkan produk lain seperti HP, Laptop, atau barang elektronik.
        - Jika pelanggan bertanya tentang 'tombol' atau 'mana menu-nya', jelaskan bahwa menu muncul sebagai pilihan tombol/daftar di layar chat. Jika tidak muncul, minta mereka mengetik nomor pilihan yang tertera pada pesan menu.
        - JANGAN pernah mengira 'tombol' adalah tombol fisik pada modem kecuali pelanggan sedang dalam proses troubleshooting perangkat.
        
        KEPRIBADIAN:
        - Ramah, hangat, dan profesional.
        - Deteksi bahasa pelanggan (Indo atau Jawa). Balas sesuai bahasa mereka.

        KONTEKS BISNIS & SOP:
        $sopContent

        DATA JARINGAN REALTIME:
        $networkSummary

        PENGETAHUAN TAMBAHAN (Database):
        $dynamicBotData

        ATURAN PENTING (WAJIB):
        - Gunakan List Bullet (•) atau penomoran manual (1, 2, 3).
        - *Tebalkan* harga, nomor rekening, dan info krusial.
        - Akhiri dengan Link Admin: https://wa.me/$adminNum dan Watermark _Respons by R-Care AI_";

        $messages = [['role' => 'system', 'content' => $systemInstruction]];

        foreach ($history as $h) {
            $messages[] = [
                'role' => $h->is_from_me ? 'assistant' : 'user',
                'content' => $h->message
            ];
        }

        // Add current message if not already in history
        $messages[] = ['role' => 'user', 'content' => $message];

        // Try models one by one
        foreach ($models as $model) {
            try {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'HTTP-Referer' => 'https://rozitech.co.id',
                    'X-Title' => 'Rozitech R-Care Bot',
                ])->timeout(60)->post('https://openrouter.ai/api/v1/chat/completions', [
                    'model' => $model,
                    'messages' => $messages,
                    'temperature' => 0.7,
                ]);

                if ($response->successful()) {
                    $result = $response->json();
                    $content = $result['choices'][0]['message']['content'] ?? null;
                    if ($content) {
                        Log::info("AI Response success via OpenRouter ($model) for: " . substr($message, 0, 50));
                        return $content;
                    }
                } else {
                    Log::error("OpenRouter Error ($model): " . $response->body());
                    continue; 
                }
            } catch (\Exception $e) {
                Log::error("AI Error ($model): " . $e->getMessage());
                continue; 
            }
        }

        // === JALUR PENYELAMAT: POLLINATIONS AI (GET - RINGKAS) ===
        Log::warning("OpenRouter Gagal. Mencoba Pollinations GET...");
        try {
            $encodedPrompt = urlencode($message);
            $encodedSystem = urlencode(substr($systemInstruction, 0, 500)); // Batasi panjang instruksi
            $url = "https://text.pollinations.ai/{$encodedPrompt}?system={$encodedSystem}&model=openai";
            
            $response = Http::timeout(30)->get($url);

            if ($response->successful()) {
                $reply = $response->body();
                
                // --- PROSES PEMBERSIHAN WATERMARK/IKLAN ---
                if ($reply) {
                    // Hapus baris yang mengandung kata-kata iklan
                    $lines = explode("\n", $reply);
                    $cleanLines = array_filter($lines, function($line) {
                        $trimmed = trim($line);
                        $lowered = strtolower($trimmed);
                        return !str_contains($lowered, 'pollinations.ai') && 
                               !str_contains($lowered, '*ad*') && 
                               !str_contains($lowered, 'powered by') &&
                               !str_contains($lowered, 'support pollination') &&
                               $trimmed !== '---' && 
                               $trimmed !== '--' &&
                               $trimmed !== '';
                    });
                    $reply = trim(implode("\n", $cleanLines));
                }

                if ($reply && strlen($reply) > 5) {
                    return $reply;
                }
            }
        } catch (\Exception $e) {
            Log::error("Pollinations GET Error: " . $e->getMessage());
        }

        Log::warning("Semua jalur AI gagal untuk: " . $message);
        return null;
    }

    /**
     * Handle bot status updates (connected, disconnected, etc.)
     */
    public function status(Request $request)
    {
        Log::info('WhatsApp Status Update:', $request->all());
        
        $sessionId = $request->sessionId;
        $status    = $request->status;
        $user      = $request->user;

        // You can store this in DB or Cache to show in UI
        Cache::put("whatsapp_session_{$sessionId}_status", [
            'status' => $status,
            'user'   => $user,
            'last_update' => now()->toDateTimeString()
        ], 86400);

        return response()->json(['success' => true]);
    }

    /**
     * Save bot's own reply to history for AI context synchronization
     */
    private function saveBotReply($remoteJid, $message)
    {
        try {
            \App\Models\WhatsappTraining::create([
                'remote_jid' => (string)$remoteJid,
                'message' => (string)$message,
                'timestamp' => now()->toDateTimeString(),
                'type' => 'chat',
                'is_from_me' => true,
                'sender_name' => 'R-Care AI'
            ]);
        } catch (\Exception $e) {
            \Log::error("Failed to save bot reply to history: " . $e->getMessage());
        }
    }

    /**
     * Handle !train command from admin
     */
    private function handleTrainingCommand($message, $sender, $pushName, $shouldReply = true)
    {
        $incomingMsg = trim($message);
        if (preg_match('/^!train\s+/i', $incomingMsg)) {
            // DYNAMIC ADMIN CHECK
            $adminNumbers = explode(',', env('WHATSAPP_ADMIN_NUMBER'));
            $adminIds = explode(',', env('WHATSAPP_ADMIN_IDS', '117849758691352,251749826822171'));
            $adminNames = explode(',', strtolower(env('WHATSAPP_ADMIN_NAMES', 'rozitech,kang digital,admin')));
            
            $senderClean = preg_replace('/[^0-9]/', '', $sender);
            $pushNameLower = strtolower($pushName);
            
            $isAdmin = false;
            
            // Check by Number
            foreach ($adminNumbers as $num) {
                if (str_contains($senderClean, trim($num))) {
                    $isAdmin = true;
                    break;
                }
            }
            
            // Check by ID
            if (!$isAdmin) {
                foreach ($adminIds as $id) {
                    if (str_contains($sender, trim($id))) {
                        $isAdmin = true;
                        break;
                    }
                }
            }
            
            // Check by Name
            if (!$isAdmin) {
                foreach ($adminNames as $name) {
                    if (str_contains($pushNameLower, trim($name))) {
                        $isAdmin = true;
                        break;
                    }
                }
            }

            if ($isAdmin) {
                $content = preg_replace('/^!train\s+/i', '', $incomingMsg);
                $parts = explode('|', $content, 2);
                
                if (count($parts) == 2) {
                    $keyword = trim($parts[0]);
                    $response = trim($parts[1]);
                    
                    // Store to DB (AI uses this as knowledge)
                    \App\Models\BotResponse::updateOrCreate(
                        ['keyword' => $keyword],
                        ['response' => $response, 'is_active' => true, 'is_exact_match' => true]
                    );
                    
                    // Send confirmation message
                    if ($shouldReply) {
                        try {
                            $client = new \App\Services\WhatsappClient();
                            $client->sendMessage($sender, [
                                'text' => "*Berhasil Melatih R-Care (Secara Dinamis)!* ✅\n\n*Keyword:* $keyword\n*Jawaban:* [Tersimpan]\n\nData sudah aktif di Database & AI nggih Kak."
                            ]);
                        } catch (\Exception $e) {
                            Log::error("Failed to send training confirmation: " . $e->getMessage());
                        }
                    }
                    
                    return true;
                }
            } else {
                Log::warning("Unauthorized training attempt from: $sender (Name: $pushName). If this is you, please add your ID/Name to .env");
            }
        }
        return false;
    }

    /**
     * Tampilkan menu secara dinamis (IM3 Style - List Message)
     */
    private function showMenu($remoteJid, $parentId = null)
    {
        $query = BotResponse::where('is_active', true);
        
        if ($parentId) {
            $query->where('parent_id', $parentId);
            $parent = BotResponse::find($parentId);
            $title = strtoupper($parent->keyword);
            $text = $parent->response;
        } else {
            $query->whereNull('parent_id')->where('is_menu', true);
            $title = "MENU UTAMA R-CARE";
            $text = "Selamat datang di layanan R-Care AI. Silakan pilih menu di bawah ini untuk memulai.";
        }

        $menus = $query->orderBy('sort_order')->get();

        if ($menus->isEmpty()) {
            return response()->json(['reply' => "Maaf, menu belum tersedia."]);
        }

        // Decide whether to use List or Buttons
        if ($menus->count() > 0 && $menus->count() <= 3) {
            $textFallback = $text . "\n\n*PILIHAN MENU:*\n";
            $buttons = [];
            foreach ($menus as $menu) {
                $label = strtoupper($menu->menu_label ?: explode(',', $menu->keyword)[0]);
                $textFallback .= "👉 Ketik *" . explode(',', $menu->keyword)[0] . "* untuk " . $label . "\n";
                
                $buttons[] = [
                    'buttonId' => explode(',', $menu->keyword)[0],
                    'buttonText' => ['displayText' => $label],
                    'type' => 1
                ];
            }

            $buttonMessage = [
                'text' => $textFallback . "\n_Silakan pilih tombol di bawah ini (atau ketik angkanya):_",
                'footer' => "R-Care AI • Rozitech",
                'buttons' => $buttons,
                'headerType' => 1
            ];

            $this->saveBotReply($remoteJid, "Menampilkan Menu: " . $title . "\n" . $textFallback);
            return response()->json($buttonMessage);
        } else {
            // Build List Message for Baileys (More than 3 items)
            $textFallback = $text . "\n\n*PILIHAN MENU:*\n";
            $rows = [];
            foreach ($menus as $menu) {
                $label = strtoupper($menu->menu_label ?: explode(',', $menu->keyword)[0]);
                $textFallback .= "👉 Ketik *" . explode(',', $menu->keyword)[0] . "* untuk " . $label . "\n";
                
                $rows[] = [
                    'title' => $label,
                    'rowId' => explode(',', $menu->keyword)[0],
                    'description' => Str::limit(strip_tags($menu->response), 50)
                ];
            }

            if ($parentId) {
                $rows[] = [
                    'title' => 'KEMBALI KE MENU UTAMA',
                    'rowId' => 'menu',
                    'description' => 'Lihat layanan utama kami'
                ];
            }

            $listMessage = [
                'text' => $textFallback . "\n_Silakan pilih menu di bawah ini (atau ketik pilihannya):_",
                'footer' => "R-Care AI • Rozitech",
                'title' => "📍 " . $title,
                'buttonText' => "Klik Pilih Menu",
                'sections' => [
                    [
                        'title' => "Silakan Pilih",
                        'rows' => $rows
                    ]
                ]
            ];

            $this->saveBotReply($remoteJid, "Menampilkan List Menu: " . $title . "\n" . $textFallback);
            return response()->json($listMessage);
        }
    }

    /**
     * Convert Markdown-like tables and common formatting to WhatsApp-friendly text
     */
    private function formatForWhatsapp($text)
    {
        if (empty($text)) return $text;

        // 1. Convert Markdown Tables to Lists
        // Matches | cell | cell | ... |
        $lines = explode("\n", $text);
        $formattedLines = [];
        $inTable = false;
        $tableData = [];

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (preg_match('/^\|.*\|$/', $trimmedLine)) {
                // Table header separator |---|---|
                if (preg_match('/^[|:\-\s]+$/', $trimmedLine)) {
                    continue;
                }
                
                $inTable = true;
                $cells = array_map('trim', explode('|', trim($trimmedLine, '|')));
                $tableData[] = $cells;
            } else {
                if ($inTable) {
                    // Process collected table data
                    foreach ($tableData as $rowIndex => $row) {
                        if ($rowIndex === 0) {
                            // Header
                            $formattedLines[] = "*" . implode(" | ", $row) . "*";
                        } else {
                            // Row
                            $formattedLines[] = "• " . implode(": ", $row);
                        }
                    }
                    $formattedLines[] = ""; // Empty line after table
                    $tableData = [];
                    $inTable = false;
                }
                $formattedLines[] = $line;
            }
        }

        // Finalize table if text ends with one
        if ($inTable) {
            foreach ($tableData as $rowIndex => $row) {
                if ($rowIndex === 0) {
                    $formattedLines[] = "*" . implode(" | ", $row) . "*";
                } else {
                    $formattedLines[] = "• " . implode(": ", $row);
                }
            }
        }

        $result = implode("\n", $formattedLines);

        // 2. Fix excessive newlines
        $result = preg_replace("/\n{3,}/", "\n\n", $result);

        return trim($result);
    }
}
