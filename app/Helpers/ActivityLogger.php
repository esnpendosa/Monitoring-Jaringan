<?php

namespace App\Helpers;

use App\Models\AktivitasUser;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    public static function log($aktivitas, $tipe = 'system', $actorName = null, $actorRole = null)
    {
        $user = auth()->user();
        
        AktivitasUser::create([
            'id_user' => $user ? $user->id : null,
            'nama_user' => $user ? $user->name : ($actorName ?? 'System'),
            'role' => $user && $user->role ? $user->role->name : ($actorRole ?? 'System'),
            'aktivitas' => $aktivitas,
            'tipe' => $tipe,
            'ip_address' => Request::ip(),
        ]);
    }
}
