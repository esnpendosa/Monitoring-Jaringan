<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Olt extends Model
{
    use HasFactory;

    protected $table = 'olts';

    protected $fillable = [
        'nama',
        'lokasi',
        'latitude',
        'longitude',
        'ip_address',
        'snmp_community',
        'kapasitas_pon',
        'status',
        'deskripsi',
    ];

    /**
     * ODC langsung di bawah OLT ini
     */
    public function odcList()
    {
        return $this->hasMany(OdcOdp::class, 'olt_id')->where('tipe', 'ODC');
    }

    /**
     * Kabel yang berasal dari OLT ini
     */
    public function kabels()
    {
        return $this->hasMany(Kabel::class, 'from_id')->where('from_type', 'olt');
    }

    /**
     * Warna status untuk peta
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'online'  => '#28a745',
            'warning' => '#ffc107',
            'offline' => '#dc3545',
            default   => '#6c757d',
        };
    }
}
