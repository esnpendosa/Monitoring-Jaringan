<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tutorial extends Model
{
    protected $fillable = [
        'judul', 'slug', 'kategori', 'thumbnail',
        'ringkasan', 'konten', 'urutan', 'is_published', 'created_by'
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    // Auto-generate slug dari judul
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($tutorial) {
            if (empty($tutorial->slug)) {
                $tutorial->slug = static::generateUniqueSlug($tutorial->judul);
            }
        });
        static::updating(function ($tutorial) {
            if ($tutorial->isDirty('judul') && !$tutorial->isDirty('slug')) {
                $tutorial->slug = static::generateUniqueSlug($tutorial->judul, $tutorial->id);
            }
        });
    }

    protected static function generateUniqueSlug($judul, $excludeId = null)
    {
        $slug = Str::slug($judul);
        $original = $slug;
        $i = 1;
        while (static::where('slug', $slug)->when($excludeId, fn($q) => $q->where('id', '!=', $excludeId))->exists()) {
            $slug = $original . '-' . $i++;
        }
        return $slug;
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('created_at', 'desc');
    }

    // Daftar kategori yang tersedia
    public static function kategoriList()
    {
        return ['Modem', 'Router', 'WiFi', 'Umum', 'Troubleshooting'];
    }
}
