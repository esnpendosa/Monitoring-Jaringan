<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modem extends Model
{
    protected $table = 'modems';

    protected $fillable = [
        'nama',
        'merek',
        'model',
        'ip_address',
        'image_path_front',
        'image_path_back',
        'deskripsi',
        'spesifikasi',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
