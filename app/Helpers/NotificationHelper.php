<?php

namespace App\Helpers;

use App\Models\Notification;
use App\Models\User;

class NotificationHelper
{
    /**
     * Kirim notifikasi ke user tertentu.
     *
     * @param int    $userId
     * @param string $type       Contoh: tiket_baru, tagihan_lunas, upgrade_paket
     * @param string $title
     * @param string $body
     * @param array  $options    ['icon', 'color', 'action_url']
     */
    public static function send(int $userId, string $type, string $title, string $body, array $options = []): Notification
    {
        return Notification::create([
            'user_id'    => $userId,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'icon'       => $options['icon'] ?? self::defaultIcon($type),
            'color'      => $options['color'] ?? self::defaultColor($type),
            'action_url' => $options['action_url'] ?? null,
        ]);
    }

    /**
     * Broadcast notifikasi ke SEMUA user (user_id = null).
     */
    public static function broadcast(string $type, string $title, string $body, array $options = []): Notification
    {
        return Notification::create([
            'user_id'    => null,
            'type'       => $type,
            'title'      => $title,
            'body'       => $body,
            'icon'       => $options['icon'] ?? self::defaultIcon($type),
            'color'      => $options['color'] ?? self::defaultColor($type),
            'action_url' => $options['action_url'] ?? null,
        ]);
    }

    /**
     * Kirim notifikasi ke semua user dengan role tertentu.
     */
    public static function sendToRole(string $roleName, string $type, string $title, string $body, array $options = []): void
    {
        $users = User::whereHas('role', fn($q) => $q->where('name', $roleName))->get();
        foreach ($users as $user) {
            self::send($user->id, $type, $title, $body, $options);
        }
    }

    /**
     * Shorthand: notifikasi tiket baru untuk admin
     */
    public static function newTicket(int $adminUserId, string $kode, string $pelangganName, string $keluhan): void
    {
        self::send($adminUserId, 'tiket_baru', 'Tiket Gangguan Baru', 
            "Tiket #{$kode} dari {$pelangganName}: {$keluhan}",
            ['icon' => 'bx-error-circle', 'color' => 'danger', 'action_url' => route('tiket.index')]
        );
    }

    /**
     * Shorthand: notifikasi tagihan lunas untuk admin
     */
    public static function billPaid(int $adminUserId, string $pelangganName, string $kode, int $bulan, int $tahun): void
    {
        self::send($adminUserId, 'tagihan_lunas', 'Tagihan Dibayar',
            "{$pelangganName} ({$kode}) membayar tagihan bulan " . self::monthName($bulan) . " {$tahun}.",
            ['icon' => 'bx-money', 'color' => 'success', 'action_url' => route('billing.index')]
        );
    }

    /**
     * Shorthand: notifikasi upgrade paket
     */
    public static function packageUpgrade(int $userId, string $pelangganName, string $paketLama, string $paketBaru): void
    {
        self::send($userId, 'upgrade_paket', 'Permintaan Upgrade Paket',
            "{$pelangganName} minta upgrade dari {$paketLama} ke {$paketBaru}.",
            ['icon' => 'bx-trending-up', 'color' => 'warning', 'action_url' => route('upgrade-paket.index')]
        );
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function defaultIcon(string $type): string
    {
        return match ($type) {
            'tiket_baru'     => 'bx-error-circle',
            'tagihan_lunas'  => 'bx-money',
            'upgrade_paket'  => 'bx-trending-up',
            'system'         => 'bx-cog',
            default          => 'bx-bell',
        };
    }

    private static function defaultColor(string $type): string
    {
        return match ($type) {
            'tiket_baru'     => 'danger',
            'tagihan_lunas'  => 'success',
            'upgrade_paket'  => 'warning',
            'system'         => 'info',
            default          => 'primary',
        };
    }

    private static function monthName(int $bulan): string
    {
        $names = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                  'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        return $names[$bulan] ?? $bulan;
    }
}
