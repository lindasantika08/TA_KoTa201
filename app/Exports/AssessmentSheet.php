<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class AssessmentSheet implements FromCollection, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Ambil data dari database
        $data = Assessment::select('type', 'pertanyaan', 'aspek', 'kriteria')
            ->get()
            ->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'type' => $item->type,
                    'pertanyaan' => $item->pertanyaan,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                ];
            })
            ->toArray();

        // Tambahkan 10 baris template kosong di bawah data yang ada
        for ($i = 0; $i < 10; $i++) {
            $data[] = [
                'no' => count($data) + 1,
                'type' => '',
                'pertanyaan' => '',
                'aspek' => '',
                'kriteria' => '',
            ];
        }

        return collect($data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Type',
            'Pertanyaan',
            'Aspek',
            'Kriteria'
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow(); // Mengambil baris terakhir yang ada

                // Tambahkan border untuk semua sel termasuk header dan data
                $worksheet->getStyle('A1:E' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Membuat header menjadi tebal
                $worksheet->getStyle('A1:E1')->getFont()->setBold(true);

                // Meratakan header ke tengah
                $worksheet->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom No ke tengah
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Meratakan kolom lainnya ke kiri (Type, Pertanyaan, Aspek, Kriteria)
                $worksheet->getStyle('B2:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Menyesuaikan ukuran kolom secara otomatis
                foreach (range('A', 'E') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Menambahkan dropdown pada kolom 'Type' (Kolom B) mulai dari baris kedua hingga terakhir
                for ($i = 2; $i <= $lastRow; $i++) {
                    $validation = $worksheet->getCell("B$i")->getDataValidation();
                    $validation->setType(DataValidation::TYPE_LIST);
                    $validation->setErrorStyle(DataValidation::STYLE_INFORMATION);
                    $validation->setAllowBlank(false);
                    $validation->setShowDropDown(true);
                    $validation->setFormula1('"selfAssessment,peerAssessment"');
                }
            }
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Assessment';
    }
}
