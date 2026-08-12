<?php
use Illuminate\Support\Facades\Route;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Http;

Route::get('/debug-finger', function () {
    $devicesJson = Setting::get('fingerspot_devices');
    $devices = json_decode($devicesJson, true) ?: [];
    
    $registered_pins = User::whereNotNull('pin_fingerspot')
        ->get(['name', 'pin_fingerspot'])
        ->pluck('pin_fingerspot', 'name');

    $results = [
        'info' => 'Fingerprint Solutions X100-C Debug Info',
        'registered_pins' => $registered_pins,
        'devices' => []
    ];

    foreach ($devices as $device) {
        $name = $device['name'] ?? 'Unknown';
        
        // Manual test pull (If it's an internal URL, it will hit handleADMS)
        try {
            $response = Http::timeout(5)->post($device['url'], [
                'sn' => $device['sn'],
                'sc' => $device['sc'] ?? '',
            ]);
            
            $results['devices'][$name] = [
                'name' => $name,
                'sn' => $device['sn'],
                'url' => $device['url'],
                'http_status' => $response->status(),
                'raw_body' => $response->body(),
                'type' => str_contains($device['url'], 'iclock/cdata') ? 'ADMS (PUSH)' : 'PULL (CLOUD/PROXY)'
            ];
        } catch (\Exception $e) {
            $results['devices'][$name] = [
                'name' => $name,
                'error' => $e->getMessage()
            ];
        }
    }
    
    return response()->json($results, 200, [], JSON_PRETTY_PRINT);
});
