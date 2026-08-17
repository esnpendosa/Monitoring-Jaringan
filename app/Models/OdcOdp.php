<?php

namespace App\Models;

use App\Models\Kabel;
use App\Models\Olt;
use App\Models\Pelanggan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OdcOdp extends Model
{
    use HasFactory;

    protected $table = 'odc_odp';

    protected $fillable = [
        'nama',
        'tipe',
        'latitude',
        'longitude',
        'foto',
        'deskripsi',
        'parent_id',
    ];

    public function parent()
    {
        return $this->belongsTo(OdcOdp::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(OdcOdp::class, 'parent_id');
    }

    /**
     * OLT induk node ini
     */
    public function olt()
    {
        return $this->belongsTo(Olt::class, 'olt_id');
    }

    /**
     * Pelanggan yang terhubung ke ODP ini
     */
    public function pelanggan()
    {
        return $this->hasMany(Pelanggan::class, 'odp_id');
    }

    /**
     * Kabel yang masuk ke node ini
     */
    public function kabelsIn()
    {
        return $this->hasMany(Kabel::class, 'to_id')
            ->where('to_type', $this->tipe === 'ODC' ? 'odc' : 'odp');
    }

    /**
     * Kabel yang keluar dari node ini
     */
    public function kabelsOut()
    {
        return $this->hasMany(Kabel::class, 'from_id')
            ->where('from_type', $this->tipe === 'ODC' ? 'odc' : 'odp');
    }

    /**
     * Warna status untuk marker peta
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status ?? 'online') {
            'online'  => '#28a745',
            'warning' => '#ffc107',
            'offline' => '#dc3545',
            default   => '#6c757d',
        };
    }
}
