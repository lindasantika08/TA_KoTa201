<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\TypeCriteria;
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        // Ambil data assessment berdasarkan tahun_ajaran dan nama_proyek
        $assessments = Assessment::where('tahun_ajaran', $this->tahunAjaran)
            ->where('nama_proyek', $this->namaProyek)
            ->with('typeCriteria') // Pastikan eager load relasi typeCriteria
            ->get();

        if ($assessments->isEmpty()) {
            // Jika tidak ada data, buat 5 baris template kosong
            $data = [];
            for ($i = 1; $i <= 5; $i++) {
                $data[] = [
                    'no' => $i,
                    'aspek' => '',
                    'kriteria' => '',
                    'bobot_1' => '',
                    'bobot_2' => '',
                    'bobot_3' => '',
                    'bobot_4' => '',
                    'bobot_5' => '',
                ];
            }
        } else {
            // Map data dengan relasi ke type_criteria
            $data = $assessments->map(function ($item, $key) {
                $typeCriteria = $item->typeCriteria; // Ambil data typeCriteria terkait
                return [
                    'no' => $key + 1,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                    'bobot_1' => $typeCriteria->bobot_1 ?? '', // Default kosong jika null
                    'bobot_2' => $typeCriteria->bobot_2 ?? '',
                    'bobot_3' => $typeCriteria->bobot_3 ?? '',
                    'bobot_4' => $typeCriteria->bobot_4 ?? '',
                    'bobot_5' => $typeCriteria->bobot_5 ?? '',
                ];
            })->toArray();
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
