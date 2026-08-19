<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PelangganController;

use App\Http\Controllers\TiketController;
use App\Http\Controllers\MikrotikController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\ScanController;
use App\Http\Controllers\OdcOdpController;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\NotificationController;

// Fallback route to serve storage files directly without depending on symlink permissions
Route::get('storage/{folder}/{filename}', function ($folder, $filename) {
    $path = storage_path("app/public/{$folder}/{$filename}");
    if (!file_exists($path)) {
        abort(404);
    }
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'pdf' => 'application/pdf',
    ];
    $contentType = $mimes[$extension] ?? 'application/octet-stream';
    return response()->file($path, [
        'Content-Type' => $contentType,
        'Cache-Control' => 'public, max-age=86400',
    ]);
});

// Auth Routes (Using template's basic login for now)
Route::get('/login', [LoginBasic::class, 'index'])->name('login');
Route::get('/auth/login-basic', [LoginBasic::class, 'index']);
Route::post('/login', [LoginBasic::class, 'authenticate'])->name('login.post');
Route::get('/auth/register-basic', [\App\Http\Controllers\authentications\RegisterBasic::class, 'index'])->name('auth-register-basic');
Route::post('/auth/register-basic', [\App\Http\Controllers\authentications\RegisterBasic::class, 'store'])->name('auth-register-basic.store');
Route::post('/logout', function() {
    \App\Helpers\ActivityLogger::log('Melakukan logout dari sistem', 'auth');
    auth()->logout();
    return redirect()->route('login');
})->name('logout');

// Public billing route for automatic customer login & redirect
Route::get('billing', function(\Illuminate\Http\Request $request) {
    if ($request->filled('search')) {
        $code = trim($request->input('search'));
        
        $pelanggan = \App\Models\Pelanggan::where('kode_pelanggan', $code)
            ->orWhere('id_pelanggan', $code)
            ->orWhere('mikrotik_username', $code)
            ->first();
            
        if ($pelanggan) {
            // Check if it's an authenticated Admin/Staff
            if (auth()->check() && auth()->user()->hasPermission('pelanggan_manage')) {
                return app(\App\Http\Controllers\TagihanController::class)->index($request);
            }
            
            // Redirect guests/customers directly to the public Quick Pay page
            return redirect()->route('payment.by-id', ['kode_pelanggan' => $pelanggan->kode_pelanggan]);
        }
    }
    
    if (auth()->check()) {
        return app(\App\Http\Controllers\TagihanController::class)->index($request);
    }
    
    return redirect()->route('login');
})->name('billing.index');

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Intern Tasks & Kanban Board
    Route::post('/intern/tasks/{task}/status', [DashboardController::class, 'updateInternTaskStatus'])->name('intern.tasks.update-status');

    // Admin Intern Tasks Management (Protected by can:user_manage)
    Route::middleware('can:user_manage')->group(function() {
        Route::get('/admin/intern-tasks', [DashboardController::class, 'adminInternTasksIndex'])->name('admin.intern-tasks.index');
        Route::post('/admin/intern-tasks', [DashboardController::class, 'adminStoreInternTask'])->name('admin.intern-tasks.store');
        Route::put('/admin/intern-tasks/{task}', [DashboardController::class, 'adminUpdateInternTask'])->name('admin.intern-tasks.update');
        Route::delete('/admin/intern-tasks/{task}', [DashboardController::class, 'adminDeleteInternTask'])->name('admin.intern-tasks.destroy');
    });

    // Pelanggan
    Route::middleware('can:pelanggan_manage')->group(function() {
        Route::get('registrasi', [PelangganController::class, 'registrasiIndex'])->name('pelanggan.registrasi.index');
        Route::post('registrasi/{pelanggan}/send-to-group', [PelangganController::class, 'sendRegistrasiToGroup'])->name('pelanggan.registrasi.send-to-group');
        Route::get('/pelanggan/card-massal', [PelangganController::class, 'cardMassal'])->name('pelanggan.card-massal');
        Route::get('/pelanggan/export', [PelangganController::class, 'export'])->name('pelanggan.export');
        Route::get('/pelanggan/get-next-code', [PelangganController::class, 'getNextCode'])->name('pelanggan.next-code');
        Route::get('/pelanggan/monitoring', [PelangganController::class, 'monitoring'])->name('pelanggan.monitoring');
        Route::get('/pelanggan/monitoring-data', [PelangganController::class, 'monitoringData'])->name('pelanggan.monitoring.data');
        Route::post('/pelanggan/{pelanggan}/ping', [PelangganController::class, 'pingPelanggan'])->name('pelanggan.ping');
        Route::get('pelanggan/{pelanggan}/delete-direct', [PelangganController::class, 'destroyDirect'])->name('pelanggan.destroy-direct');
        Route::resource('pelanggan', PelangganController::class);
        Route::get('odc-odp', function() { return redirect()->route('ftth.dashboard'); })->name('odc-odp.index');
        Route::any('odc-odp/{any}', function() { return redirect()->route('ftth.dashboard'); })->where('any', '.*');
        Route::post('pelanggan-import', [PelangganController::class, 'import'])->name('pelanggan.import');
        Route::get('map-pelanggan', function() { return redirect()->route('ftth.dashboard'); })->name('pelanggan.map');
        Route::post('pelanggan/{pelanggan}/toggle-status', [PelangganController::class, 'toggleStatus'])->name('pelanggan.toggle-status');
        Route::post('pelanggan/{pelanggan}/toggle-wa', [PelangganController::class, 'toggleWa'])->name('pelanggan.toggle-wa');
        Route::post('pelanggan/toggle-all-wa', [PelangganController::class, 'toggleAllWa'])->name('pelanggan.toggle-all-wa');
        Route::get('pelanggan/{pelanggan}/traffic', [PelangganController::class, 'traffic'])->name('pelanggan.traffic');

    });

    // Customer Routes
    Route::get('my-connection', [PelangganController::class, 'myConnection'])->name('pelanggan.my-connection');
    Route::get('/pelanggan/{pelanggan}/card', [PelangganController::class, 'card'])->name('pelanggan.card');

    // Jaringan FTTH
    Route::middleware('can:pelanggan_manage')->group(function() {
        Route::get('kabel', function() { return redirect()->route('ftth.dashboard'); })->name('kabel.index');
        Route::any('kabel/{any}', function() { return redirect()->route('ftth.dashboard'); })->where('any', '.*');
        Route::get('olt', function() { return redirect()->route('ftth.dashboard'); })->name('olt.index');
        Route::any('olt/{any}', function() { return redirect()->route('ftth.dashboard'); })->where('any', '.*');
        Route::get('ftth/map', [\App\Http\Controllers\FtthMapController::class, 'index'])->name('ftth.map');
        Route::get('ftth', [\App\Http\Controllers\FtthMapController::class, 'dashboard'])->name('ftth.dashboard');
        // FTTH API — nodes existing
        Route::get('ftth/api/nodes', [\App\Http\Controllers\FtthMapController::class, 'apiNodes'])->name('ftth.api.nodes');
        Route::post('ftth/api/olt', [\App\Http\Controllers\FtthMapController::class, 'storeOlt'])->name('ftth.api.olt.store');
        Route::post('ftth/api/node', [\App\Http\Controllers\FtthMapController::class, 'storeNode'])->name('ftth.api.node.store');
        Route::put('ftth/api/node/{id}', [\App\Http\Controllers\FtthMapController::class, 'updateNode'])->name('ftth.api.node.update');
        Route::delete('ftth/api/node/{type}/{id}', [\App\Http\Controllers\FtthMapController::class, 'deleteNode'])->name('ftth.api.node.delete');
        Route::post('ftth/api/kabel', [\App\Http\Controllers\FtthMapController::class, 'storeKabel'])->name('ftth.api.kabel.store');
        Route::put('ftth/api/kabel/{kabel}', [\App\Http\Controllers\FtthMapController::class, 'updateKabel'])->name('ftth.api.kabel.update');
        Route::delete('ftth/api/kabel/{kabel}', [\App\Http\Controllers\FtthMapController::class, 'deleteKabel'])->name('ftth.api.kabel.delete');
        // FTTH Items — Tiang Tumpu, Joint Closure, HTB, AP, Server/Router, dst
        Route::get('ftth/api/items', [\App\Http\Controllers\FtthItemController::class, 'apiItems'])->name('ftth.api.items');
        Route::post('ftth/api/items', [\App\Http\Controllers\FtthItemController::class, 'store'])->name('ftth.api.items.store');
        Route::put('ftth/api/items/{ftthItem}', [\App\Http\Controllers\FtthItemController::class, 'update'])->name('ftth.api.items.update');
        Route::delete('ftth/api/items/{ftthItem}', [\App\Http\Controllers\FtthItemController::class, 'destroy'])->name('ftth.api.items.destroy');
        Route::post('ftth/api/items/auto-generate-tiang', [\App\Http\Controllers\FtthItemController::class, 'autoGenerateTiang'])->name('ftth.api.items.auto-tiang');
        // KMZ Export / Import
        Route::get('ftth/export-kmz', [\App\Http\Controllers\FtthItemController::class, 'exportKmz'])->name('ftth.export.kmz');
        Route::post('ftth/import-kmz', [\App\Http\Controllers\FtthItemController::class, 'importKmz'])->name('ftth.import.kmz');
        // Advanced Features
        Route::post('ftth/api/redaman-calc', [\App\Http\Controllers\FtthAdvancedController::class, 'kalkulatorRedaman'])->name('ftth.api.redaman');
        Route::get('ftth/api/redaman-calc/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'kalkulatorRedamanPelanggan'])->name('ftth.api.redaman.pelanggan');
        Route::get('ftth/api/wifi-info/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'getWifiInfo'])->name('ftth.api.wifi-info');
        Route::post('ftth/api/set-wifi/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'setWifi'])->name('ftth.api.set-wifi');
        Route::post('ftth/api/reboot-ont/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'rebootOnt'])->name('ftth.api.reboot-ont');
        Route::post('ftth/api/ping', [\App\Http\Controllers\FtthAdvancedController::class, 'ping'])->name('ftth.api.ping');
        Route::get('ftth/api/data-table', [\App\Http\Controllers\FtthAdvancedController::class, 'dataTable'])->name('ftth.api.data-table');
        Route::get('ftth/export-csv', [\App\Http\Controllers\FtthAdvancedController::class, 'exportCsv'])->name('ftth.export.csv');
        Route::post('ftth/api/set-baseline/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'setBaseline'])->name('ftth.api.set-baseline');
        Route::post('ftth/api/duplicate-pelanggan/{pelanggan}', [\App\Http\Controllers\FtthAdvancedController::class, 'duplicatePelanggan'])->name('ftth.api.duplicate');
        Route::post('ftth/api/sync-now', [\App\Http\Controllers\FtthAdvancedController::class, 'syncNow'])->name('ftth.api.sync-now');
        // GenieACS Full App Management Routes
        Route::get('ftth/api/acs/test', [\App\Http\Controllers\FtthAdvancedController::class, 'testAcsConnection'])->name('ftth.api.acs.test');
        Route::post('ftth/api/acs/config', [\App\Http\Controllers\FtthAdvancedController::class, 'saveAcsSettings'])->name('ftth.api.acs.config');
        Route::get('ftth/api/acs/devices', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsDevices'])->name('ftth.api.acs.devices');
        Route::get('ftth/api/acs/devices/{serialId}', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsDeviceDetail'])->where('serialId', '.*')->name('ftth.api.acs.device-detail');
        Route::post('ftth/api/acs/devices/{serialId}/wifi', [\App\Http\Controllers\FtthAdvancedController::class, 'setAcsWifi'])->where('serialId', '.*')->name('ftth.api.acs.set-wifi');
        Route::post('ftth/api/acs/devices/{serialId}/reboot', [\App\Http\Controllers\FtthAdvancedController::class, 'rebootAcsDevice'])->where('serialId', '.*')->name('ftth.api.acs.reboot');
        Route::post('ftth/api/acs/devices/{serialId}/pppoe', [\App\Http\Controllers\FtthAdvancedController::class, 'setAcsPppoe'])->where('serialId', '.*')->name('ftth.api.acs.set-pppoe');
        Route::post('ftth/api/acs/devices/{serialId}/factory-reset', [\App\Http\Controllers\FtthAdvancedController::class, 'factoryResetAcsDevice'])->where('serialId', '.*')->name('ftth.api.acs.factory-reset');
        Route::post('ftth/api/acs/devices/{serialId}/refresh', [\App\Http\Controllers\FtthAdvancedController::class, 'refreshAcsDevice'])->where('serialId', '.*')->name('ftth.api.acs.refresh');
        Route::delete('ftth/api/acs/devices/{serialId}', [\App\Http\Controllers\FtthAdvancedController::class, 'deleteAcsDevice'])->where('serialId', '.*')->name('ftth.api.acs.delete-device');
        Route::get('ftth/api/acs/presets', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsPresets'])->name('ftth.api.acs.presets');
        Route::get('ftth/api/acs/provisions', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsProvisions'])->name('ftth.api.acs.provisions');
        Route::get('ftth/api/acs/faults', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsFaults'])->name('ftth.api.acs.faults');
        Route::get('ftth/api/acs/files', [\App\Http\Controllers\FtthAdvancedController::class, 'getAcsFiles'])->name('ftth.api.acs.files');
        Route::post('ftth/api/acs/files', [\App\Http\Controllers\FtthAdvancedController::class, 'uploadAcsFile'])->name('ftth.api.acs.upload-file');
        Route::delete('ftth/api/acs/files/{filename}', [\App\Http\Controllers\FtthAdvancedController::class, 'deleteAcsFile'])->where('filename', '.*')->name('ftth.api.acs.delete-file');
        Route::match(['GET','POST'], 'ftth/settings/telegram', [\App\Http\Controllers\FtthAdvancedController::class, 'telegramSettings'])->name('ftth.settings.telegram');
        Route::post('ftth/settings/telegram/test', [\App\Http\Controllers\FtthAdvancedController::class, 'telegramTest'])->name('ftth.settings.telegram.test');
    });



    // Tiket (Fine-grained role authorization is handled inside TiketController)
    Route::resource('tiket', TiketController::class);
    Route::post('tiket/{tiket}/status', [TiketController::class, 'updateStatus'])->name('tiket.status');
    Route::post('tiket/{tiket}/assign-teknisi', [TiketController::class, 'assignTeknisi'])->name('tiket.assign-teknisi');
    Route::get('tiket/{tiket}/chats', [TiketController::class, 'getChats'])->name('tiket.chats');
    Route::post('tiket/{tiket}/chats', [TiketController::class, 'sendChat'])->name('tiket.chats.send');

    // Mikrotik
    Route::middleware('can:mikrotik_monitor')->group(function() {
        Route::get('mikrotik', [MikrotikController::class, 'index'])->name('mikrotik.index');
        Route::post('mikrotik', [MikrotikController::class, 'store'])->name('mikrotik.store');
        Route::get('mikrotik/{router}/profiles/{type}', [MikrotikController::class, 'getProfilesApi'])->name('mikrotik.profiles.api');
        Route::get('mikrotik/{router}/edit', [MikrotikController::class, 'edit'])->name('mikrotik.edit');
        Route::put('mikrotik/{router}', [MikrotikController::class, 'update'])->name('mikrotik.update');
        Route::delete('mikrotik/{router}', [MikrotikController::class, 'destroy'])->name('mikrotik.destroy');
        Route::get('mikrotik/{router}/sync', [MikrotikController::class, 'sync'])->name('mikrotik.sync');
        Route::get('mikrotik/{router}/stats', [MikrotikController::class, 'stats'])->name('mikrotik.stats');
    });

    // Kas Bon Pekerja
    Route::get('kas-bon', [\App\Http\Controllers\KasBonController::class, 'index'])->name('kas-bon.index');
    Route::post('kas-bon', [\App\Http\Controllers\KasBonController::class, 'store'])->name('kas-bon.store');
    Route::put('kas-bon/{id}', [\App\Http\Controllers\KasBonController::class, 'update'])->name('kas-bon.update');
    Route::patch('kas-bon/{id}/pay', [\App\Http\Controllers\KasBonController::class, 'pay'])->name('kas-bon.pay');
    Route::get('kas-bon/{id}/delete', [\App\Http\Controllers\KasBonController::class, 'destroy'])->name('kas-bon.destroy');

    // Management Pengguna
    Route::middleware('can:user_manage')->group(function() {
        Route::post('users/reset-customer-passwords', [UserController::class, 'resetCustomerPasswords'])->name('users.reset-customer-passwords');
        Route::resource('users', UserController::class);
    });

    // Laporan
    Route::middleware('can:report_view')->group(function() {
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/rekap-pembayaran', [LaporanController::class, 'rekapPembayaran'])->name('laporan.rekap-pembayaran');
        Route::get('laporan/rekap-pembayaran/export-excel', [LaporanController::class, 'exportExcel'])->name('laporan.rekap-pembayaran.export-excel');
    });

    // Profile (All can view)
    Route::get('profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');

    // Billing (Handled by the public proxy route above)

    Route::get('billing/{tagihan}/pay', [\App\Http\Controllers\PaymentController::class, 'getSnapToken'])->name('billing.pay');
    Route::post('billing', [\App\Http\Controllers\TagihanController::class, 'store'])->name('billing.store');
    Route::post('billing/{tagihan}/confirm', [\App\Http\Controllers\TagihanController::class, 'confirmPayment'])->name('billing.confirm');
    Route::post('billing/{tagihan}/verify', [\App\Http\Controllers\TagihanController::class, 'verifikasi'])->name('billing.verify');
    Route::get('billing/sync', [\App\Http\Controllers\TagihanController::class, 'generateMonthlyBills'])->name('billing.sync');
    Route::put('billing/{tagihan}/amount', [\App\Http\Controllers\TagihanController::class, 'updateAmount'])->name('billing.amount.update');
    Route::put('billing/{tagihan}', [\App\Http\Controllers\TagihanController::class, 'update'])->name('billing.update');
    Route::get('billing/{tagihan}/edit-bukti-bayar', [\App\Http\Controllers\TagihanController::class, 'showEditBuktiBayar'])->name('billing.edit-bukti-bayar');
    Route::put('billing/{tagihan}/edit-bukti-bayar', [\App\Http\Controllers\TagihanController::class, 'editBuktiBayar'])->name('billing.update-bukti-bayar');
    Route::delete('billing/delete-all', [\App\Http\Controllers\TagihanController::class, 'deleteAll'])->name('billing.delete-all');
    Route::delete('billing/{tagihan}', [\App\Http\Controllers\TagihanController::class, 'destroy'])->name('billing.destroy');
    Route::get('billing/delete-all-direct', [\App\Http\Controllers\TagihanController::class, 'deleteAllDirect'])->name('billing.delete-all-direct');
    Route::get('billing/{tagihan}/delete-direct', [\App\Http\Controllers\TagihanController::class, 'destroyDirect'])->name('billing.destroy-direct');
    Route::get('billing/{tagihan}/receipt', [\App\Http\Controllers\TagihanController::class, 'downloadReceipt'])->name('billing.receipt.pdf');
    Route::post('billing/{tagihan}/cash', [\App\Http\Controllers\TagihanController::class, 'payCash'])->name('billing.pay-cash');
    Route::post('billing/{tagihan}/send-receipt-wa', [\App\Http\Controllers\TagihanController::class, 'sendReceiptWa'])->name('billing.send-receipt-wa');
    
    // Upgrade Paket WiFi
    Route::get('upgrade-paket', [\App\Http\Controllers\UpgradePaketController::class, 'index'])->name('upgrade-paket.index');
    Route::post('upgrade-paket/request', [\App\Http\Controllers\UpgradePaketController::class, 'requestUpgrade'])->name('upgrade-paket.request');
    Route::post('upgrade-paket/admin-upgrade', [\App\Http\Controllers\UpgradePaketController::class, 'adminUpgrade'])->name('upgrade-paket.admin-upgrade');
    Route::post('upgrade-paket/{upgrade}/cancel', [\App\Http\Controllers\UpgradePaketController::class, 'cancelUpgrade'])->name('upgrade-paket.cancel');

    // Tutorial Modem & WiFi
    Route::get('tutorial', [\App\Http\Controllers\TutorialController::class, 'index'])->name('tutorial.index');
    Route::get('tutorial/{tutorial:slug}', [\App\Http\Controllers\TutorialController::class, 'show'])->name('tutorial.show');
    
    // Admin Tutorial Management
    Route::get('admin/tutorial', [\App\Http\Controllers\TutorialController::class, 'adminIndex'])->name('tutorial.admin.index');
    Route::get('admin/tutorial/create', [\App\Http\Controllers\TutorialController::class, 'create'])->name('tutorial.create');
    Route::post('admin/tutorial', [\App\Http\Controllers\TutorialController::class, 'store'])->name('tutorial.store');
    Route::get('admin/tutorial/{tutorial}/edit', [\App\Http\Controllers\TutorialController::class, 'edit'])->name('tutorial.edit');
    // We can use PUT/PATCH or POST
    Route::put('admin/tutorial/{tutorial}', [\App\Http\Controllers\TutorialController::class, 'update'])->name('tutorial.update');
    Route::delete('admin/tutorial/{tutorial}', [\App\Http\Controllers\TutorialController::class, 'destroy'])->name('tutorial.destroy');
    Route::post('admin/tutorial/{tutorial}/toggle-publish', [\App\Http\Controllers\TutorialController::class, 'togglePublish'])->name('tutorial.toggle-publish');
    Route::post('admin/tutorial/upload-image', [\App\Http\Controllers\TutorialController::class, 'uploadImage'])->name('tutorial.upload-image');

    // Katalog Modem
    Route::get('modem', [\App\Http\Controllers\ModemController::class, 'index'])->name('modem.index');
    Route::get('modem/{modem}', [\App\Http\Controllers\ModemController::class, 'show'])->name('modem.show');

    // Admin Modem Management
    Route::middleware('can:pelanggan_manage')->group(function() {
        Route::get('admin/modem', [\App\Http\Controllers\ModemController::class, 'adminIndex'])->name('modem.admin.index');
        Route::get('admin/modem/create', [\App\Http\Controllers\ModemController::class, 'create'])->name('modem.create');
        Route::post('admin/modem', [\App\Http\Controllers\ModemController::class, 'store'])->name('modem.store');
        Route::get('admin/modem/{modem}/edit', [\App\Http\Controllers\ModemController::class, 'edit'])->name('modem.edit');
        Route::put('admin/modem/{modem}', [\App\Http\Controllers\ModemController::class, 'update'])->name('modem.update');
        Route::delete('admin/modem/{modem}', [\App\Http\Controllers\ModemController::class, 'destroy'])->name('modem.destroy');
    });
    
    // Settings
    Route::get('settings/payment', [\App\Http\Controllers\TagihanController::class, 'settings'])->name('settings.payment');
    Route::post('settings/payment', [\App\Http\Controllers\TagihanController::class, 'updateSettings'])->name('settings.payment.update');
    Route::post('settings/billing/run-isolir', [\App\Http\Controllers\TagihanController::class, 'runIsolirSync'])->name('settings.billing.isolir');
    Route::post('settings/billing/clear-phones', [\App\Http\Controllers\TagihanController::class, 'clearAllPhoneNumbers'])->name('settings.billing.clear-phones');

    // Inventory
    Route::middleware('can:inventory_manage')->group(function() {
        Route::get('inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('inventory', [InventoryController::class, 'store'])->name('inventory.store');
        Route::get('inventory/{inventory}', [InventoryController::class, 'show'])->name('inventory.show');
        Route::put('inventory/{inventory}', [InventoryController::class, 'update'])->name('inventory.update');
        Route::delete('inventory/{inventory}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
        Route::post('inventory/{inventory}/assign', [InventoryController::class, 'assign'])->name('inventory.assign');
    });

    // Scanner
    Route::get('scan', [ScanController::class, 'index'])->name('scan.index');
    Route::post('scan/process', [ScanController::class, 'process'])->name('scan.process');

    // Whatsapp Multi-Device Management
    Route::middleware('can:user_manage')->group(function() {
        Route::get('whatsapp/manager', [\App\Http\Controllers\WhatsappManagementController::class, 'index'])->name('whatsapp.index');
        Route::post('whatsapp/session/start', [\App\Http\Controllers\WhatsappManagementController::class, 'start'])->name('whatsapp.session.start');
        Route::post('whatsapp/session/pairing', [\App\Http\Controllers\WhatsappManagementController::class, 'pairing'])->name('whatsapp.session.pairing');
        Route::post('whatsapp/session/stop', [\App\Http\Controllers\WhatsappManagementController::class, 'stop'])->name('whatsapp.session.stop');
        Route::post('whatsapp/bot/start', [\App\Http\Controllers\WhatsappManagementController::class, 'startBotProcess'])->name('whatsapp.bot.start');
        
        // WA Status Scheduler
        Route::get('whatsapp/status-schedule', [\App\Http\Controllers\WaStatusScheduleController::class, 'index'])->name('whatsapp.status.index');
        Route::post('whatsapp/status-schedule', [\App\Http\Controllers\WaStatusScheduleController::class, 'store'])->name('whatsapp.status.store');
        Route::post('whatsapp/status-schedule/{schedule}/publish', [\App\Http\Controllers\WaStatusScheduleController::class, 'publishImmediately'])->name('whatsapp.status.publish-now');
        Route::delete('whatsapp/status-schedule/{schedule}', [\App\Http\Controllers\WaStatusScheduleController::class, 'destroy'])->name('whatsapp.status.destroy');
    });

    // Chatbot Management
    Route::get('bot/responses', [\App\Http\Controllers\BotResponseController::class, 'index'])->name('bot.index');
    Route::post('bot/responses', [\App\Http\Controllers\BotResponseController::class, 'store'])->name('bot.store');
    Route::put('bot/responses/{bot}', [\App\Http\Controllers\BotResponseController::class, 'update'])->name('bot.update');
    Route::delete('bot/responses/{bot}', [\App\Http\Controllers\BotResponseController::class, 'destroy'])->name('bot.destroy');

    // =========================================================
    // Notifikasi In-App (+ SSE Real-time Stream)
    // =========================================================
    Route::get('notifications/stream', [NotificationController::class, 'stream'])->name('notifications.stream');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
    Route::post('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    // =========================================================
    // Chat Inbox (Pesan Tiket Gangguan)
    // =========================================================
    Route::get('chat-inbox', [\App\Http\Controllers\ChatInboxController::class, 'index'])->name('chat-inbox.index');
    Route::post('chat-inbox/read-all', [\App\Http\Controllers\ChatInboxController::class, 'readAll'])->name('chat-inbox.read-all');
    Route::post('chat-inbox/read-tiket/{id_tiket}', [\App\Http\Controllers\ChatInboxController::class, 'readTiket'])->name('chat-inbox.read-tiket');

    // System Logs
    Route::get('system/logs', [\App\Http\Controllers\LogController::class, 'index'])->name('logs.index');
    Route::get('system/logs/fetch', [\App\Http\Controllers\LogController::class, 'fetch'])->name('logs.fetch');
    Route::post('system/logs/clear', [\App\Http\Controllers\LogController::class, 'clear'])->name('logs.clear');

    // Kepegawaian & Absensi Pegawai
    Route::get('absensi', [\App\Http\Controllers\AttendanceController::class, 'index'])->name('absensi.index');
    Route::get('absensi/today', [\App\Http\Controllers\AttendanceController::class, 'today'])->name('absensi.today');
    
    Route::middleware('can:user_manage')->group(function() {
        Route::get('absensi/settings', [\App\Http\Controllers\AttendanceController::class, 'showSettings'])->name('absensi.settings');
        Route::post('absensi/settings', [\App\Http\Controllers\AttendanceController::class, 'storeSettings'])->name('absensi.settings.store');
        Route::post('absensi/manual', [\App\Http\Controllers\AttendanceController::class, 'storeManual'])->name('absensi.store-manual');
        Route::post('absensi/manual-batch', [\App\Http\Controllers\AttendanceController::class, 'storeManualBatch'])->name('absensi.store-manual-batch');
        Route::post('absensi/date-schedule', [\App\Http\Controllers\AttendanceController::class, 'storeDateSchedule'])->name('absensi.date-schedule.store');
        Route::delete('absensi/date-schedule', [\App\Http\Controllers\AttendanceController::class, 'destroyDateSchedule'])->name('absensi.date-schedule.destroy');
        Route::post('absensi/import', [\App\Http\Controllers\AttendanceController::class, 'importCsv'])->name('absensi.import');
        Route::get('absensi/export', [\App\Http\Controllers\AttendanceController::class, 'exportExcel'])->name('absensi.export');
        Route::get('absensi/pdf', [\App\Http\Controllers\AttendanceController::class, 'exportPdf'])->name('absensi.pdf');
        Route::delete('absensi/{id}', [\App\Http\Controllers\AttendanceController::class, 'destroy'])->name('absensi.destroy');
        // Kirim rekap absensi manual via WA
        Route::post('absensi/send-rekap', [\App\Http\Controllers\AttendanceController::class, 'sendRekapManual'])->name('absensi.send-rekap');

        // Keuangan & PSB
        Route::get('keuangan', [\App\Http\Controllers\KeuanganController::class, 'index'])->name('keuangan.index');
        Route::post('keuangan', [\App\Http\Controllers\KeuanganController::class, 'store'])->name('keuangan.store');
        Route::put('keuangan/{id}', [\App\Http\Controllers\KeuanganController::class, 'update'])->name('keuangan.update');
        Route::delete('keuangan/{id}', [\App\Http\Controllers\KeuanganController::class, 'destroy'])->name('keuangan.destroy');
    });

});

// ADMS (Solution X105 Fingerprint Machine Push Protocol)
Route::any('iclock/cdata', [\App\Http\Controllers\AttendanceController::class, 'handleADMS']);
Route::any('iclock/getrequest', [\App\Http\Controllers\AttendanceController::class, 'handleADMS']);
Route::post('absensi/webhook', [\App\Http\Controllers\AttendanceController::class, 'handleGeneric']);

// Public Wifi Registration routes (Guest access)
Route::get('register-wifi', [\App\Http\Controllers\PublicRegistrationController::class, 'showForm'])->name('public.register');
Route::post('register-wifi', [\App\Http\Controllers\PublicRegistrationController::class, 'register'])->name('public.register.store');
Route::get('register-wifi/success', [\App\Http\Controllers\PublicRegistrationController::class, 'success'])->name('public.register.success');

// Whatsapp Hook (Outside auth because it's called by the Node.js bot)
Route::post('whatsapp/webhook', [\App\Http\Controllers\WhatsappController::class, 'webhook']);
Route::post('whatsapp/status', [\App\Http\Controllers\WhatsappController::class, 'status']);
Route::post('whatsapp/train', [\App\Http\Controllers\WhatsappController::class, 'train']);

// Midtrans Callback (Outside auth)
Route::post('payment/callback', [\App\Http\Controllers\PaymentController::class, 'callback'])->name('payment.callback');

// Quick Scan Pay
Route::get('pay/{kode_pelanggan}', [\App\Http\Controllers\PaymentController::class, 'payById'])->name('payment.by-id');

// Temporary route to get WA Groups from local bot on aaPanel
Route::get('get-wa-groups', function() {
    $port = env('BOT_PORT', 3000);
    $secret = env('BOT_SECRET'); // Ambil dari .env

    if (!$secret) {
        return response()->json(['error' => 'Bot secret not configured'], 500);
    }
    
    // Parse .env directly in case config is cached
    if (file_exists(base_path('.env'))) {
        $envContent = file_get_contents(base_path('.env'));
        if (preg_match('/^BOT_PORT\s*=\s*(.+)$/m', $envContent, $matches)) {
            $port = trim($matches[1], "\"' \r\n");
        }
        if (preg_match('/^BOT_SECRET\s*=\s*(.+)$/m', $envContent, $matches)) {
            $secret = trim($matches[1], "\"' \r\n");
        }
    }

    $url = "http://127.0.0.1:$port/groups";
    
    try {
        $response = \Illuminate\Support\Facades\Http::timeout(15)
            ->withHeaders(['X-Bot-Secret' => $secret])
            ->get($url);
        
        if (!$response->successful()) {
            return response()->json([
                'status' => 'failed_response',
                'target_url' => $url,
                'http_status' => $response->status(),
                'body' => $response->body()
            ]);
        }
        
        $groups = $response->json();
        
        if (request()->has('json')) {
            return response()->json($groups);
        }
        
        // RENDER PREMIUM HTML
        $groupCards = '';
        if (empty($groups)) {
            $groupCards = '<div class="empty-state">Tidak ada grup WhatsApp yang ditemukan untuk sesi ini.</div>';
        } else {
            foreach ($groups as $g) {
                $escapedId = e($g['id']);
                $escapedSubject = e($g['subject']);
                $groupCards .= "
                <div class=\"group-card\">
                    <div class=\"group-info\">
                        <div class=\"group-name\">{$escapedSubject}</div>
                        <div class=\"group-id\">{$escapedId}</div>
                    </div>
                    <button class=\"btn-copy\" onclick=\"copyToClipboard('{$escapedId}')\">
                        Salin ID
                    </button>
                </div>";
            }
        }
        
        return "
<!DOCTYPE html>
<html lang=\"id\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>Daftar Grup WhatsApp - Rozitech</title>
    <link href=\"https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap\" rel=\"stylesheet\">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --accent: #10b981;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --border: rgba(255, 255, 255, 0.08);
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-main);
            min-height: 100vh;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            box-sizing: border-box;
        }
        .container {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }
        .logo-area {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--primary) 0%, #8b5cf6 100%);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem auto;
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.3);
        }
        .logo-area svg {
            width: 32px;
            height: 32px;
            fill: white;
        }
        h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
            background: linear-gradient(to right, #f8fafc, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            color: var(--text-muted);
            font-size: 0.95rem;
        }
        .group-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        .group-card {
            background: rgba(15, 23, 42, 0.4);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .group-card:hover {
            transform: translateY(-2px);
            border-color: rgba(99, 102, 241, 0.4);
            background: rgba(15, 23, 42, 0.6);
            box-shadow: 0 10px 20px -10px rgba(0, 0, 0, 0.3);
        }
        .group-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        .group-name {
            font-weight: 600;
            font-size: 1.1rem;
            color: #ffffff;
        }
        .group-id {
            font-family: monospace;
            font-size: 0.85rem;
            color: var(--text-muted);
            background: rgba(255, 255, 255, 0.05);
            padding: 0.2rem 0.5rem;
            border-radius: 6px;
            width: fit-content;
        }
        .btn-copy {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-hover) 100%);
            border: none;
            color: white;
            padding: 0.6rem 1.2rem;
            border-radius: 12px;
            font-weight: 500;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }
        .btn-copy:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
        }
        .btn-copy:active {
            transform: scale(0.95);
        }
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }
        .toast {
            position: fixed;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--accent);
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 99px;
            font-weight: 500;
            box-shadow: 0 10px 25px -5px rgba(16, 185, 129, 0.4);
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 100;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
        }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <div class=\"logo-area\">
                <svg viewBox=\"0 0 24 24\"><path d=\"M12 2C6.48 2 2 6.48 2 12c0 2.17.76 4.16 2.03 5.74L3 21l3.35-1.03C7.91 20.65 9.89 21 12 21c5.52 0 10-4.48 10-10S17.52 2 12 2zm1 14h-2v-2h2v2zm0-4h-2V7h2v5z\"/></svg>
            </div>
            <h1>Daftar Grup WhatsApp</h1>
            <div class=\"subtitle\">Salin ID Grup di bawah untuk ditempel pada file .env di aaPanel</div>
        </div>
        
        <div class=\"group-list\">
            {$groupCards}
        </div>
    </div>
    
    <div class=\"toast\" id=\"toast\">ID Grup berhasil disalin!</div>
    
    <script>
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(() => {
                const toast = document.getElementById('toast');
                toast.classList.add('show');
                setTimeout(() => {
                    toast.classList.remove('show');
                }, 2000);
            });
        }
    </script>
</body>
</html>
";
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'connection_error',
            'target_url' => $url,
            'message' => $e->getMessage()
        ]);
    }
});