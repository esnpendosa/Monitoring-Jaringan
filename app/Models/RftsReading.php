<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RftsReading extends Model
{
    use HasFactory;

    protected $table = 'rfts_readings';

    protected $fillable = [
        'kabel_id',
        'status',
        'redaman',
        'jarak_putus_meter',
        'waktu_baca',
    ];

    protected $casts = [
        'waktu_baca' => 'datetime',
    ];

    public function kabel()
    {
        return $this->belongsTo(Kabel::class, 'kabel_id');
    }

    /**
     * Label status (human-readable)
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ok'               => '✅ Normal',
            'break'            => '🔴 Putus',
            'attenuation_high' => '🟡 Redaman Tinggi',
            default            => $this->status,
        };
    }
}
