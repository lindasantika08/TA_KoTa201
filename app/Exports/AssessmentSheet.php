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

class AssessmentSheet implements FromCollection, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Assessment::select('type', 'pertanyaan', 'aspek', 'kriteria')
            ->get()
            ->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'type' => $item->type,
                    'pertanyaan' => $item->pertanyaan,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                ];
            });
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
                // Get worksheet
                $worksheet = $event->sheet->getDelegate();

                // Get the last row number
                $lastRow = $worksheet->getHighestRow();

                // Add borders to all cells including headers
                $worksheet->getStyle('A1:E' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Make headers bold
                $worksheet->getStyle('A1:E1')->getFont()->setBold(true);

                // Center headers
                $worksheet->getStyle('A1:E1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Center align No column
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Left align text columns (Type, Pertanyaan, Aspek, Kriteria)
                $worksheet->getStyle('B2:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Auto-size columns
                foreach (range('A', 'E') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
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
