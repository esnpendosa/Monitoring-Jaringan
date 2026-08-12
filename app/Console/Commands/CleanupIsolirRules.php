<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Router;
use App\Models\Pelanggan;
use RouterOS\Client;
use RouterOS\Query;

class CleanupIsolirRules extends Command
{
    protected $signature = 'mikrotik:cleanup-isolir-rules {--router= : ID router spesifik (opsional)}';
    protected $description = 'Bersihkan per-IP isolir rule lama di MikroTik dan ganti dengan 1 global rule berbasis address-list ISOLIR';

    public function handle()
    {
        $routers = Router::all();
        if ($this->option('router')) {
            $routers = Router::where('id_router', $this->option('router'))->get();
        }

        if ($routers->isEmpty()) {
            $this->error('Tidak ada router ditemukan.');
            return;
        }

        // Ambil semua IP pelanggan yang sedang diisolir
        $isolatedPelanggan = Pelanggan::where('is_isolated', true)
            ->whereNotNull('ip_address')
            ->get(['kode_pelanggan', 'nama_pelanggan', 'ip_address', 'mikrotik_username'])
            ->keyBy('ip_address');

        foreach ($routers as $router) {
            $this->info("\n=== Router: {$router->nama_router} ({$router->ip_host}) ===");

            try {
                $client = new Client([
                    'host'    => $router->ip_host,
                    'user'    => $router->username,
                    'pass'    => decrypt($router->password_encrypted),
                    'port'    => (int) ($router->api_port ?? 8728),
                    'timeout' => 20,
                    'attempts' => 1,
                    'delay'    => 1,
                ]);

                // ============================================================
                // Step 1: Bersihkan per-IP firewall rule lama (chain=forward, action=drop, src-address=IP)
                // ============================================================
                $filterPath = '/ip/firewall/filter';
                $filterResp = $client->query(new Query($filterPath . '/print'))->read();

                $removedCount  = 0;
                $hasGlobalRule = false;

                if (is_array($filterResp)) {
                    foreach ($filterResp as $filter) {
                        if (!is_array($filter)) continue;

                        $fChain   = $filter['chain']            ?? '';
                        $fAction  = $filter['action']           ?? '';
                        $fSrcList = $filter['src-address-list'] ?? '';
                        $fSrc     = $filter['src-address']      ?? '';
                        $fComment = $filter['comment']          ?? '';

                        // Deteksi global ISOLIR rule yang sudah benar
                        if ($fChain === 'forward' && $fAction === 'drop' && $fSrcList === 'ISOLIR') {
                            $this->line("  [✓] Global ISOLIR rule sudah ada (ID: {$filter['.id']})");
                            $hasGlobalRule = true;
                            continue;
                        }

                        // Hapus per-IP drop rule lama yang dibuat oleh sistem sebelumnya
                        if ($fChain === 'forward' && $fAction === 'drop' && empty($fSrcList) && !empty($fSrc)) {
                            // Hanya hapus jika IP-nya cocok dengan pelanggan yang ada di sistem
                            $cleanSrc = str_replace('/32', '', $fSrc);
                            if ($isolatedPelanggan->has($cleanSrc) || $isolatedPelanggan->has($fSrc)) {
                                $this->warn("  [→] Hapus per-IP rule lama: comment='{$fComment}' src={$fSrc}");
                                $client->query((new Query($filterPath . '/remove'))
                                    ->equal('.id', $filter['.id']))->read();
                                $removedCount++;
                            }
                        }
                    }
                }

                $this->info("  Per-IP rule lama dihapus: {$removedCount} rule(s)");

                // ============================================================
                // Step 2: Buat global ISOLIR drop rule jika belum ada
                // ============================================================
                if (!$hasGlobalRule) {
                    $client->query((new Query($filterPath . '/add'))
                        ->equal('chain', 'forward')
                        ->equal('action', 'drop')
                        ->equal('src-address-list', 'ISOLIR')
                        ->equal('comment', 'ISOLIR-GLOBAL: Drop isolir pelanggan')
                        ->equal('disabled', 'no')
                        ->equal('place-before', '0')
                    )->read();
                    $this->info("  [✓] Global ISOLIR drop rule dibuat.");
                }

                // ============================================================
                // Step 3: Sinkronkan address-list ISOLIR sesuai database
                // ============================================================
                $addrQuery = new Query('/ip/firewall/address-list/print');
                $addrQuery->where('list', 'ISOLIR');
                $existingAddrs = $client->query($addrQuery)->read();
                $existingIPs = collect($existingAddrs)->pluck('address')->map(fn($a) => str_replace('/32', '', $a))->toArray();

                $this->info("\n  Sinkronisasi Address-List ISOLIR:");
                $this->line("  - Di router : " . implode(', ', $existingIPs ?: ['(kosong)']));
                $this->line("  - Di database: " . implode(', ', $isolatedPelanggan->keys()->toArray() ?: ['(kosong)']));

                // Tambahkan IP yang ada di DB tapi belum di router
                foreach ($isolatedPelanggan as $pelIp => $pel) {
                    if (!in_array($pelIp, $existingIPs)) {
                        $uname = $pel->mikrotik_username ?: $pel->kode_pelanggan;
                        $client->query((new Query('/ip/firewall/address-list/add'))
                            ->equal('address', $pelIp)
                            ->equal('list', 'ISOLIR')
                            ->equal('comment', $uname))->read();
                        $this->warn("  [+] Ditambahkan ke ISOLIR list: {$pelIp} ({$pel->nama_pelanggan})");
                    }
                }

                $this->info("  [✓] Sinkronisasi selesai.");

            } catch (\Exception $e) {
                $this->error("  Gagal koneksi ke router {$router->nama_router}: " . $e->getMessage());
            }
        }

        $this->info("\n✅ Cleanup selesai. Isolir sekarang menggunakan 1 global rule berbasis address-list ISOLIR.");
        $this->info("   Pastikan di MikroTik sudah ada rule: chain=forward, action=drop, src-address-list=ISOLIR");
    }
}
