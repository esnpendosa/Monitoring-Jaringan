<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dokumen extends Model
{
    protected $fillable = [
        'user_id', 'tipe', 'file_path', 'status', 'keterangan'
    ];

    public function user() { return $this->belongsTo(User::class); }
}
