<?php

namespace App\Exports;

use App\Models\TypeCriteria;
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
        // Get criteria IDs used in assessments for this project
        $criteriaIds = Assessment::where('project_id', $this->projectId)
            ->pluck('criteria_id')
            ->unique();

        // Get type criteria for this project only
        $typeCriterias = TypeCriteria::whereIn('id', $criteriaIds)
            ->select(
                'aspect',
                'criteria',
                'bobot_1',
                'bobot_2',
                'bobot_3',
                'bobot_4',
                'bobot_5'
            )
            ->get();

        if ($typeCriterias->isEmpty()) {
            // Create empty template
            $data = collect(range(1, 5))->map(function ($i) {
                return [
                    'no' => $i,
                    'aspect' => '',
                    'criteria' => '',
                    'bobot_1' => '',
                    'bobot_2' => '',
                    'bobot_3' => '',
                    'bobot_4' => '',
                    'bobot_5' => '',
                ];
            });
        } else {
            // Map existing data
            $data = $typeCriterias->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'aspect' => $item->aspect,
                    'criteria' => $item->criteria,
                    'bobot_1' => $item->bobot_1,
                    'bobot_2' => $item->bobot_2,
                    'bobot_3' => $item->bobot_3,
                    'bobot_4' => $item->bobot_4,
                    'bobot_5' => $item->bobot_5,
                ];
            });
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['No', 'Aspect', 'Criteria', 'Bobot', '', '', '', ''],
            ['', '', '', 'Bobot 1', 'Bobot 2', 'Bobot 3', 'Bobot 4', 'Bobot 5']
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
                $worksheet->mergeCells('C3:C4');
                $worksheet->mergeCells('D3:H3');

                // Center align and bold headers
                $worksheet->getStyle('A1:H2')->getFont()->setBold(true);
                $worksheet->getStyle('A3:H4')->getAlignment()
                    ->setVertical(Alignment::VERTICAL_CENTER)
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('A3:H4')->getFont()->setBold(true);

                // Add borders
                $lastRow = $worksheet->getHighestRow();
                $worksheet->getStyle('A3:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');

                // Center align specific columns
                $worksheet->getStyle('A5:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('D5:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Auto-size columns
                foreach (range('A', 'H') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    public function title(): string
    {
        return 'Type Criteria';
    }
}
