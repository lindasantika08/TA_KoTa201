<?php

namespace App\Exports;

use App\Models\User;
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
        // Cek apakah ada data mahasiswa dengan kriteria yang diberikan
        $existingData = User::where('role', 'mahasiswa')
            ->where('jurusan', $this->jurusan)
            ->where('prodi', $this->prodi)
            ->where('angkatan', $this->angkatan)
            ->whereNotNull('class')
            ->whereNotNull('name')
            ->whereNotNull('nim')
            ->whereNotNull('email')
            ->orderBy('class')
            ->orderBy('name')
            ->get();

        // Jika ada data yang memenuhi kriteria
        if ($existingData->count() > 0) {
            $data = [];
            foreach ($existingData as $index => $user) {
                $data[] = [
                    'no' => $index + 1,
                    'jurusan' => $user->jurusan,
                    'prodi' => $user->prodi,
                    'angkatan' => $user->angkatan,
                    'class' => $user->class,
                    'name' => $user->name,
                    'nim' => $user->nim,
                    'email' => $user->email
                ];
            }
            return new Collection($data);
        }

        // Jika tidak ada data, return template kosong
        $templateData = [];
        for ($i = 1; $i <= 30; $i++) {
            $templateData[] = [
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
        return new Collection($templateData);
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
            'B' => 20,  // Jurusan
            'C' => 20,  // Prodi
            'D' => 10,  // Angkatan
            'E' => 15,  // Kelas
            'F' => 20,  // Nama
            'G' => 15,  // NIM
            'H' => 25   // Email
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