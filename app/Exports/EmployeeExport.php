<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithColumnFormatting
{
    public function collection()
    {
        return User::with('biodata')->whereIn('role', ['pegawai', 'admin_unit', 'yayasan'])->get();
    }

    public function headings(): array
    {
        return [
            'NAMA PEGAWAI',
            'USERNAME',
            'EMAIL',
            'PIN FINGERPRINT',
            'KODE PEGAWAI',
            'UNIT',
            'ROLE',
            'NIK (KTP)',
            'NO WA (Ex: 628xxx)',
            'TEMPAT LAHIR',
            'TANGGAL LAHIR (YYYY-MM-DD)',
            'JENIS KELAMIN',
            'AGAMA',
            'STATUS PEGAWAI'
        ];
    }

    public function map($user): array
    {
        return [
            $user->name,
            $user->username,
            $user->email,
            $user->pin_fingerspot ? (string) $user->pin_fingerspot : '',
            $user->biodata?->kode_pegawai ?? '',
            $user->unit,
            $user->role,
            $user->biodata?->nik ? (string) $user->biodata->nik : '',
            $user->biodata?->no_wa ? (string) $user->biodata->no_wa : '',
            $user->biodata?->tempat_lahir ?? '',
            $user->biodata?->tgl_lahir ? $user->biodata->tgl_lahir->format('Y-m-d') : '',
            $user->biodata?->jenis_kelamin ?? '',
            $user->biodata?->agama ?? '',
            $user->biodata?->status_pegawai ?? 'Aktif'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'D' => NumberFormat::FORMAT_TEXT, // PIN Fingerprint
            'H' => NumberFormat::FORMAT_TEXT, // NIK
            'I' => NumberFormat::FORMAT_TEXT, // NO WA
        ];
    }
}
