<?php

namespace App\Exports;

use App\Models\Kelompok;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class KelompokExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $tahunAjaran;
    protected $namaProyek;

    public function __construct($tahunAjaran, $namaProyek)
    {
        $this->tahunAjaran = $tahunAjaran;
        $this->namaProyek = $namaProyek;
    }

    public function collection()
    {
        // Ambil semua user yang memiliki role mahasiswa
        $mahasiswa = DB::table('users')
            ->where('role', 'mahasiswa')
            ->select('name', 'nim')
            ->get();

        // Ambil semua dosen dengan role dosen
        $dosen = DB::table('users')
            ->where('role', 'dosen')
            ->select('name', 'kode_dosen')
            ->get();

        // Siapkan data untuk template export
        $data = [];
        $no = 1;

        // Loop melalui mahasiswa dan buat template dengan kelompok kosong
        foreach ($mahasiswa as $mahasiswaItem) {
            $data[] = [
                'no' => $no++,
                'tahun_ajaran' => $this->tahunAjaran,
                'proyek' => $this->namaProyek,
                'name' => $mahasiswaItem->name, // Mengambil name dari mahasiswa
                'nim' => $mahasiswaItem->nim,   // Mengambil nim dari mahasiswa
                'kode_dosen' => '', // Kosongkan untuk diisi dengan dropdown
                'kelompok' => '', // Kolom kelompok tetap kosong
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['No', 'Tahun Ajaran', 'Proyek', 'Name', 'NIM', 'Dosen Manager', 'Kelompok'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow();

                // Membuat daftar dosen untuk dropdown
                $dosenList = DB::table('users')
                    ->where('role', 'dosen')
                    ->pluck('name', 'kode_dosen')
                    ->map(function ($name, $kode_dosen) {
                        return $name . ' - ' . $kode_dosen; // Format nama dosen dan kode_dosen
                    })
                    ->toArray();

                // Membuat data validation untuk dropdown di kolom Kode Dosen
                $validation = new DataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('"' . implode(',', $dosenList) . '"');

                // Menambahkan dropdown pada setiap sel di kolom 'Kode Dosen'
                for ($row = 2; $row <= $lastRow; $row++) {
                    $worksheet->getCell('F' . $row)->setDataValidation(clone $validation);
                }

                // Tambahkan border untuk semua sel
                $worksheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Membuat header menjadi tebal
                $worksheet->getStyle('A1:G1')->getFont()->setBold(true);

                // Meratakan header ke tengah
                $worksheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom No ke tengah
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom lainnya ke kiri
                $worksheet->getStyle('B2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}
