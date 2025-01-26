<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class MahasiswaExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{

    protected $jurusan;
    protected $prodi;
    protected $angkatan;

    public function __construct($jurusan, $prodi, $angkatan)
    {
        $this->jurusan = $jurusan;
        $this->prodi = $prodi;
        $this->angkatan = $angkatan;
    }

    public function collection()
    {
        $data = [];
        for ($i = 1; $i <= 30; $i++) {
            $data[] = [
                'no' => $i,
                'jurusan' => $this->jurusan,
                'prodi' => $this->prodi,
                'angkatan' => $this->angkatan,
                'class' => null,
                'name' => null,
                'nim' => null,
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
            'Prodi', 
            'Angkatan',
            'Class',
            'Name',
            'NIM',
            'Email'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Border and alignment for entire table
        $sheet->getStyle('A1:H31')->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000']
                ]
            ]
        ]);

        // Header styling
        $sheet->getStyle('A1:H1')->applyFromArray([
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
        $sheet->getStyle('A2:A31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Left align other columns
        $sheet->getStyle('B2:B31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('C2:C31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('D2:D31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('E2:E31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('F2:F31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('G2:G31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
        $sheet->getStyle('H2:H31')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(5);  // No
        $sheet->getColumnDimension('B')->setWidth(20); // Jurusan
        $sheet->getColumnDimension('C')->setWidth(20); // Prodi
        $sheet->getColumnDimension('D')->setWidth(10); // Angkatan
        $sheet->getColumnDimension('E')->setWidth(15); // Kelas
        $sheet->getColumnDimension('F')->setWidth(20); // Nama
        $sheet->getColumnDimension('G')->setWidth(15); // NIM
        $sheet->getColumnDimension('H')->setWidth(25); // Email
    }

    public function title(): string
    {
        return 'Data Mahasiswa';
    }
}