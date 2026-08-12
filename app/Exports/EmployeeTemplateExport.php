<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class EmployeeTemplateExport implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new EmployeeTemplateDataSheet(),
            new EmployeeTemplateUnitSheet(),
            new EmployeeTemplateRoleSheet(),
            new EmployeeTemplateAgamaSheet(),
        ];
    }
}

class EmployeeTemplateDataSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize, WithColumnFormatting
{
    public function collection()
    {
        return collect([
            [
                'MUHAMMAD MINAN', 'minan', 'minan@pmu.com', '123456', '101', 'PMU-001', 'SMP', 'pegawai', '3501xxxxxxxxxxxx', '628123456789', 'Gresik', '1995-10-10', 'Laki-laki', 'Islam', 'Aktif'
            ]
        ]);
    }

    public function headings(): array
    {
        return [
            'nama_pegawai', 'username', 'email', 'password', 'pin_fingerprint', 'kode_pegawai', 'unit', 'role', 'nik_ktp', 'no_wa_ex_628xxx', 'tempat_lahir', 'tanggal_lahir_yyyy_mm_dd', 'jenis_kelamin', 'agama', 'status_pegawai'
        ];
    }

    public function title(): string
    {
        return 'Data Pegawai';
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_TEXT, // pin_fingerprint
            'I' => NumberFormat::FORMAT_TEXT, // nik_ktp
            'J' => NumberFormat::FORMAT_TEXT, // no_wa_ex_628xxx
        ];
    }
}

class EmployeeTemplateUnitSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return \App\Models\Unit::select('id', 'nama', 'keterangan')->orderBy('id')->get();
    }

    public function headings(): array
    {
        return [
            'ID Unit', 'Nama Unit', 'Keterangan'
        ];
    }

    public function title(): string
    {
        return 'Referensi Unit';
    }
}

class EmployeeTemplateRoleSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return collect([
            ['pegawai', 'Pegawai / Guru'],
            ['admin_unit', 'Admin Unit (contoh: Admin MA)'],
            ['yayasan', 'Yayasan (Pimpinan / Pengurus)'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Role', 'Keterangan'
        ];
    }

    public function title(): string
    {
        return 'Referensi Role';
    }
}

class EmployeeTemplateAgamaSheet implements FromCollection, WithTitle, WithHeadings, ShouldAutoSize
{
    public function collection()
    {
        return collect([
            ['Islam'],
            ['Kristen'],
            ['Katolik'],
            ['Hindu'],
            ['Budha'],
            ['Konghucu'],
        ]);
    }

    public function headings(): array
    {
        return [
            'Agama'
        ];
    }

    public function title(): string
    {
        return 'Referensi Agama';
    }
}
