<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPegawai extends Model
{
    protected $fillable = [
        'user_id', 'thn_ajaran', 'unit', 'jenis_pegawai', 'jabatan', 
        'mapel', 'status_pegawai', 'satmingkal', 'golongan', 'thn_mulai', 
        'tgl_selesai', 'tgl_sk', 'file_sk', 'status'
    ];

    protected $casts = [
        'tgl_selesai' => 'date',
        'tgl_sk' => 'date',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($riwayat) {
            // Auto expire if tgl_selesai is in the past
            if ($riwayat->tgl_selesai && $riwayat->tgl_selesai->lt(now()->startOfDay())) {
                $riwayat->status = 'Selesai';
            }

            // If status is 'Selesai', automatically delete physical SK file and set field to null
            if ($riwayat->status === 'Selesai' && $riwayat->file_sk) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($riwayat->file_sk);
                $riwayat->file_sk = null;
            }
        });
    }

    public function user() { return $this->belongsTo(User::class); }
}
