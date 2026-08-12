<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Izin extends Model
{
    protected $fillable = [
        'user_id',
        'jenis_izin',
        'tgl_mulai',
        'tgl_selesai',
        'alasan',
        'lampiran',
        'status',
        'keterangan_admin'
    ];

    protected $casts = [
        'tgl_mulai' => 'date',
        'tgl_selesai' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
