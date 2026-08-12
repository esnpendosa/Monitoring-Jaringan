<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TiketChat extends Model
{
    use HasFactory;

    protected $table = 'tiket_chats';

    protected $fillable = [
        'id_tiket',
        'id_user',
        'message',
        'image_path',
    ];

    public function ticket()
    {
        return $this->belongsTo(TiketGangguan::class, 'id_tiket', 'id_tiket');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
