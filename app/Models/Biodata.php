<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Biodata extends Model
{
    protected $fillable = [
        'user_id', 'kode_pegawai', 'nik', 'tempat_lahir', 'tgl_lahir', 'jenis_kelamin', 
        'alamat', 'agama', 'gol_darah', 'no_wa', 'rekening', 'tgl_masuk', 
        'status_sertifikat', 'status_pegawai', 'purna_tugas',
        'status_pernikahan', 'tgl_menikah', 'jumlah_anak',
        'pendidikan_terakhir', 'riwayat_pendidikan', 'keluarga'
    ];

    protected $casts = [
        'riwayat_pendidikan' => 'array',
        'keluarga' => 'array',
        'tgl_lahir' => 'date',
        'tgl_masuk' => 'date',
        'tgl_menikah' => 'date',
        'purna_tugas' => 'date',
    ];

    public function user() { return $this->belongsTo(User::class); }

    public function getAge()
    {
        if (!$this->tgl_lahir) return '-';
        return $this->tgl_lahir->age;
    }

    public function getPurnaTugas()
    {
        if (!$this->tgl_lahir) return '-';
        $year = $this->tgl_lahir->year + 60;
        return $year . '/' . ($year + 1);
    }

    public function hasSertifikat()
    {
        if (!$this->user) return false;
        
        return $this->user->dokumens()
            ->where('tipe', 'Sertifikat Pendidik')
            ->whereIn('status', ['Valid', 'Disetujui'])
            ->exists();
    }
}
