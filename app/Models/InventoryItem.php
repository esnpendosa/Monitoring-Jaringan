<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasFactory;

    protected $table = 'inventory_items';
    protected $primaryKey = 'id_inventory';

    protected $fillable = [
        'nama_alat', 'gambar_alat', 'kategori', 'merk', 'serial_number', 'stok',
        'kondisi', 'status', 'id_teknisi', 'id_user', 'catatan', 'harga_beli', 'tanggal_beli'
    ];

    protected $casts = [
        'tanggal_beli' => 'date',
    ];

    public function technician()
    {
        return $this->belongsTo(Teknisi::class, 'id_teknisi', 'id_teknisi');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function logs()
    {
        return $this->hasMany(InventoryLog::class, 'id_inventory', 'id_inventory');
    }
}
