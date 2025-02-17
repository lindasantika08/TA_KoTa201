<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class KelompokExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $tahunAjaran;
    protected $namaProyek;
    protected $semester;
    protected $angkatan;

    public function __construct($tahunAjaran, $namaProyek, $semester, $angkatan)
    {
        $this->tahunAjaran = $tahunAjaran;
        $this->namaProyek = $namaProyek;
        $this->semester = $semester;
        $this->angkatan = $angkatan;
    }

    public function collection()
    {
        $projectMajor = DB::table('project')
            ->where('batch_year', $this->tahunAjaran)
            ->where('project_name', $this->namaProyek)
            ->value('major_id');

        $mahasiswaQuery = DB::table('mahasiswa')
            ->join('users', 'mahasiswa.user_id', '=', 'users.id')
            ->join('class_room', 'mahasiswa.class_id', '=', 'class_room.id')
            ->join('prodi', 'class_room.prodi_id', '=', 'prodi.id')
            ->leftJoin('groups', function ($join) use ($projectMajor) {
                $join->on('groups.mahasiswa_id', '=', 'mahasiswa.id')
                    ->whereExists(function ($query) use ($projectMajor) {
                        $query->select(DB::raw(1))
                            ->from('project')
                            ->whereRaw('project.id = groups.project_id')
                            ->where('project.major_id', $projectMajor)
                            ->where('project.batch_year', $this->tahunAjaran)
                            ->where('project.project_name', $this->namaProyek);
                    });
            })
            ->leftJoin('dosen', function ($join) use ($projectMajor) {
                $join->on('groups.dosen_id', '=', 'dosen.id')
                    ->where('dosen.major_id', $projectMajor);
            })
            ->leftJoin('users as dosen_user', 'dosen.user_id', '=', 'dosen_user.id')
            ->where('prodi.major_id', $projectMajor)
            ->where('class_room.angkatan', $this->angkatan)
            ->select(
                DB::raw('(@row_number:=@row_number + 1) AS no'),
                DB::raw("'{$this->tahunAjaran}' as batch_year"),
                DB::raw("'{$this->namaProyek}' as project_name"),
                DB::raw("'{$this->angkatan}' as angkatan"),
                'users.name as mahasiswa_name',
                'mahasiswa.nim',
                'dosen_user.name as dosen_manajer',
                DB::raw('COALESCE(groups.`group`, \'\') as kelompok'),
            )
            ->from(DB::raw('(SELECT @row_number:=0) as r, mahasiswa'))
            ->get();

        return $mahasiswaQuery;
    }

    public function headings(): array
    {
        return ['No', 'Tahun Ajaran', 'Proyek', 'Angkatan', 'Nama', 'NIM', 'Dosen Manajer', 'Kelompok'];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $worksheet = $event->sheet->getDelegate();
                $lastRow = $worksheet->getHighestRow();

                $projectMajor = DB::table('project')
                    ->where('project_name', $this->namaProyek)
                    ->where('batch_year', $this->tahunAjaran)
                    ->value('major_id');

                // Get dosen list
                $dosenList = DB::table('users')
                    ->join('dosen', 'users.id', '=', 'dosen.user_id')
                    ->where('users.role', 'dosen')
                    ->where('dosen.major_id', $projectMajor)
                    ->select('users.name')
                    ->distinct()
                    ->pluck('name')
                    ->toArray();

                // Create hidden sheet for dosen list
                $spreadsheet = $worksheet->getParent();
                $listSheet = $spreadsheet->createSheet();
                $listSheet->setTitle('DosenList');

                // Add dosen names to hidden sheet
                foreach ($dosenList as $index => $name) {
                    $listSheet->setCellValue('A' . ($index + 1), $name);
                }

                // Name the range for dosen list
                $lastDosenRow = count($dosenList);
                $spreadsheet->addNamedRange(
                    new \PhpOffice\PhpSpreadsheet\NamedRange(
                        'DosenList',
                        $listSheet,
                        '$A$1:$A$' . $lastDosenRow
                    )
                );

                // Hide the list sheet
                $listSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);

                // Add data validation to main sheet
                $validation = new DataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('=DosenList');

                // Apply validation to all rows
                for ($row = 2; $row <= $lastRow; $row++) {
                    $worksheet->getCell('G' . $row)->setDataValidation(clone $validation);
                }

                // Apply styles
                $worksheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $worksheet->getStyle('A1:H1')->getFont()->setBold(true);
                $worksheet->getStyle('A1:H1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('B2:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}