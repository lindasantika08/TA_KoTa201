<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Major;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Collection;

class DosenExport implements FromCollection, WithHeadings, WithTitle, WithStyles
{
    protected $majorId;

    public function __construct($majorId)
    {
        $this->majorId = $majorId;
    }

    public function collection()
    {
        // Get the major first
        $major = Major::find($this->majorId);
        
        if (!$major) {
            return new Collection($this->getEmptyTemplate(null));
        }

        // Get existing data through relationships
        $existingData = User::whereHas('dosen', function($query) {
            $query->where('major_id', $this->majorId);
        })
        ->where('role', 'dosen')
        ->whereNotNull('name')
        ->whereNotNull('email')
        ->whereHas('dosen', function($query) {
            $query->whereNotNull('nip')
                  ->whereNotNull('kode_dosen');
        })
        ->with(['dosen', 'dosen.major']) // Eager load relationships
        ->orderBy('name')
        ->get();

        // If data exists, format it
        if ($existingData->count() > 0) {
            $data = [];
            foreach ($existingData as $index => $user) {
                $data[] = [
                    'no' => $index + 1,
                    'jurusan' => $user->dosen->major->major_name,
                    'name' => $user->name,
                    'kode_dosen' => $user->dosen->kode_dosen,
                    'nip' => $user->dosen->nip,
                    'email' => $user->email
                ];
            }
            return new Collection($data);
        }

        // If no data, return empty template
        return new Collection($this->getEmptyTemplate($major->major_name));
    }

    private function getEmptyTemplate($majorName)
    {
        $templateData = [];
        for ($i = 1; $i <= 30; $i++) {
            $templateData[] = [
                'no' => $i,
                'jurusan' => $majorName,
                'name' => null,
                'kode_dosen' => null,
                'nip' => null,
                'email' => null
            ];
        }
        return $templateData;
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
        $lastRow = $sheet->getHighestRow();
        $sheet->getStyle('A1:F' . $lastRow)->applyFromArray([
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
        $sheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Left align other columns
        $columns = ['B', 'C', 'D', 'E', 'F'];
        foreach ($columns as $column) {
            $sheet->getStyle($column . '2:' . $column . $lastRow)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);
        }

        // Set column widths
        $columnWidths = [
            'A' => 5,   // No
            'B' => 20,  // Jurusan
            'C' => 20,  // Name
            'D' => 15,  // Kode_dosen
            'E' => 20,  // NIP
            'F' => 25,  // email
        ];
        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
    }

    public function title(): string
    {
        return 'Data Dosen';
    }
}