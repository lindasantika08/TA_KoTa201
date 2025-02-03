<?php

namespace App\Exports;

use App\Models\Mahasiswa;
use App\Models\ClassRoom;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class MahasiswaExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $prodiId;
    protected $batchYear;

    public function __construct($prodiId, $batchYear)
    {
        $this->prodiId = $prodiId;
        $this->batchYear = $batchYear;
    }

    public function collection()
    {
        // Get the classroom for the specified prodi and batch year
        $classroom = ClassRoom::where('prodi_id', $this->prodiId)
            ->where('angkatan', $this->batchYear)
            ->with(['prodi.major', 'prodi'])
            ->first();

        if ($classroom) {
            // Get existing students data
            $existingData = Mahasiswa::whereHas('classRoom', function ($query) {
                    $query->where('prodi_id', $this->prodiId)
                          ->where('angkatan', $this->batchYear);
                })
                ->with(['user', 'classRoom.prodi.major'])
                ->whereHas('user', function ($query) {
                    $query->whereNotNull('name')
                          ->whereNotNull('email');
                })
                ->orderBy('nim')
                ->get();

            if ($existingData->count() > 0) {
                $data = [];
                foreach ($existingData as $index => $mahasiswa) {
                    $data[] = [
                        'no' => $index + 1,
                        'jurusan' => $mahasiswa->classRoom->prodi->major->major_name ?? '',
                        'prodi' => $mahasiswa->classRoom->prodi->prodi_name ?? '',
                        'angkatan' => $mahasiswa->classRoom->angkatan ?? '',
                        'class' => $mahasiswa->classRoom->class_name ?? '',
                        'name' => $mahasiswa->user->name ?? '',
                        'nim' => $mahasiswa->nim ?? '',
                        'email' => $mahasiswa->user->email ?? ''
                    ];
                }
                return new Collection($data);
            }
        }

        // If no data exists, return template with prodi information
        $prodiInfo = \App\Models\Prodi::with('major')
            ->find($this->prodiId);

        $templateData = [];
        for ($i = 1; $i <= 30; $i++) {
            $templateData[] = [
                'no' => $i,
                'jurusan' => $prodiInfo->major->major_name ?? '',
                'prodi' => $prodiInfo->prodi_name ?? '',
                'angkatan' => $this->batchYear,
                'class' => $classroom->class_name ?? '',
                'name' => null,
                'nim' => null,
                'email' => null
            ];
        }
        return new Collection($templateData);
    }

    public function headings(): array
    {
        return [
            'No',
            'Jurusan',
            'Prodi', 
            'Angkatan',
            'Kelas',
            'Nama Mahasiswa',
            'NIM',
            'Email'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Border and alignment for entire table
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:H' . $lastRow)->applyFromArray([
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
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Left align other columns
        $columns = ['B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($columns as $column) {
            $sheet->getStyle($column . '2:' . $column . $lastRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Set column widths
        $columnWidths = [
            'A' => 5,   // No
            'B' => 25,  // Jurusan
            'C' => 25,  // Program Studi
            'D' => 12,  // Angkatan
            'E' => 15,  // Kelas
            'F' => 30,  // Nama Mahasiswa
            'G' => 15,  // NIM
            'H' => 30   // Email
        ];

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    public function title(): string
    {
        return 'Data Mahasiswa';
    }
}