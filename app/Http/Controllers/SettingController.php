<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Auth;

class SettingController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'yayasan') {
            abort(403);
        }

        $devicesJson = Setting::get('fingerspot_devices');
        $devices = $devicesJson ? json_decode($devicesJson, true) : [];
        $fonnteToken = Setting::get('fonnte_token');
        $minInterval = Setting::get('absensi_min_interval', 5);

        // Fallback to old keys if no devices yet
        if (empty($devices)) {
            $url = Setting::get('fingerspot_url', env('FINGERSPOT_URL'));
            $sn = Setting::get('fingerspot_sn', env('FINGERSPOT_SN'));
            $sc = Setting::get('fingerspot_sc', env('FINGERSPOT_SC'));

            if ($url && $sn && $sc) {
                $devices[] = [
                    'name' => 'Default Device',
                    'url' => $url,
                    'sn' => $sn,
                    'sc' => $sc
                ];
            }
        }

        return view('settings.fingerspot', compact('devices', 'fonnteToken', 'minInterval'));
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'yayasan') {
            abort(403);
        }

        $request->validate([
            'devices' => 'nullable|array',
            'devices.*.name' => 'required|string',
            'devices.*.url' => 'required|url',
            'devices.*.sn' => 'required|string',
            'devices.*.sc' => 'nullable|string',
            'fonnte_token' => 'nullable|string',
            'absensi_min_interval' => 'nullable|integer|min:0',
        ]);

        if ($request->has('devices')) {
            Setting::updateOrCreate(['key' => 'fingerspot_devices'], ['value' => json_encode(array_values($request->devices))]);
        }

        if ($request->has('fonnte_token')) {
            Setting::updateOrCreate(['key' => 'fonnte_token'], ['value' => $request->fonnte_token]);
        }

        if ($request->has('absensi_min_interval')) {
            Setting::updateOrCreate(['key' => 'absensi_min_interval'], ['value' => $request->absensi_min_interval]);
        }

        return redirect()->back()->with('success', 'Pengaturan berhasil diperbarui.');
    }
}
