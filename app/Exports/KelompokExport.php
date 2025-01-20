<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        // Query data kelompok
        $kelompokData = DB::table('kelompok')
            ->join('users as mahasiswa', 'kelompok.user_id', '=', 'mahasiswa.id')
            ->join('users as dosen', 'kelompok.dosen_id', '=', 'dosen.id')
            ->where('kelompok.tahun_ajaran', $this->tahunAjaran)
            ->where('kelompok.nama_proyek', $this->namaProyek)
            ->select(
                'kelompok.tahun_ajaran',
                'kelompok.nama_proyek',
                'mahasiswa.name as mahasiswa_name',
                'mahasiswa.nim',
                DB::raw("CONCAT(dosen.name, ' - ', dosen.kode_dosen) as dosen_manajer"), // Menggabungkan nama dosen dan kode_dosen
                'kelompok.kelompok'
            )
            // ->orderBy('mahasiswa.name', 'asc')
            ->orderBy('kelompok.kelompok', 'asc') // Urutkan berdasarkan kelompok
            ->get();

        // Jika data kelompok ditemukan, tambahkan kolom No secara dinamis
        if ($kelompokData->isNotEmpty()) {
            $dataWithNo = $kelompokData->map(function ($item, $index) {
                return (object) array_merge(['no' => $index + 1], (array) $item);
            });

            return $dataWithNo;
        }

        // Jika data kosong, log pesan dan buat template data kosong
        Log::info("Tidak ada data kelompok untuk tahun ajaran {$this->tahunAjaran} dan proyek {$this->namaProyek}.");

        $mahasiswa = DB::table('users')
            ->where('role', 'mahasiswa')
            ->select('name', 'nim')
            ->orderBy('name', 'asc')
            ->get();

        $data = [];
        foreach ($mahasiswa as $index => $mahasiswaItem) {
            $data[] = [
                'no' => $index + 1,
                'tahun_ajaran' => $this->tahunAjaran,
                'nama_proyek' => $this->namaProyek,
                'name' => $mahasiswaItem->name,
                'nim' => $mahasiswaItem->nim,
                'kode_dosen' => '', // Kosong untuk dropdown
                'kelompok' => '', // Kosong
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['No', 'Tahun Ajaran', 'Proyek', 'Nama', 'NIM', 'Dosen Manajer', 'Kelompok'];
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

                // Membuat data validation untuk dropdown di kolom F
                $validation = new DataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('"' . implode(',', $dosenList) . '"');

                // Menambahkan dropdown pada setiap sel di kolom F
                for ($row = 2; $row <= $lastRow; $row++) {
                    $worksheet->getCell('F' . $row)->setDataValidation(clone $validation);
                }

                // Tambahkan border untuk semua sel
                $worksheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Membuat header menjadi tebal dan rata tengah
                $worksheet->getStyle('A1:G1')->getFont()->setBold(true);
                $worksheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom No ke tengah
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom lainnya ke kiri
                $worksheet->getStyle('B2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}
