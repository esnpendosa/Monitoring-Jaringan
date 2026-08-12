<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acara extends Model
{
    protected $fillable = ['nama', 'tanggal', 'jam_mulai', 'jam_selesai', 'lokasi', 'qr_code'];

    public function absensiAcaras()
    {
        return $this->hasMany(AbsensiAcara::class);
    }
}
