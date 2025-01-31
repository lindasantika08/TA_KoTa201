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
    protected $batchYear;
    protected $projectName;
    protected $projectId;
    protected $assessments;

    public function __construct($batchYear, $projectName, $projectId, $assessments = null)
    {
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
        $this->projectId = $projectId;
        $this->assessments = $assessments;
    }

    public function collection()
    {
        if (!$this->assessments || $this->assessments->isEmpty()) {
            // Template kosong dengan 10 baris
            $data = [];
            for ($i = 1; $i <= 10; $i++) {
                $data[] = [
                    'no' => $i,
                    'batch_year' => $this->batchYear,
                    'project_name' => $this->projectName,
                    'type' => '',
                    'question' => '',
                    'aspect' => '',    // Menambahkan kolom aspect
                    'criteria' => '',   // Menambahkan kolom criteria
                ];
            }
            return collect($data);
        }

        // Map data yang ada dengan mengambil aspek dan kriteria dari relasi
        return $this->assessments->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'batch_year' => $this->batchYear,
                'project_name' => $this->projectName,
                'type' => $item->type,
                'question' => $item->question,
                'aspect' => $item->typeCriteria ? $item->typeCriteria->aspect : '',
                'criteria' => $item->typeCriteria ? $item->typeCriteria->criteria : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'No',
            'Batch Year',
            'Project Name',
            'Type',
            'Question',
            'Aspect',
            'Criteria'
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow();

                // Border untuk semua sel
                $worksheet->getStyle('A1:G' . $lastRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle('thin');

                // Header bold
                $worksheet->getStyle('A1:G1')
                    ->getFont()
                    ->setBold(true);

                // Header center
                $worksheet->getStyle('A1:G1')
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Nomor center
                $worksheet->getStyle('A2:A' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Kolom lain left align
                $worksheet->getStyle('B2:G' . $lastRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_LEFT);

                // Auto-size columns
                foreach (range('A', 'G') as $col) {
                    $worksheet->getColumnDimension($col)
                        ->setAutoSize(true);
                }

                // Dropdown untuk Type
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
