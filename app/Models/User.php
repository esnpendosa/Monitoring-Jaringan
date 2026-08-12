<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'id_role',
        'pin_fingerspot',
        'no_hp',
        'username_email',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    public function teknisi()
    {
        return $this->hasOne(Teknisi::class, 'id_user');
    }

    public function pelanggan()
    {
        return $this->hasOne(Pelanggan::class, 'id_user');
    }

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(UserSchedule::class, 'user_id');
    }

    public function dateSchedules()
    {
        return $this->hasMany(UserDateSchedule::class, 'user_id');
    }

    /**
     * Cek apakah tanggal tertentu merupakan hari kerja wajib untuk karyawan ini.
     * Prioritas: penugasan tanggal khusus (user_date_schedules) > jadwal mingguan (user_schedules) > default non-weekend
     *
     * @param  \Carbon\Carbon $date
     * @param  \Illuminate\Support\Collection|null $loadedDateSchedules (opsional, untuk menghindari N+1)
     * @param  \Illuminate\Support\Collection|null $loadedSchedules (opsional)
     * @return bool
     */
    public function isWorkDay(\Carbon\Carbon $date, $loadedDateSchedules = null, $loadedSchedules = null): bool
    {
        $dateStr = $date->toDateString();

        // 1. Cek penugasan tanggal khusus (override apapun)
        $dateSchedules = $loadedDateSchedules ?? $this->dateSchedules;
        $dateOverride = $dateSchedules->firstWhere('tgl', $dateStr);
        if ($dateOverride) {
            if (in_array($dateOverride->status_wajib, ['wajib_masuk', 'wfh'])) return true;
            if ($dateOverride->status_wajib === 'libur_khusus') return false;
        }

        // 2. Cek jadwal mingguan
        $schedules = $loadedSchedules ?? $this->schedules;
        $dayIndex  = $date->dayOfWeek; // 0=Minggu ... 6=Sabtu
        $daySchedule = $schedules->firstWhere('day_index', $dayIndex);
        if ($daySchedule) {
            return ($daySchedule->minutes ?? 0) > 0;
        }

        // 3. Default: non-weekend = hari kerja
        return !$date->isWeekend();
    }

    /**
     * Ambil label hari kerja untuk tanggal tertentu
     */
    public function getWorkDayLabel(\Carbon\Carbon $date, $loadedDateSchedules = null): string
    {
        $dateStr = $date->toDateString();
        $dateSchedules = $loadedDateSchedules ?? $this->dateSchedules;
        $dateOverride  = $dateSchedules->firstWhere('tgl', $dateStr);
        if ($dateOverride) {
            return $dateOverride->status_label . ($dateOverride->keterangan ? " ({$dateOverride->keterangan})" : '');
        }
        return '';
    }

    public function hasPermission($permissionCode)
    {
        if (!$this->role) return false;
        return $this->role->permissions()->where('code', $permissionCode)->exists();
    }
}
