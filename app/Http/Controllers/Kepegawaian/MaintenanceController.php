<?php

namespace App\Http\Controllers\Kepegawaian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Artisan;
use App\Models\User;

class MaintenanceController extends Controller
{
    /**
     * Backup Database
     * Menggunakan perintah mysqldump atau PHP Native fallback untuk export SQL
     */
    public function backup()
    {
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');
        $dbHost = config('database.connections.mysql.host');
        
        $filename = "Backup_PMU_Bungah_" . date('Y-m-d_His') . ".sql";
        
        // Coba pakai mysqldump (berfungsi di Laragon/XAMPP jika path terdaftar)
        $command = "mysqldump --user=$dbUser --password=$dbPass --host=$dbHost $dbName > " . storage_path("app/$filename");
        
        try {
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                // Fallback jika mysqldump gagal (PHP Native Simple Export)
                return $this->fallbackBackup($dbName);
            }

            return response()->download(storage_path("app/$filename"))->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses backup: ' . $e->getMessage());
        }
    }

    private function fallbackBackup($dbName)
    {
        $tables = DB::select('SHOW TABLES');
        $sql = "-- PMU Bungah Database Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . $dbName};
            
            // Create Table
            $createTable = DB::select("SHOW CREATE TABLE $tableName")[0]->{'Create Table'};
            $sql .= "DROP TABLE IF EXISTS `$tableName`;\n$createTable;\n\n";
            
            // Insert Data
            $rows = DB::table($tableName)->get();
            foreach ($rows as $row) {
                $values = array_map(function($v) { return is_null($v) ? 'NULL' : "'" . addslashes($v) . "'"; }, (array)$row);
                $sql .= "INSERT INTO `$tableName` VALUES (" . implode(', ', $values) . ");\n";
            }
            $sql .= "\n\n";
        }

        return response($sql)
            ->header('Content-Type', 'application/sql')
            ->header('Content-Disposition', 'attachment; filename="Backup_PMU_Bungah_' . date('Y-m-d_Hmi') . '.sql"');
    }

    /**
     * Reset Database
     * Menghapus semua data kecuali akun Yayasan
     */
    public function reset(Request $request)
    {
        // Security check
        if ($request->confirm_text !== 'KONFIRMASI RESET') {
            return back()->with('error', 'Teks konfirmasi salah. Gagal melakukan reset.');
        }

        try {
            DB::beginTransaction();

            // 1. Ambil ID semua akun Yayasan untuk dikecualikan
            $yayasanIds = User::where('role', 'yayasan')->pluck('id')->toArray();

            // 2. Hapus data transaksi utama
            DB::table('absensis')->truncate();
            DB::table('izins')->truncate();
            DB::table('cutis')->truncate();
            DB::table('sk_permohonans')->truncate();
            DB::table('dokumens')->delete(); // delete krn mungkin ada relasi file
            DB::table('rpps')->truncate();
            DB::table('user_schedules')->truncate();

            // 3. Hapus data profil & riwayat
            DB::table('riwayat_pegawais')->truncate();
            
            // Biodata harus di-delete krn foreign key ke user (biasanya cascade, tapi kita manual agar aman)
            DB::table('biodatas')->whereNotIn('user_id', $yayasanIds)->delete();

            // 4. Hapus User kecuali Yayasan
            User::whereNotIn('id', $yayasanIds)->delete();

            DB::commit();
            
            return back()->with('success', 'Database berhasil di-reset. Semua data pegawai dan log telah dihapus, kecuali akun Yayasan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat reset: ' . $e->getMessage());
        }
    }
}
