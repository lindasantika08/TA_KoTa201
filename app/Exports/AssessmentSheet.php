<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class AssessmentSheet implements FromCollection, WithHeadings, WithEvents, ShouldAutoSize
{
    protected $tahunAjaran;
    protected $namaProyek;

    public function __construct($tahunAjaran, $namaProyek)
    {
        $this->tahunAjaran = $tahunAjaran;
        $this->namaProyek = $namaProyek;
    }

    /**
     * Ambil data dari database atau buat template kosong jika tidak ada data
     */
    public function collection()
    {
        // Cek apakah ada data di tabel assessment dengan tahun_ajaran dan nama_proyek
        $assessments = Assessment::where('tahun_ajaran', $this->tahunAjaran)
            ->where('nama_proyek', $this->namaProyek)
            ->get();

        if ($assessments->isEmpty()) {
            // Jika tidak ada data, buat 10 baris template kosong
            $data = [];
            for ($i = 1; $i <= 10; $i++) {
                $data[] = [
                    'no' => $i,
                    'tahun_ajaran' => $this->tahunAjaran,
                    'nama_proyek' => $this->namaProyek,
                    'type' => '',
                    'pertanyaan' => '',
                    'aspek' => '',
                    'kriteria' => '',
                ];
            }
        } else {
            // Jika ada data, map data yang ada
            $data = $assessments->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'tahun_ajaran' => $this->tahunAjaran,
                    'nama_proyek' => $this->namaProyek,
                    'type' => $item->type,
                    'pertanyaan' => $item->pertanyaan,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                ];
            })->toArray();
        }

        return collect($data);
    }

    /**
     * Judul kolom di Excel
     */
    public function headings(): array
    {
        return [
            'No',
            'Tahun Ajaran',
            'Nama Proyek',
            'Type',
            'Pertanyaan',
            'Aspek',
            'Kriteria',
        ];
    }

    /**
     * Menambahkan format dan event setelah sheet selesai diisi
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow();

                // Tambahkan border untuk semua sel termasuk header dan data
                $worksheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Membuat header menjadi tebal
                $worksheet->getStyle('A1:G1')->getFont()->setBold(true);

                // Meratakan header ke tengah
                $worksheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom No ke tengah
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom lainnya ke kiri
                $worksheet->getStyle('B2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Menyesuaikan ukuran kolom secara otomatis
                foreach (range('A', 'G') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Menambahkan dropdown pada kolom 'Type' (Kolom D) mulai dari baris kedua hingga terakhir
                for ($i = 2; $i <= $lastRow; $i++) {
                    $validation = $worksheet->getCell("D$i")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"selfAssessment,peerAssessment"');
                }
            }
        ];
    }
}
