<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pelanggan extends Model
{
    use HasFactory;

    protected $table = 'pelanggan';
    protected $primaryKey = 'id_pelanggan';

    protected $fillable = [
        'id_user',
        'id_router',
        'kode_pelanggan',
        'nama_pelanggan',
        'email',
        'no_wa',
        'mikrotik_username',
        'mikrotik_type',
        'alamat',
        'latitude',
        'longitude',
        'usage_gb',
        'jumlah_device',
        'paket',
        'harga_layanan',
        'is_active',
        'is_isolated',
        'wa_active',
        'prioritas_label',
        'ip_address',
        'last_online_status',
        'last_ping_at',
        'billing_date',
        'foto_rumah',
        'tanggal_pasang',
        'gratis_pemasangan',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function router()
    {
        return $this->belongsTo(Router::class, 'id_router', 'id_router');
    }

    public function tagihan()
    {
        return $this->hasMany(Tagihan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function tiket()
    {
        return $this->hasMany(TiketGangguan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function getWifiProfileAttribute()
    {
        $harga = $this->harga_layanan;
        $hargaK = number_format($harga / 1000, 0, ',', '.') . '.k';
        switch ($harga) {
            case 100000:
                return "Paket 1 {$hargaK}";
            case 130000:
                return "Paket 2 {$hargaK}";
            case 150000:
                return "Paket 3 {$hargaK}";
            case 200000:
                return "Paket 4 {$hargaK}";
            default:
                return "Paket {$hargaK}";
        }
    }

    public function isBulanGratis($month, $year)
    {
        if (!$this->gratis_pemasangan) {
            return false;
        }

        $tanggalPasang = $this->tanggal_pasang ?: $this->created_at;
        if (!$tanggalPasang) {
            return false;
        }

        $carbonPasang = \Carbon\Carbon::parse($tanggalPasang);
        $pasangDay = $carbonPasang->day;

        // Bonus gratis pemasangan jika dibawah tgl 27 terhitung di bulan pemasangan, dan jika diatas tgl 27 gratis terhitung mulai bulan depan
        if ($pasangDay < 27) {
            $freeStart = $carbonPasang->copy()->startOfMonth();
            $freeEnd = $carbonPasang->copy()->addMonth()->endOfMonth();
        } else {
            $freeStart = $carbonPasang->copy()->addMonth()->startOfMonth();
            $freeEnd = $carbonPasang->copy()->addMonths(2)->endOfMonth();
        }

        $targetDate = \Carbon\Carbon::createFromDate($year, $month, 1)->startOfMonth();

        return $targetDate->between($freeStart, $freeEnd);
    }
}
