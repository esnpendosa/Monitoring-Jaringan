<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Biodata;
use Illuminate\Support\Facades\Auth;

class BiodataController extends Controller
{
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        
        // If Yayasan or Admin and no specific user_id requested, show employee list
        if (($currentUser->isYayasan() || $currentUser->isAdminUnit()) && !$request->has('user_id')) {
            $users = \App\Models\User::where('role', \App\Models\User::ROLE_PEGAWAI)
                ->when($currentUser->isAdminUnit(), function($q) use ($currentUser) {
                    return $q->where('unit', $currentUser->unit);
                })
                ->when($request->unit, function($q) use ($request) {
                    return $q->where('unit', $request->unit);
                })
                ->with('biodata')
                ->paginate(10);
            
            $units = \App\Models\Unit::orderBy('nama')->get();
            
            return view('kepegawaian.biodata_list', compact('users', 'units'));
        }

        // Determine which user profile to view
        if ($request->has('user_id') && ($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            $user = \App\Models\User::findOrFail($request->user_id);
        } else {
            $user = $currentUser;
        }

        $biodata = $user->biodata ?? new Biodata();
        $dokumens = $user->dokumens;
        // Auto-check and expire active status history records if tgl_selesai has passed
        $activeRiwayats = $user->riwayatPegawais()->where('status', 'Aktif')->get();
        foreach ($activeRiwayats as $riwayat) {
            if ($riwayat->tgl_selesai && $riwayat->tgl_selesai->lt(now()->startOfDay())) {
                $riwayat->status = 'Selesai';
                $riwayat->save();
            }
        }

        $riwayats = $user->riwayatPegawais()->orderBy('thn_ajaran', 'desc')->get();
        $riwayatPend = $biodata->riwayat_pendidikan ?? [];
        $children = $biodata->keluarga['anak'] ?? [];

        return view('kepegawaian.biodata', compact('user', 'biodata', 'dokumens', 'riwayats', 'riwayatPend', 'children'));
    }

    public function updateFoto(Request $request)
    {
        $request->validate([
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:512', // Max 512KB
        ]);

        $user = Auth::user();
        
        // Delete all old photos if any exist to clean up duplicates
        $oldFotos = \App\Models\Dokumen::where('user_id', $user->id)->where('tipe', 'Foto')->get();
        foreach ($oldFotos as $oldFoto) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($oldFoto->file_path);
            $oldFoto->delete();
        }

        // Store new photo
        $path = $request->file('foto')->store('uploads/fotos', 'public');

        \App\Models\Dokumen::create([
            'user_id' => $user->id,
            'tipe' => 'Foto',
            'file_path' => $path,
            'status' => 'Disetujui' // Auto approve for profile photo
        ]);

        return redirect()->back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function store(Request $request)
    {
        $currentUser = Auth::user();
        
        // Determine target user (self or others if admin/yayasan)
        $targetUserId = $request->get('user_id', $currentUser->id);
        if ($targetUserId != $currentUser->id && !($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }
        
        $user = \App\Models\User::findOrFail($targetUserId);

        $data = $request->validate([
            'kode_pegawai' => 'nullable|string',
            'nik' => 'nullable|string|max:20',
            'tempat_lahir' => 'nullable|string|max:100',
            'tgl_lahir' => 'nullable|date',
            'jenis_kelamin' => 'nullable|string',
            'alamat' => 'nullable|string',
            'agama' => 'nullable|string',
            'gol_darah' => 'nullable|string',
            'no_wa' => 'nullable|string',
            'rekening' => 'nullable|string',
            'tgl_masuk' => 'nullable|date',
            'status_pegawai' => 'nullable|string',
            'status_pernikahan' => 'nullable|string',
            'tgl_menikah' => 'nullable|date',
            'jumlah_anak' => 'nullable|integer',
            'pendidikan_terakhir' => 'nullable|string',
        ]);

        // Proteksi field khusus Yayasan
        if (!$currentUser->isYayasan()) {
            unset($data['tgl_masuk']);
            unset($data['status_pegawai']);
        }

        $biodata = Biodata::updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        // Update User Account Info
        $userData = [];
        if ($request->has('username')) {
            $request->validate(['username' => 'required|string|unique:users,username,'.$user->id]);
            $userData['username'] = $request->username;
        }
        if ($request->has('email')) {
            $request->validate(['email' => 'required|email|unique:users,email,'.$user->id]);
            $userData['email'] = $request->email;
        }
        if ($request->filled('password')) {
            $request->validate(['password' => 'required|string|min:8']);
            $userData['password'] = \Illuminate\Support\Facades\Hash::make($request->password);
        }
        if ($request->has('pin_fingerspot')) {
            $userData['pin_fingerspot'] = $request->pin_fingerspot;
        }

        if (!empty($userData)) {
            $user->update($userData);
        }

        // Update Children if provided
        if ($request->has('anak_nama')) {
            $anakList = [];
            foreach ($request->anak_nama as $index => $nama) {
                if ($nama) {
                    $anakList[] = [
                        'nama' => $nama,
                        'ttl' => $request->anak_ttl[$index] ?? '',
                        'gender' => $request->anak_gender[$index] ?? '',
                        'status' => $request->anak_status[$index] ?? ''
                    ];
                }
            }
            $keluarga = $biodata->keluarga ?? [];
            $keluarga['anak'] = $anakList;
            $biodata->update([
                'keluarga' => $keluarga,
                'jumlah_anak' => count($anakList)
            ]);
        }

        // Update Education if provided
        if ($request->has('pend_jenjang')) {
            $oldPend = $biodata->riwayat_pendidikan ?? [];
            $pendList = [];
            foreach ($request->pend_jenjang as $index => $jenjang) {
                if ($request->pend_sekolah[$index]) {
                    $pendList[] = [
                        'jenjang' => $jenjang,
                        'sekolah' => $request->pend_sekolah[$index],
                        'alamat' => $request->pend_alamat[$index] ?? '',
                        'tgl_masuk' => $request->pend_tgl_masuk[$index] ?? '',
                        'tgl_lulus' => $request->pend_tgl_lulus[$index] ?? '',
                        'jurusan' => $request->pend_jurusan[$index] ?? ''
                    ];
                }
            }

            // Detect and delete removed education documents (Ijazah)
            $newJenjangs = collect($pendList)->pluck('jenjang')->toArray();
            foreach ($oldPend as $oldItem) {
                $oldJenjang = $oldItem['jenjang'] ?? null;
                if ($oldJenjang && !in_array($oldJenjang, $newJenjangs)) {
                    // Delete the physical file and database entry for this ijazah
                    $ijazahDoc = $user->dokumens()->where('tipe', 'Ijazah ' . $oldJenjang)->first();
                    if ($ijazahDoc) {
                        \Illuminate\Support\Facades\Storage::disk('public')->delete($ijazahDoc->file_path);
                        $ijazahDoc->delete();
                    }
                }
            }

            $biodata->update(['riwayat_pendidikan' => $pendList]);
        }

        return redirect()->back()->with('success', 'Biodata berhasil diperbarui.');
    }

    public function updateSchedule(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'schedule' => 'required|array',
            'jam_masuk' => 'required|array',
            'jam_pulang' => 'required|array',
        ]);

        $currentUser = Auth::user();
        if ($request->user_id != $currentUser->id && !($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        foreach ($request->schedule as $dayIndex => $minutes) {
            \App\Models\UserSchedule::updateOrCreate(
                ['user_id' => $request->user_id, 'day_index' => $dayIndex],
                [
                    'minutes' => $minutes,
                    'jam_masuk' => $request->jam_masuk[$dayIndex] ?? null,
                    'jam_pulang' => $request->jam_pulang[$dayIndex] ?? null,
                ]
            );
        }

        return redirect()->back()->with('success', 'Jadwal kerja berhasil diperbarui.');
    }
}
