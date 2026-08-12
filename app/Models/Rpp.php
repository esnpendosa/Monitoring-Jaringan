<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rpp extends Model
{
    protected $fillable = [
        'user_id', 'tanggal', 'tahun_akademik', 'unit', 'kelas', 
        'mata_pelajaran', 'judul', 'deskripsi', 'dokumen'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
