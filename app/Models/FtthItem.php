<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FtthItem extends Model
{
    use HasFactory;

    protected $table = 'ftth_items';

    protected $fillable = [
        'kategori', 'nama', 'kode', 'latitude', 'longitude', 'status',
        'merk', 'model', 'serial_number', 'ip_address',
        'tanggal_pasang', 'deskripsi', 'foto',
        'tinggi_tiang', 'material_tiang', 'kapasitas_core',
        'snmp_community', 'kapasitas_port',
        'frekuensi_ghz', 'daya_watt', 'gain_dbi',
        'parent_id', 'parent_type', 'olt_id',
        'updated_by',
    ];

    protected $casts = [
        'tanggal_pasang' => 'date',
        'tinggi_tiang'   => 'float',
        'frekuensi_ghz'  => 'float',
        'daya_watt'      => 'float',
        'gain_dbi'       => 'float',
    ];

    // ─── Kategori constants ─────────────────────────────────────
    const KATEGORI_LABELS = [
        'server_router' => 'Server/Router',
        'tiang_tumpu'   => 'Tiang Tumpu T-Bar',
        'tiang_loop'    => 'Tiang Loop Fiber',
        'slack_loop'    => 'Joint Closure Oval / Loop',
        'tiang_odp'     => 'Tiang ODP',
        'tiang_odc'     => 'Tiang ODC',
        'joint_closure' => 'Joint Closure Box',
        'htb'           => 'HTB',
        'htb_ap'        => 'HTB & Access Point',
        'access_point'  => 'Access Point',
    ];

    const KATEGORI_EMOJI = [
        'server_router' => '🖥️',
        'tiang_tumpu'   => '📡',
        'tiang_loop'    => '⭕',
        'slack_loop'    => '🔄',
        'tiang_odp'     => '🔗',
        'tiang_odc'     => '🌐',
        'joint_closure' => '🔌',
        'htb'           => '📶',
        'htb_ap'        => '📶',
        'access_point'  => '📶',
    ];

    const KATEGORI_COLOR = [
        'server_router' => '#6366f1',
        'tiang_tumpu'   => '#0f172a',
        'tiang_loop'    => '#0f172a',
        'slack_loop'    => '#0f172a',
        'tiang_odp'     => '#0ea5e9',
        'tiang_odc'     => '#8b5cf6',
        'joint_closure' => '#f97316',
        'htb'           => '#06b6d4',
        'htb_ap'        => '#06b6d4',
        'access_point'  => '#10b981',
    ];

    // ─── Accessors ──────────────────────────────────────────────
    public function getKategoriLabelAttribute(): string
    {
        return self::KATEGORI_LABELS[$this->kategori] ?? $this->kategori;
    }

    public function getKategoriEmojiAttribute(): string
    {
        return self::KATEGORI_EMOJI[$this->kategori] ?? '📍';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status ?? 'online') {
            'online'  => '#22c55e',
            'warning' => '#f59e0b',
            'offline' => '#ef4444',
            default   => '#64748b',
        };
    }

    public function getKategoriColorAttribute(): string
    {
        return self::KATEGORI_COLOR[$this->kategori] ?? '#64748b';
    }

    // ─── Relationships ──────────────────────────────────────────
    public function olt()
    {
        return $this->belongsTo(Olt::class, 'olt_id');
    }

    public function children()
    {
        return $this->hasMany(FtthItem::class, 'parent_id')
                    ->where('parent_type', 'ftth_item');
    }

    public function parent()
    {
        return $this->belongsTo(FtthItem::class, 'parent_id');
    }

    // ─── Scopes ─────────────────────────────────────────────────
    public function scopeKategori($query, string $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    public function scopeWithCoords($query)
    {
        return $query->whereNotNull('latitude')->whereNotNull('longitude');
    }

    // ─── Helper ─────────────────────────────────────────────────
    public static function generateKode(string $kategori): string
    {
        $prefix = [
            'server_router' => 'SVR',
            'tiang_tumpu'   => 'TTM',
            'tiang_loop'    => 'TLP',
            'slack_loop'    => 'SLK',
            'tiang_odp'     => 'TODP',
            'tiang_odc'     => 'TODC',
            'joint_closure' => 'JC',
            'htb'           => 'HTB',
            'htb_ap'        => 'HTB',
            'access_point'  => 'AP',
        ][$kategori] ?? 'ITEM';

        $maxId = static::max('id') ?? 0;
        $num = $maxId + 1;

        do {
            $kode = $prefix . '-' . str_pad($num, 4, '0', STR_PAD_LEFT);
            $num++;
        } while (static::where('kode', $kode)->exists());

        return $kode;
    }
}
