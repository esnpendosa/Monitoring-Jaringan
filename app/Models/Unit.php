<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $fillable = ['nama', 'slug', 'keterangan'];

    public static function boot()
    {
        parent::boot();
        static::creating(function ($unit) {
            $unit->slug = \Illuminate\Support\Str::slug($unit->nama);
        });
    }
}
