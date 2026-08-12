<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbsensiAcara extends Model
{
    protected $fillable = ['acara_id', 'user_id', 'waktu_scan'];

    public function acara()
    {
        return $this->belongsTo(Acara::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
