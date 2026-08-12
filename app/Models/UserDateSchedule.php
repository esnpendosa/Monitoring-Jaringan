<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDateSchedule extends Model
{
    protected $table = 'user_date_schedules';

    protected $fillable = [
        'user_id',
        'tgl',
        'status_wajib',
        'keterangan',
        'created_by',
    ];

    protected $casts = [
        'tgl' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Labels for display
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status_wajib) {
            'wajib_masuk'  => 'Wajib Masuk',
            'libur_khusus' => 'Libur Khusus',
            'wfh'          => 'WFH',
            default        => ucfirst($this->status_wajib),
        };
    }

    // Badge color
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status_wajib) {
            'wajib_masuk'  => 'danger',
            'libur_khusus' => 'secondary',
            'wfh'          => 'info',
            default        => 'secondary',
        };
    }
}
