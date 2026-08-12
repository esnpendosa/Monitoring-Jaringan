<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageUpgrade extends Model
{
    use HasFactory;

    protected $table = 'package_upgrades';

    protected $fillable = [
        'id_pelanggan',
        'id_tagihan',
        'paket_lama',
        'harga_lama',
        'paket_baru',
        'harga_baru',
        'status', // pending, paid, completed, cancelled
    ];

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    public function tagihan()
    {
        return $this->belongsTo(Tagihan::class, 'id_tagihan', 'id_tagihan');
    }
}
