<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkPermohonan extends Model
{
    protected $fillable = [
        'user_id', 
        'catatan', 
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
