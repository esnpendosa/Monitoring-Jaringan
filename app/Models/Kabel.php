<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kabel extends Model
{
    use HasFactory;

    protected $table = 'kabels';

    protected $fillable = [
        'label',
        'tipe',
        'monitoring_type',
        'from_type',
        'from_id',
        'to_type',
        'to_id',
        'geometry',
        'jumlah_core',
        'status',
        'redaman_db',
        'titik_putus_meter',
        'catatan',
        'updated_by',
    ];

    protected $casts = [
        'geometry' => 'array', // simpan sebagai JSON [[lat,lng],...]
    ];

    /**
     * Node asal kabel (OLT, ODC, atau ODP)
     */
    public function fromNode()
    {
        return match ($this->from_type) {
            'olt' => $this->belongsTo(Olt::class, 'from_id'),
            'odc', 'odp' => $this->belongsTo(OdcOdp::class, 'from_id'),
            default => null,
        };
    }

    /**
     * Node tujuan kabel (ODC, ODP, atau Pelanggan)
     */
    public function toNode()
    {
        return match ($this->to_type) {
            'odc', 'odp' => $this->belongsTo(OdcOdp::class, 'to_id'),
            'pelanggan'  => $this->belongsTo(Pelanggan::class, 'to_id', 'id_pelanggan'),
            default => null,
        };
    }

    /**
     * Riwayat pembacaan RFTS sensor
     */
    public function rftsReadings()
    {
        return $this->hasMany(RftsReading::class, 'kabel_id');
    }

    /**
     * Pembacaan RFTS terakhir
     */
    public function latestRftsReading()
    {
        return $this->hasOne(RftsReading::class, 'kabel_id')->latestOfMany('waktu_baca');
    }

    /**
     * Warna polyline untuk Leaflet berdasarkan status
     */
    public function getPolylineColorAttribute(): string
    {
        return match ($this->status) {
            'online'  => '#28a745', // Hijau
            'warning' => '#ffc107', // Kuning
            'offline' => '#dc3545', // Merah
            default   => '#6c757d', // Abu-abu
        };
    }

    /**
     * Label tipe kabel (human-readable)
     */
    public function getTipeLabelAttribute(): string
    {
        return match ($this->tipe) {
            'feeder'     => 'Feeder (OLT→ODC)',
            'distribusi' => 'Distribusi (ODC→ODP)',
            'drop'       => 'Drop Core (ODP→Pelanggan)',
            default      => $this->tipe,
        };
    }
}
