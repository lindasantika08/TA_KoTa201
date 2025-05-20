<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class ReflectiveAssessmentRubricSheet implements FromCollection, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    protected $projectId;
    protected $batchYear;
    protected $projectName;

    public function __construct($projectId, $batchYear, $projectName)
    {
        $this->projectId = $projectId;
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
    }

    public function collection()
    {
        // Create an empty template with 5 rows
        $data = collect(range(1, 5))->map(function ($i) {
            return [
                'no' => $i,
                'criteria_reflective' => '',
                'bobot_1' => '',
                'bobot_2' => '',
                'bobot_3' => '',
                'bobot_4' => '',
                'bobot_5' => '',
            ];
        });

        return $data;
    }

    public function headings(): array
    {
        return [
            ['No', 'Criteria Reflective', 'Scale Description', '', '', '', ''],
            ['', '', 'Bobot 1', 'Bobot 2', 'Bobot 3', 'Bobot 4', 'Bobot 5']
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();

                // Add project info at the top
                $worksheet->insertNewRowBefore(1, 2);
                $worksheet->setCellValue('A1', 'Batch Year: ' . $this->batchYear);
                $worksheet->setCellValue('A2', 'Project Name: ' . $this->projectName);

                // Merge header cells (adjusted for new rows)
                $worksheet->mergeCells('A3:A4');
                $worksheet->mergeCells('B3:B4');
                $worksheet->mergeCells('C3:G3');

                // Center align and bold headers
                $worksheet->getStyle('A1:G2')->getFont()->setBold(true);
                $worksheet->getStyle('A3:G4')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('A3:G4')->getFont()->setBold(true);

                // Add borders
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getStyle('A3:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Center align specific columns
                $worksheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('C5:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);



                // Auto-size columns
                foreach (range('A', 'G') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    public function title(): string
    {
        return 'Reflective Rubric';
    }
}
