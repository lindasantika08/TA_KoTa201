<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class ReflectiveAssessmentQuestionSheet implements FromCollection, WithHeadings, WithEvents, WithTitle, ShouldAutoSize
{
    protected $batchYear;
    protected $projectName;
    protected $projectId;
    protected $reflectiveType;

    public function __construct($batchYear, $projectName, $projectId, $reflectiveType)
    {
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
        $this->projectId = $projectId;
        $this->reflectiveType = $reflectiveType;
    }

    public function collection()
    {
        // Template dengan data kosong untuk 10 pertanyaan
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'no' => $i,
                'batch_year' => $this->batchYear,
                'project_name' => $this->projectName,
                'reflective_type' => $this->reflectiveType,
                'question' => '',
                'criteria_id' => '',
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return [
            'No',
            'Batch Year',
            'Project Name',
            'Reflective Type',
            'Question',
            'Criteria ID'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow();

                // Format header - Updated from E to F to account for new column
                $worksheet->getStyle('A1:F1')
                    ->getFont()
                    ->setBold(true);

                $worksheet->getStyle('A1:F1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Format data - Updated from E to F to account for new column
                $worksheet->getStyle('A1:F' . $lastRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                $worksheet->getStyle('A2:A' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Auto-size columns - Updated from E to F to account for new column
                foreach (range('A', 'F') as $col) {
                    $worksheet->getColumnDimension($col)->setAutoSize(true);
                }
            }
        ];
    }

    public function title(): string
    {
        // You can customize the sheet title based on reflective type if needed
        return $this->reflectiveType;
    }
}
