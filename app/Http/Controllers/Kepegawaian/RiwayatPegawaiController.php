<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RiwayatPegawai;
use Illuminate\Support\Facades\Auth;

class RiwayatPegawaiController extends Controller
{
    public function store(Request $request)
    {
        if (!Auth::user()->isYayasan()) {
            abort(403, 'Hanya Yayasan yang memiliki hak akses untuk menambahkan riwayat status pegawai.');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'thn_ajaran' => 'required|string',
            'unit' => 'required|string',
            'jenis_pegawai' => 'nullable|string',
            'jabatan' => 'required|string',
            'mapel' => 'nullable|string',
            'status_pegawai' => 'nullable|string',
            'satmingkal' => 'nullable|string',
            'golongan' => 'nullable|string',
            'thn_mulai' => 'nullable|string',
            'tgl_selesai' => 'nullable|date',
            'tgl_sk' => 'nullable|date',
            'file_sk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('file_sk')) {
            $path = $request->file('file_sk')->store('dokumen_sk', 'public');
            $data['file_sk'] = $path;
        }

        RiwayatPegawai::create($data);

        return redirect()->back()->with('success', 'Riwayat status pegawai berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        if (!Auth::user()->isYayasan()) {
            abort(403, 'Hanya Yayasan yang memiliki hak akses untuk mengubah riwayat status pegawai.');
        }

        $riwayat = RiwayatPegawai::findOrFail($id);

        $data = $request->validate([
            'thn_ajaran' => 'required|string',
            'unit' => 'required|string',
            'jenis_pegawai' => 'nullable|string',
            'jabatan' => 'required|string',
            'mapel' => 'nullable|string',
            'status_pegawai' => 'nullable|string',
            'satmingkal' => 'nullable|string',
            'golongan' => 'nullable|string',
            'thn_mulai' => 'nullable|string',
            'tgl_selesai' => 'nullable|date',
            'tgl_sk' => 'nullable|date',
            'file_sk' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'status' => 'required|string',
        ]);

        if ($request->hasFile('file_sk')) {
            if ($riwayat->file_sk) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($riwayat->file_sk);
            }
            $path = $request->file('file_sk')->store('dokumen_sk', 'public');
            $data['file_sk'] = $path;
        }

        $riwayat->update($data);

        return redirect()->back()->with('success', 'Riwayat status pegawai berhasil diperbarui.');
    }

    public function destroy($id)
    {
        if (!Auth::user()->isYayasan()) {
            abort(403, 'Hanya Yayasan yang memiliki hak akses untuk menghapus riwayat status pegawai.');
        }

        $riwayat = RiwayatPegawai::findOrFail($id);
        if ($riwayat->file_sk) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($riwayat->file_sk);
        }
        $riwayat->delete();
        return redirect()->back()->with('success', 'Riwayat berhasil dihapus.');
    }
}
