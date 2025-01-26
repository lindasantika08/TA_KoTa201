<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class DosenExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $jurusan;

    public function __construct($jurusan)
    {
        $this->jurusan = $jurusan;
    }

    public function collection()
    {
        $data = [];
        for ($i = 1; $i <= 10; $i++) {
            $data[] = [
                'no' => $i,
                'jurusan' => $this->jurusan,
                'name' => null,
                'kode_dosen' => null,
                'nip' => null,
                'email' => null
            ];
        }
        return collect($data);
    }

    public function headings(): array
    {
        return [
            'No',
            'Jurusan',
            'Name',
            'Kode Dosen',
            'NIP',
            'Email'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Border and alignment for entire table
        $sheet->getStyle('A1:F11')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Header styling
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF']
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4CAF50']
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER
            ]
        ]);

        // Center align No column
        $sheet->getStyle('A2:A11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Left align other columns
        $sheet->getStyle('B2:B11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C2:C11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D2:D11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E2:E11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F2:F11')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);  // No
        $sheet->getColumnDimension('B')->setWidth(20); // Jurusan
        $sheet->getColumnDimension('C')->setWidth(20); // Nama
        $sheet->getColumnDimension('D')->setWidth(15); // Kode Dosen
        $sheet->getColumnDimension('E')->setWidth(20); // NIP
        $sheet->getColumnDimension('F')->setWidth(25); // Email
    }

    public function title(): string
    {
        return 'Data Dosen';
    }
}