<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\FonnteService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class WhatsAppResetController extends Controller
{
    public function request(Request $request, FonnteService $fonnte)
    {
        $username = $request->query('username');

        if (!$username) {
            return redirect()->route('login')->withErrors(['username' => 'Silahkan masukkan username untuk reset WA.']);
        }

        $user = User::where('username', $username)->with('biodata')->first();

        if (!$user || !$user->biodata || !$user->biodata->no_wa) {
            return redirect()->route('login')->withErrors(['username' => 'Username tidak ditemukan atau nomor WA belum terdaftar di sistem.']);
        }

        // Generate temporary password
        $tempPassword = Str::random(8);
        $user->password = Hash::make($tempPassword);
        $user->save();

        // Send via WA
        $target = $user->biodata->no_wa;
        $message = "Yth. *" . strtoupper($user->name) . "*,\n\n"
                 . "Kami menerima permintaan reset password untuk akun SIAP Digital Anda.\n\n"
                 . "🔹 Username: *{$user->username}*\n"
                 . "🔹 Password Baru: *{$tempPassword}*\n\n"
                 . "Silahkan login menggunakan password di atas dan *SEGERA UBAH PASSWORD* Anda pada menu Profil setelah berhasil masuk demi keamanan data.\n\n"
                 . "Terima kasih.\n_Yayasan PMU Bungah_";

        $result = $fonnte->sendMessage($target, $message);

        if ($result['status']) {
            return redirect()->route('login')->with('status', 'Password baru berhasil dikirim ke nomor WhatsApp Anda. Silahkan cek HP Anda.');
        } else {
            return redirect()->route('login')->withErrors(['username' => 'Gagal mengirim pesan WA: ' . $result['message']]);
        }
    }
}
