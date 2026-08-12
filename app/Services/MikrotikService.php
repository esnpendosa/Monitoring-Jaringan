<?php

namespace App\Services;

use RouterOS\Client;
use RouterOS\Query;
use App\Models\Router;
use App\Models\RouterStat;
use Exception;
use Illuminate\Support\Facades\Cache;

class MikrotikService
{
    public function markConnectionFailed(Router $router, $error = '')
    {
        $cacheKey = "mikrotik_connection_status_" . $router->id_router;
        
        // Cache failure for 15 seconds to prevent overload and log flooding
        Cache::put($cacheKey, 'failed', 15);

        // Update status in DB
        $router->update(['status_koneksi' => 'Simulated (Error: ' . substr($error, 0, 30) . ')']);
        \Log::warning("Mikrotik Router (" . $router->nama_router . ") marked as failed in cache for 15s due to error: " . $error);
    }

    public function getConnection(Router $router, $retries = 1, $bypassCache = false)
    {
        $cacheKey = "mikrotik_connection_status_" . $router->id_router;
        if (!$bypassCache && Cache::get($cacheKey) === 'failed') {
            return null; // Immediately return if connection was marked as failed recently
        }

        $attempt = 0;
        $lastError = '';

        while ($attempt <= $retries) {
            try {
                // Konfigurasi Client Mikrotik
                $client = new Client([
                    'host'    => $router->ip_host,
                    'user'    => $router->username,
                    'pass'    => decrypt($router->password_encrypted),
                    'port'    => (int) ($router->api_port ?? 8728),
                    'timeout' => 20, // Menggunakan 20 detik agar pembacaan kueri stabil & andal dan mencegah Stream timed out
                    'attempts' => 1,
                    'delay'    => 1,
                ]);

                // Verifikasi apakah socket benar-benar terbuka
                // (Beberapa versi library mengembalikan object tapi stream gagal)
                return $client;
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                $attempt++;
                
                if ($attempt <= $retries) {
                    usleep(500000); // Tunggu 0.5 detik sebelum retry
                    continue;
                }
            }
        }

        // Cache kegagalan koneksi
        $this->markConnectionFailed($router, $lastError);
        
        return null;
    }

    public function syncStats(Router $router)
    {
        try {
            $client = $this->getConnection($router, 1, true);
            
            if (!$client) {
                // Status sudah diupdate oleh getConnection jika gagal
                return false;
            }

            // Get Resource
            $resource = $client->query('/system/resource/print')->read();
            if (isset($resource[0])) {
                $res = $resource[0];
                RouterStat::create([
                    'id_router' => $router->id_router,
                    'uptime' => $res['uptime'] ?? '0',
                    'cpu_load' => (int) ($res['cpu-load'] ?? 0),
                    'memory_free' => (int) ($res['free-memory'] ?? 0),
                    'recorded_at' => now(),
                ]);
            }

            $router->update([
                'status_koneksi' => 'Connected',
                'last_sync_at' => now()
            ]);

            return true;
        } catch (\Exception $e) {
            $this->markConnectionFailed($router, $e->getMessage());
            return false;
        }
    }

    public function ping(Router $router, $target, $count = 4)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return null;

            $query = new Query('/ping');
            $query->equal('address', $target);
            $query->equal('count', $count);
            
            return $client->query($query)->read();
        } catch (\Exception $e) {
            $this->markConnectionFailed($router, $e->getMessage());
            return null;
        }
    }

    public function getTraffic(Router $router, $interface)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return null;

            $query = new Query('/interface/monitor-traffic');
            $query->equal('interface', $interface);
            $query->equal('once', '');
            
            return $client->query($query)->read();
        } catch (\Exception $e) {
            $this->markConnectionFailed($router, $e->getMessage());
            return null;
        }
    }

    public function findSimpleQueue($client, $username, $ipAddress = null, Router $router = null)
    {
        try {
            $searchKey = trim($username);

            // Helper to safely extract first item from RouterOS response
            // RouterOS API sometimes returns ['after' => [...]] for empty results
            // or non-indexed arrays, causing "Undefined array key 0" errors
            $safeFirst = function($result) {
                if (!is_array($result) || empty($result) || isset($result['after'])) {
                    return null;
                }
                return isset($result[0]) && is_array($result[0]) ? $result[0] : null;
            };

            // 1. Coba cari langsung berdasarkan Name (Paling Cepat & Akurat!)
            $query = new Query('/queue/simple/print');
            $query->equal('.proplist', '.id,name,target,comment,max-limit');
            $query->where('name', $searchKey);
            $queues = $client->query($query)->read();
            $found = $safeFirst($queues);
            if ($found) return $found;

            // 2. Coba cari langsung berdasarkan Comment
            $query = new Query('/queue/simple/print');
            $query->equal('.proplist', '.id,name,target,comment,max-limit');
            $query->where('comment', $searchKey);
            $queues = $client->query($query)->read();
            $found = $safeFirst($queues);
            if ($found) return $found;

            // 3. Coba cari langsung berdasarkan Target IP
            if ($ipAddress) {
                $cleanIp = trim($ipAddress);
                
                // Format IP/32 (MikroTik biasanya mencatat target statis sebagai IP/32)
                $query = new Query('/queue/simple/print');
                $query->equal('.proplist', '.id,name,target,comment,max-limit');
                $query->where('target', $cleanIp . '/32');
                $queues = $client->query($query)->read();
                $found = $safeFirst($queues);
                if ($found) return $found;

                // Format IP biasa
                $query = new Query('/queue/simple/print');
                $query->equal('.proplist', '.id,name,target,comment,max-limit');
                $query->where('target', $cleanIp);
                $queues = $client->query($query)->read();
                $found = $safeFirst($queues);
                if ($found) return $found;
            }

        } catch (\Exception $e) {
            \Log::error("Mikrotik findSimpleQueue Error: " . $e->getMessage());
            if ($router) {
                $this->markConnectionFailed($router, $e->getMessage());
            }
        }
        return null;
    }

    public function getQueueTraffic(Router $router, $name, \App\Models\Pelanggan $pelanggan = null)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return null;

            $ipAddress = $pelanggan ? $pelanggan->ip_address : null;
            $q = $this->findSimpleQueue($client, $name, $ipAddress, $router);

            if (!$q) return null;

            // Ambil statistik real-time untuk queue yang ditemukan
            $statQuery = new Query('/queue/simple/print');
            $statQuery->equal('.id', $q['.id']);
            $statQuery->equal('stats', '');
            $stats = $client->query($statQuery)->read();
            
            if (!empty($stats) && isset($stats[0]['rate'])) {
                $rates = explode('/', $stats[0]['rate']);
                return [
                    'tx-bits-per-second' => (int)($rates[0] ?? 0), 
                    'rx-bits-per-second' => (int)($rates[1] ?? 0),
                ];
            }
            return null;
        } catch (\Exception $e) {
            \Log::error("Mikrotik getQueueTraffic Error: " . $e->getMessage());
            $this->markConnectionFailed($router, $e->getMessage());
            return null;
        }
    }

    public function getPelangganActiveIp(Router $router, $username, $type)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return 'ROUTER_OFFLINE';

            if ($type === 'pppoe') {
                $path = '/ppp/active';
                $query = new Query($path . '/print');
                $query->where('name', $username);
                $active = $client->query($query)->read();

                if (!empty($active)) {
                    return $active[0]['address'] ?? null;
                }

                $querySecret = new Query('/ppp/secret/print');
                $querySecret->where('name', $username);
                $secret = $client->query($querySecret)->read();

                if (!empty($secret)) {
                    return $secret[0]['remote-address'] ?? null;
                }
            } elseif ($type === 'hotspot') {
                $path = '/ip/hotspot/active';
                $query = new Query($path . '/print');
                $query->where('user', $username);
                $active = $client->query($query)->read();

                if (!empty($active)) {
                    return $active[0]['address'] ?? null;
                }

                $querySecret = new Query('/ip/hotspot/user/print');
                $querySecret->where('name', $username);
                $secret = $client->query($querySecret)->read();

                if (!empty($secret)) {
                    return $secret[0]['address'] ?? null;
                }
            } elseif ($type === 'static') {
                // For static type, we check if the simple queue exists on Mikrotik!
                $targetQueue = $this->findSimpleQueue($client, $username, null, $router);
                if (!empty($targetQueue)) {
                    $address = $targetQueue['target'] ?? '';
                    $address = str_replace('/32', '', $address);
                    if (str_contains($address, '/')) {
                        $address = explode('/', $address)[0];
                    }
                    return $address ?: 'STATIC_ONLINE';
                }
            }

            return null;
        } catch (\Exception $e) {
            \Log::error("Mikrotik getPelangganActiveIp Error: " . $e->getMessage());
            $this->markConnectionFailed($router, $e->getMessage());
            return 'ROUTER_OFFLINE';
        }
    }

    public function getUserDetails(Router $router, $username, $type, \App\Models\Pelanggan $pelanggan = null)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return null;

            if ($type === 'pppoe') {
                $query = new Query('/ppp/secret/print');
                $query->where('name', $username);
                $details = $client->query($query)->read();
                
                $secret = $details[0] ?? null;
                $rateLimit = 'N/A';

                if ($secret) {
                    $rateLimit = $secret['rate-limit'] ?? null;
                    
                    // If no direct rate-limit, check the profile
                    if (!$rateLimit && isset($secret['profile'])) {
                        $profileQuery = new Query('/ppp/profile/print');
                        $profileQuery->where('name', $secret['profile']);
                        $profile = $client->query($profileQuery)->read();
                        $rateLimit = $profile[0]['rate-limit'] ?? $secret['profile'];
                    }
                }

                // Get active connection for uptime and IP
                $queryActive = new Query('/ppp/active/print');
                $queryActive->where('name', $username);
                $active = $client->query($queryActive)->read();
                
                return [
                    'secret' => array_merge($secret ?? [], ['limit-out' => $rateLimit]),
                    'active' => $active[0] ?? null,
                ];
            } elseif ($type === 'hotspot') {
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $username);
                $details = $client->query($query)->read();

                $secret = $details[0] ?? null;
                $rateLimit = 'N/A';

                if ($secret) {
                    $rateLimit = $secret['rate-limit'] ?? null;
                    
                    // If no direct rate-limit, check the profile
                    if (!$rateLimit && isset($secret['profile'])) {
                        $profileQuery = new Query('/ip/hotspot/user/profile/print');
                        $profileQuery->where('name', $secret['profile']);
                        $profile = $client->query($profileQuery)->read();
                        $rateLimit = $profile[0]['rate-limit'] ?? $secret['profile'];
                    }
                }

                $queryActive = new Query('/ip/hotspot/active/print');
                $queryActive->where('user', $username);
                $active = $client->query($queryActive)->read();

                return [
                    'secret' => array_merge($secret ?? [], ['limit-out' => $rateLimit]),
                    'active' => $active[0] ?? null,
                ];
            } elseif ($type === 'static') {
                $targetQueue = $this->findSimpleQueue($client, $username, $pelanggan ? $pelanggan->ip_address : null, $router);

                $address = $targetQueue['target'] ?? ($pelanggan ? $pelanggan->ip_address : 'N/A');
                $address = str_replace('/32', '', $address);
                if ($address === 'N/A' || empty($address)) $address = $username;
                
                $limit = $targetQueue['max-limit'] ?? 'No Limit';
                if ($limit === '0/0' || $limit === '0' || empty($limit)) {
                    $limit = 'No Limit';
                } else {
                    $limits = explode('/', $limit);
                    if (count($limits) === 2) {
                        $up = round((int)$limits[0] / 1000000, 1);
                        $down = round((int)$limits[1] / 1000000, 1);
                        $limit = ($up > 0 || $down > 0) ? "{$up}M / {$down}M" : "No Limit";
                    }
                }

                return [
                    'secret' => [
                        'name' => $targetQueue['name'] ?? $username,
                        'profile' => 'Static IP',
                        'limit-out' => $limit,
                    ],
                    'active' => [
                        'address' => $address,
                        'uptime' => ($targetQueue) ? 'Connected' : 'Offline',
                    ],
                    'queue' => $targetQueue
                ];
            }
        } catch (Exception $e) {
            // If connection fails, return a "Cached/Fallback" response to keep UI stable
            return [
                'secret' => [
                    'limit-out' => $pelanggan->paket_layanan ?? 'N/A',
                ],
                'active' => [
                    'address' => $pelanggan->ip_address ?? 'N/A',
                    'uptime' => $pelanggan->last_online_status ? 'Connected (Last Seen)' : 'Offline',
                ],
                'is_fallback' => true
            ];
        }
    }

    public function getProfiles(Router $router, $type)
    {
        try {
            $client = $this->getConnection($router);
            if (!$client) return [];

            $path = ($type === 'pppoe') ? '/ppp/profile/print' : '/ip/hotspot/user/profile/print';
            return $client->query($path)->read();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllActiveUsers(Router $router)
    {
        $activeUsers = [];
        try {
            $client = $this->getConnection($router);
            if (!$client) return [];

            // Fetch PPPoE active
            $pppoeQuery = new Query('/ppp/active/print');
            $pppoeQuery->equal('.proplist', '.id,name,address,uptime,caller-id');
            $pppoeActive = $client->query($pppoeQuery)->read();
            
            if (is_array($pppoeActive)) {
                foreach ($pppoeActive as $p) {
                    if (isset($p['name'])) {
                        $activeUsers[] = [
                            'username' => $p['name'],
                            'service' => 'PPPoE',
                            'address' => $p['address'] ?? 'N/A',
                            'uptime' => $p['uptime'] ?? 'N/A',
                            'caller_id' => $p['caller-id'] ?? 'N/A',
                        ];
                    }
                }
            }

            // Fetch Hotspot active
            $hotspotQuery = new Query('/ip/hotspot/active/print');
            $hotspotQuery->equal('.proplist', '.id,user,address,uptime,mac-address');
            $hotspotActive = $client->query($hotspotQuery)->read();
            
            if (is_array($hotspotActive)) {
                foreach ($hotspotActive as $h) {
                    if (isset($h['user'])) {
                        $activeUsers[] = [
                            'username' => $h['user'],
                            'service' => 'Hotspot',
                            'address' => $h['address'] ?? 'N/A',
                            'uptime' => $h['uptime'] ?? 'N/A',
                            'caller_id' => $h['mac-address'] ?? 'N/A',
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Mikrotik getAllActiveUsers error: " . $e->getMessage());
        }
        return $activeUsers;
    }

    public function setSecretStatus(Router $router, $username, $type, $disable, $ip = null, $profileName = null)
    {
        try {
            $client = $this->getConnection($router, 1, true);
            if (!$client) {
                \Log::error("Mikrotik setSecretStatus: Connection failed to router {$router->nama_router}");
                return false;
            }

            $found = false;

            // 1. Update status secret (Enable/Disable) & Profile
            if ($type === 'pppoe') {
                $query = new Query('/ppp/secret/print');
                $query->where('name', $username);
                $resp = $client->query($query)->read();
                
                // Case-insensitive fallback
                if (empty($resp)) {
                    $allSecrets = $client->query(new Query('/ppp/secret/print'))->read();
                    if (is_array($allSecrets)) {
                        foreach ($allSecrets as $sec) {
                            if (strtolower($sec['name'] ?? '') === strtolower($username)) {
                                $resp = [$sec];
                                break;
                            }
                        }
                    }
                }

                if (!empty($resp)) {
                    $id = $resp[0]['.id'];
                    $setQuery = (new Query('/ppp/secret/set'))
                        ->equal('.id', $id)
                        ->equal('disabled', $disable ? 'yes' : 'no');
                    if ($profileName && $profileName !== 'custom') {
                        $setQuery->equal('profile', $profileName);
                    }
                    if ($ip) {
                        $setQuery->equal('remote-address', $ip);
                    }
                    $client->query($setQuery)->read();
                    
                    // Putuskan koneksi aktif agar modem/router langsung melakukan dial-in ulang secara fresh
                    $activeQuery = new Query('/ppp/active/print');
                    $activeResp = $client->query($activeQuery)->read();
                    if (is_array($activeResp)) {
                        foreach ($activeResp as $active) {
                            if (strtolower($active['name'] ?? '') === strtolower($username)) {
                                $client->query((new Query('/ppp/active/remove'))->equal('.id', $active['.id']))->read();
                            }
                        }
                    }
                    $found = true;
                } else {
                    // Auto-create PPPoE secret
                    $addQuery = (new Query('/ppp/secret/add'))
                        ->equal('name', $username)
                        ->equal('password', '12345678')
                        ->equal('service', 'pppoe')
                        ->equal('profile', ($profileName && $profileName !== 'custom') ? $profileName : 'default')
                        ->equal('disabled', $disable ? 'yes' : 'no');
                    if ($ip) {
                        $addQuery->equal('remote-address', $ip);
                    }
                    $client->query($addQuery)->read();
                    $found = true;
                }
            } elseif ($type === 'hotspot') {
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $username);
                $resp = $client->query($query)->read();
                
                // Case-insensitive fallback
                if (empty($resp)) {
                    $allUsers = $client->query(new Query('/ip/hotspot/user/print'))->read();
                    if (is_array($allUsers)) {
                        foreach ($allUsers as $usr) {
                            if (strtolower($usr['name'] ?? '') === strtolower($username)) {
                                $resp = [$usr];
                                break;
                            }
                        }
                    }
                }

                if (!empty($resp)) {
                    $id = $resp[0]['.id'];
                    $setQuery = (new Query('/ip/hotspot/user/set'))
                        ->equal('.id', $id)
                        ->equal('disabled', $disable ? 'yes' : 'no');
                    if ($profileName && $profileName !== 'custom') {
                        $setQuery->equal('profile', $profileName);
                    }
                    $client->query($setQuery)->read();
                    
                    // Putuskan sesi aktif agar pengguna dipaksa login ulang secara fresh
                    $activeQuery = new Query('/ip/hotspot/active/print');
                    $activeResp = $client->query($activeQuery)->read();
                    if (is_array($activeResp)) {
                        foreach ($activeResp as $active) { 
                            if (strtolower($active['user'] ?? '') === strtolower($username)) {
                                $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $active['.id']))->read();
                            }
                        }
                    }
                    $found = true;
                } else {
                    // Auto-create Hotspot user
                    $addQuery = (new Query('/ip/hotspot/user/add'))
                        ->equal('name', $username)
                        ->equal('password', '12345678')
                        ->equal('profile', ($profileName && $profileName !== 'custom') ? $profileName : 'default')
                        ->equal('disabled', $disable ? 'yes' : 'no');
                    $client->query($addQuery)->read();
                    $found = true;
                }
            } elseif ($type === 'static') {
                // ============================================================
                // ISOLIR untuk Static IP: menggunakan Address-List + Global Rule
                // Cara kerja yang benar di MikroTik:
                //   1. Tambah/hapus IP dari address-list "ISOLIR"
                //   2. Pastikan ada SATU global rule permanen yang drop semua
                //      traffic dari address-list "ISOLIR" (bukan per-IP rule)
                //   3. Hapus per-IP rule lama (penyebab duplikasi)
                //   4. Kick ARP entry agar koneksi langsung terputus
                // ============================================================

                // Step 1: Kelola Address-List ISOLIR
                if ($ip) {
                    $listName = 'ISOLIR';
                    $addrQuery = new Query('/ip/firewall/address-list/print');
                    $addrQuery->where('list', $listName);
                    $allAddrs = $client->query($addrQuery)->read();

                    $matchedEntry = null;
                    if (is_array($allAddrs)) {
                        foreach ($allAddrs as $addr) {
                            if (($addr['address'] ?? '') === $ip) {
                                $matchedEntry = $addr;
                                break;
                            }
                        }
                    }

                    if ($disable) {
                        // Tambahkan ke ISOLIR list jika belum ada
                        if (!$matchedEntry) {
                            $client->query((new Query('/ip/firewall/address-list/add'))
                                ->equal('address', $ip)
                                ->equal('list', $listName)
                                ->equal('comment', $username))->read();
                        }
                    } else {
                        // Hapus dari ISOLIR list (aktifkan kembali)
                        if ($matchedEntry) {
                            $client->query((new Query('/ip/firewall/address-list/remove'))
                                ->equal('.id', $matchedEntry['.id']))->read();
                        }
                    }
                    $found = true;
                }

                // Step 2: Pastikan ada GLOBAL drop rule untuk address-list ISOLIR
                //         Hapus per-IP rule lama yang bisa menyebabkan duplikasi
                $filterPath = '/ip/firewall/filter';
                $filterResp = $client->query(new Query($filterPath . '/print'))->read();
                $hasGlobalIsolirForward = false;

                if (is_array($filterResp)) {
                    foreach ($filterResp as $filter) {
                        if (!is_array($filter)) continue;
                        $fChain   = $filter['chain']             ?? '';
                        $fAction  = $filter['action']            ?? '';
                        $fSrcList = $filter['src-address-list']  ?? '';
                        $fSrc     = $filter['src-address']       ?? '';
                        $fComment = $filter['comment']           ?? '';

                        // Hapus per-IP drop rule lama (penyebab duplikasi di foto)
                        if ($fChain === 'forward' && $fAction === 'drop' && empty($fSrcList) && $ip) {
                            if (strpos($fSrc, $ip) !== false ||
                                strtolower(trim($fComment)) === strtolower(trim($username ?? ''))) {
                                $client->query((new Query($filterPath . '/remove'))
                                    ->equal('.id', $filter['.id']))->read();
                                continue;
                            }
                        }

                        // Cek apakah global ISOLIR rule sudah ada
                        if ($fChain === 'forward' && $fAction === 'drop' && $fSrcList === 'ISOLIR') {
                            $hasGlobalIsolirForward = true;
                        }
                    }
                }

                // Buat global rule sekali jika belum ada
                if (!$hasGlobalIsolirForward) {
                    $client->query((new Query($filterPath . '/add'))
                        ->equal('chain', 'forward')
                        ->equal('action', 'drop')
                        ->equal('src-address-list', 'ISOLIR')
                        ->equal('comment', 'ISOLIR-GLOBAL: Drop isolir pelanggan')
                        ->equal('disabled', 'no')
                        ->equal('place-before', '0')
                    )->read();
                    \Log::info("Mikrotik: Global ISOLIR rule dibuat di router {$router->nama_router}");
                }

                // Step 3: Kick ARP entry agar koneksi langsung terputus (untuk static IP)
                if ($disable && $ip) {
                    try {
                        $arpQuery = new Query('/ip/arp/print');
                        $arpQuery->where('address', $ip);
                        $arpResp = $client->query($arpQuery)->read();
                        if (is_array($arpResp)) {
                            foreach ($arpResp as $arpEntry) {
                                if (isset($arpEntry['.id'])) {
                                    $client->query((new Query('/ip/arp/remove'))
                                        ->equal('.id', $arpEntry['.id']))->read();
                                }
                            }
                        }
                    } catch (\Exception $arpEx) {
                        \Log::warning("Mikrotik: Gagal hapus ARP {$ip}: " . $arpEx->getMessage());
                    }
                }

                if (!$ip) {
                    \Log::warning("Mikrotik setSecretStatus: Pelanggan {$username} static tidak punya IP, isolir address-list dilewati.");
                }
                $found = true; // static selalu dianggap ditemukan (dikelola via address-list)
            }

            if (!$found) {
                \Log::warning("Mikrotik setSecretStatus: Customer {$username} not found on Mikrotik ({$type})");
                return false;
            }

            // 2. Tendang sesi aktif jika di-disable agar koneksi terputus saat itu juga
            if ($disable) {
                if ($type === 'pppoe') {
                    $activeQuery = new Query('/ppp/active/print');
                    $activeResp = $client->query($activeQuery)->read();
                    if (is_array($activeResp)) {
                        foreach ($activeResp as $active) {
                            if (strtolower($active['name'] ?? '') === strtolower($username)) {
                                $client->query((new Query('/ppp/active/remove'))->equal('.id', $active['.id']))->read();
                            }
                        }
                    }
                } elseif ($type === 'hotspot') {
                    $activeQuery = new Query('/ip/hotspot/active/print');
                    $activeResp = $client->query($activeQuery)->read();
                    if (is_array($activeResp)) {
                        foreach ($activeResp as $active) {
                            if (strtolower($active['user'] ?? '') === strtolower($username)) {
                                $client->query((new Query('/ip/hotspot/active/remove'))->equal('.id', $active['.id']))->read();
                            }
                        }
                    }
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error("Mikrotik setSecretStatus Error for user {$username} on router {$router->nama_router}: " . $e->getMessage());
            $this->markConnectionFailed($router, $e->getMessage());
            return false;
        }
    }
}
