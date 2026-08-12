<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    protected $fillable = [
        'user_id',
        'pin',
        'tgl',
        'jam_masuk',
        'jam_pulang',
        'status_kehadiran',
        'lokasi',
        'keterangan'
    ];

    protected $casts = [
        'tgl' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
