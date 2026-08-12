<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Biodata;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function store(Request $request, \App\Services\FonnteService $fonnte)
    {
        $currentUser = Auth::user();
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'unit' => 'required|string',
            'role' => 'required|string',
            'no_wa' => 'required|string|max:20',
            'password' => 'nullable|string|min:6',
        ]);

        $username = explode('@', $request->email)[0];
        // Handle duplicate usernames
        $original = $username;
        $count = 1;
        while (User::where('username', $username)->exists()) {
            $username = $original . $count;
            $count++;
        }

        $passwordRaw = $request->password ?: $username;

        $user = User::create([
            'name'                 => $request->name,
            'email'                => $request->email,
            'username'             => $username,
            'password'             => Hash::make($passwordRaw),
            'role'                 => $request->role,
            'unit'                 => $request->unit,
            'must_change_password' => true, // Wajib ganti password saat pertama login
        ]);

        $formattedWa = $this->formatWhatsApp($request->no_wa);

        // Create initial biodata with WA number and auto-entry date
        $user->biodata()->create([
            'no_wa' => $formattedWa,
            'tgl_masuk' => now(),
        ]);

        // Message Format (As requested)
        $waMessage = "Yth. Bapak/Ibu *{$user->name}*,\ndimohon untuk mengisi biodata pada Sistem Kepegawaian Perkumpulan Manbaul Ulum Bungah sebagai berikut:\n\n"
                   . "Alamat : https://pmub.my.id\n"
                   . "Username : *{$user->username}*\n"
                   . "Password : *{$passwordRaw}*\n\n"
                   . "Silahkan mengganti password setelah login kemudian mengisi semua data yang diperlukan, terima kasih.";
        
        // Auto Send via Fonnte
        $fonnteResult = $fonnte->sendMessage($formattedWa, $waMessage);
        
        // Fallback WA Link for UI
        $waLink = "https://wa.me/{$formattedWa}?text=" . urlencode($waMessage);

        $successMsg = "Akun {$user->name} berhasil dibuat.";
        if ($fonnteResult['status']) {
            $successMsg .= " Notifikasi registrasi telah dikirim ke WhatsApp.";
        } else {
            $successMsg .= " Akun aktif, namun gagal mengirim WA otomatis.";
        }

        return redirect()->back()->with('success', $successMsg)->with('wa_link', $waLink);
    }

    public function update(Request $request, $id)
    {
        $currentUser = Auth::user();
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        $user = User::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'unit' => 'required|string',
            'role' => 'required|string',
            'pin_fingerspot' => 'nullable|string',
        ]);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'unit' => $request->unit,
            'role' => $request->role,
            'pin_fingerspot' => $request->pin_fingerspot,
        ]);

        if ($request->filled('password')) {
            $user->update(['password' => Hash::make($request->password)]);
        }

        return redirect()->back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $currentUser = Auth::user();
        if (!($currentUser->isYayasan() || $currentUser->isAdminUnit())) {
            abort(403);
        }

        $user = User::findOrFail($id);
        
        $user->biodata()->delete();
        $user->dokumens()->delete();
        $user->absensis()->delete();
        $user->delete();

        return redirect()->back()->with('success', 'Akun pegawai dan seluruh datanya berhasil dihapus.');
    }

    private function formatWhatsApp($number)
    {
        $number = preg_replace('/[^0-9]/', '', $number);
        if (str_starts_with($number, '08')) {
            $number = '628' . substr($number, 2);
        } elseif (str_starts_with($number, '8')) {
            $number = '62' . $number;
        }
        return $number;
    }
    public function export()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\EmployeeExport, 'data_pegawai_pmu_'.date('Ymd').'.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048'
        ]);

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EmployeeImport, $request->file('file'));
            return redirect()->back()->with('success', 'Data pegawai berhasil diimpor.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal impor data: ' . $e->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\EmployeeTemplateExport, 'template_import_pegawai.xlsx');
    }
}
