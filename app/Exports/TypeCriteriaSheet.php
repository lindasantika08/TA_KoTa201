<?php

namespace App\Exports;

use App\Models\type_criteria;
use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class TypeCriteriaSheet implements FromCollection, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
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
        $typeCriterias = Assessment::select(
            'assessment.aspek',
            'assessment.kriteria',
            'type_criteria.bobot_1',
            'type_criteria.bobot_2',
            'type_criteria.bobot_3',
            'type_criteria.bobot_4',
            'type_criteria.bobot_5'
        )
            ->where('assessment.tahun_ajaran', $this->tahunAjaran)
            ->where('assessment.nama_proyek', $this->namaProyek)
            ->join('type_criteria', function ($join) {
                $join->on('assessment.aspek', '=', 'type_criteria.aspek')
                    ->on('assessment.kriteria', '=', 'type_criteria.kriteria');
            })
            ->get();

        if ($typeCriterias->isEmpty()) {
            // Jika tidak ada data, buat 5 baris template kosong
            $data = collect(range(1, 5))->map(function ($i) {
                return [
                    'no' => $i,
                    'aspek' => '',
                    'kriteria' => '',
                    'bobot_1' => '',
                    'bobot_2' => '',
                    'bobot_3' => '',
                    'bobot_4' => '',
                    'bobot_5' => '',
                ];
            });
        } else {
            // Jika ada data, map data yang ada
            $data = $typeCriterias->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                    'bobot_1' => $item->bobot_1,
                    'bobot_2' => $item->bobot_2,
                    'bobot_3' => $item->bobot_3,
                    'bobot_4' => $item->bobot_4,
                    'bobot_5' => $item->bobot_5,
                ];
            });
        }

        return collect($data);
    }


    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            // First row
            [
                'No',
                'Aspek',
                'Kriteria',
                'Bobot',
                '',
                '',
                '',
                ''
            ],
            // Second row
            [
                '',
                '',
                '',
                'Bobot 1',
                'Bobot 2',
                'Bobot 3',
                'Bobot 4',
                'Bobot 5'
            ]
        ];
    }

    /**
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                // Get worksheet
                $worksheet = $event->sheet->getDelegate();

                // Merge cells for No, Aspek, and Kriteria
                $worksheet->mergeCells('A1:A2');
                $worksheet->mergeCells('B1:B2');
                $worksheet->mergeCells('C1:C2');

                // Merge cells for Bobot header
                $worksheet->mergeCells('D1:H1');

                // Set text alignment for all headers
                $worksheet->getStyle('A1:H2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                $worksheet->getStyle('A1:H2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Add borders to the headers
                $worksheet->getStyle('A1:H2')->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Make headers bold
                $worksheet->getStyle('A1:H2')->getFont()->setBold(true);

                // Auto-size columns
                foreach (range('A', 'H') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }

                // Get the last row number
                $lastRow = $worksheet->getHighestRow();

                // Add borders to all data cells
                $worksheet->getStyle('A3:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Center align the No column and Bobot columns
                $worksheet->getStyle('A3:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('D3:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Type Criteria';
    }
}
