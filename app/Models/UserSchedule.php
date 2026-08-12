<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserSchedule extends Model
{
    protected $fillable = ['user_id', 'day_index', 'minutes', 'jam_masuk', 'jam_pulang'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
