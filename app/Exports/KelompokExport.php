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
        try {
            // First get the project details including prodi_id
            $project = DB::table('project')
                ->where('batch_year', $this->tahunAjaran)
                ->where('project_name', $this->namaProyek)
                ->first();

            if (!$project) {
                Log::error('Project not found', [
                    'tahun_ajaran' => $this->tahunAjaran,
                    'nama_proyek' => $this->namaProyek
                ]);
                throw new \Exception('Project not found');
            }

            // Get the major_id through prodi
            $majorId = DB::table('prodi')
                ->where('id', $project->prodi_id)
                ->value('major_id');

            $mahasiswaQuery = DB::table('mahasiswa')
                ->join('users', 'mahasiswa.user_id', '=', 'users.id')
                ->join('class_room', 'mahasiswa.class_id', '=', 'class_room.id')
                ->join('prodi', 'class_room.prodi_id', '=', 'prodi.id')
                ->leftJoin('groups', function ($join) use ($project) {
                    $join->on('groups.mahasiswa_id', '=', 'mahasiswa.id')
                        ->whereExists(function ($query) use ($project) {
                            $query->select(DB::raw(1))
                                ->from('project')
                                ->whereRaw('project.id = groups.project_id')
                                ->where('project.id', $project->id);
                        });
                })
                ->leftJoin('dosen', function ($join) use ($majorId) {
                    $join->on('groups.dosen_id', '=', 'dosen.id')
                        ->where('dosen.major_id', $majorId);
                })
                ->leftJoin('users as dosen_user', 'dosen.user_id', '=', 'dosen_user.id')
                ->where('prodi.id', $project->prodi_id)
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
        } catch (\Exception $e) {
            Log::error('Error in KelompokExport:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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

                // Get project and major information
                $project = DB::table('project')
                    ->where('project_name', $this->namaProyek)
                    ->where('batch_year', $this->tahunAjaran)
                    ->first();

                $majorId = DB::table('prodi')
                    ->where('id', $project->prodi_id)
                    ->value('major_id');

                // Get dosen list
                $dosenList = DB::table('users')
                    ->join('dosen', 'users.id', '=', 'dosen.user_id')
                    ->where('users.role', 'dosen')
                    ->where('dosen.major_id', $majorId)
                    ->whereNull('users.deleted_at')
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

                // Add data validation for Dosen Manajer column
                $dosenValidation = new DataValidation();
                $dosenValidation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('=DosenList');

                // Add data validation for Kelompok column
                $groupValidation = new DataValidation();
                $groupValidation->setType(DataValidation::TYPE_WHOLE)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(true)
                    ->setShowDropDown(true)
                    ->setFormula1(1)  // minimum value
                    ->setFormula2(10);  // maximum value

                // Apply validations to all rows
                for ($row = 2; $row <= $lastRow; $row++) {
                    $worksheet->getCell('G' . $row)->setDataValidation(clone $dosenValidation);
                    $worksheet->getCell('H' . $row)->setDataValidation(clone $groupValidation);
                }

                // Set column widths
                $worksheet->getColumnDimension('A')->setWidth(5);   // No
                $worksheet->getColumnDimension('B')->setWidth(15);  // Tahun Ajaran
                $worksheet->getColumnDimension('C')->setWidth(20);  // Proyek
                $worksheet->getColumnDimension('D')->setWidth(10);  // Angkatan
                $worksheet->getColumnDimension('E')->setWidth(30);  // Nama
                $worksheet->getColumnDimension('F')->setWidth(15);  // NIM
                $worksheet->getColumnDimension('G')->setWidth(30);  // Dosen Manajer
                $worksheet->getColumnDimension('H')->setWidth(10);  // Kelompok

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
