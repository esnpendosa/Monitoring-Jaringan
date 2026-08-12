<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cuti extends Model
{
    protected $fillable = [
        'user_id', 
        'unit',
        'alasan', 
        'tgl_mulai', 
        'tgl_selesai', 
        'dokumen_pendukung',
        'ket_dokumen',
        'catatan',
        'status_unit', 
        'status_yayasan', 
        'status_akhir', 
        'keterangan'
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
