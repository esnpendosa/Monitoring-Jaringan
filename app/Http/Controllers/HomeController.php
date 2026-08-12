<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();
        
        // Progress Biodata logic
        $biodata = $user->biodata;
        $totalColumns = 8;
        $filledColumns = 0;
        
        if ($biodata) {
            if ($biodata->nik) $filledColumns++;
            if ($biodata->tempat_lahir) $filledColumns++;
            if ($biodata->tgl_lahir) $filledColumns++;
            if ($biodata->jenis_kelamin) $filledColumns++;
            if ($biodata->alamat) $filledColumns++;
            if ($biodata->pendidikan_terakhir) $filledColumns++;
            if ($biodata->riwayat_pendidikan) $filledColumns++;
            if ($biodata->keluarga) $filledColumns++;
        }
        
        $progress = round(($filledColumns / $totalColumns) * 100);

        // Stats for Dashboard
        $stats = [];
        if ($user->isAdminUnit() || $user->isYayasan()) {
            $stats['total_pegawai'] = \App\Models\User::where('role', 'pegawai')->count();
            $stats['cuti_pending'] = \App\Models\Cuti::where('status_akhir', 'Pending')->count();
            $stats['sk_pending'] = \App\Models\SkPermohonan::where('status', 'Pending')->count();
            $stats['dokumen_pending'] = \App\Models\Dokumen::where('status', 'Menunggu')->count();
            $absensiToday = \App\Models\Absensi::where('tgl', date('Y-m-d'))->with('user')->get();
        } else {
            $stats['my_absensi'] = $user->absensis()->count();
            $stats['my_cuti'] = $user->cutis()->count();
            $stats['my_sk'] = $user->skPermohonans()->count();
            $stats['my_dokumen'] = $user->dokumens()->count();
            $absensiToday = $user->absensis()->where('tgl', date('Y-m-d'))->get();
        }

        $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
        $userSchedules = $user->schedules->keyBy('day_index');

        // Today's Events
        $todayEvents = \App\Models\Acara::where('tanggal', date('Y-m-d'))->get();

        return view('home', compact('progress', 'absensiToday', 'stats', 'days', 'userSchedules', 'todayEvents'));
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|min:4|confirmed',
        ], [
            'old_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 4 karakter.',
            'password.confirmed' => 'Konfirmasi password baru tidak cocok.'
        ]);

        $user = auth()->user();

        if (!\Illuminate\Support\Facades\Hash::check($request->old_password, $user->password)) {
            return back()->with('error', 'Password lama Anda salah!');
        }

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'must_change_password' => false
        ]);

        // Re-login to refresh the user object in session
        \Illuminate\Support\Facades\Auth::login($user->fresh());

        return back()->with('success', 'Password Anda telah berhasil diperbarui.');
    }
}
