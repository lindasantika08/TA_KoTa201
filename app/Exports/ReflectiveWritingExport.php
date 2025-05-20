<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReflectiveWritingExport implements FromCollection, WithHeadings, WithTitle, WithStyles
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

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        try {
            // For template mode, generate 10 empty rows
            $template = [];
            for ($i = 1; $i <= 10; $i++) {
                $template[] = [
                    'no' => $i,
                    'batch_year' => $this->batchYear,
                    'project_name' => $this->projectName,
                    'reflective_type' => $this->reflectiveType,
                    'point_1' => '',
                    'point_2' => '',
                    'point_3' => '',
                    'point_4' => '',
                    'point_5' => ''
                ];
            }

            return new Collection($template);
        } catch (\Exception $e) {
            Log::error('Error generating template data: ' . $e->getMessage());
            return new Collection([]);
        }
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'Batch Year',
            'Project Name',
            'Reflective Type',
            'Point 1',
            'Point 2',
            'Point 3',
            'Point 4',
            'Point 5'
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        return 'Reflective Writing';
    }

    /**
     * @param Worksheet $sheet
     * @return array
     */
    public function styles(Worksheet $sheet)
    {
        // Set column widths explicitly for better appearance
        $sheet->getColumnDimension('A')->setWidth(5);     // No
        $sheet->getColumnDimension('B')->setWidth(12);    // Batch Year
        $sheet->getColumnDimension('C')->setWidth(30);    // Project Name
        $sheet->getColumnDimension('D')->setWidth(20);    // Reflective Type
        $sheet->getColumnDimension('E')->setWidth(35);    // Point 1
        $sheet->getColumnDimension('F')->setWidth(35);    // Point 2
        $sheet->getColumnDimension('G')->setWidth(35);    // Point 3
        $sheet->getColumnDimension('H')->setWidth(35);    // Point 4
        $sheet->getColumnDimension('I')->setWidth(35);    // Point 5

        // Apply text wrapping to content columns
        $sheet->getStyle('E:I')->getAlignment()->setWrapText(true);

        // Apply borders to all cells in the template
        $allCellsRange = 'A1:I11'; // Header + 10 rows
        $sheet->getStyle($allCellsRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ]
            ]
        ]);

        // Add color to the header
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => '000000']
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'D9E1F2'] // Light blue color
            ]
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Center align 'No' column and batch year
        $sheet->getStyle('A2:A11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('B2:B11')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Adjust row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        // Auto-height for content rows
        for ($i = 2; $i <= 11; $i++) {
            $sheet->getRowDimension($i)->setRowHeight(40);
        }

        return [];
    }
}
