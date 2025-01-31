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

class KelompokExport implements FromCollection, WithHeadings, ShouldAutoSize, WithEvents
{
    protected $tahunAjaran;
    protected $namaProyek;
    protected $semester;

    public function __construct($tahunAjaran, $namaProyek, $semester)
    {
        $this->tahunAjaran = $tahunAjaran;
        $this->namaProyek = $namaProyek;
        $this->semester = $semester;
    }

    public function collection()
    {
        $kelompokData = DB::table('groups')
            ->join('mahasiswa', 'groups.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('users as mahasiswa_user', 'mahasiswa.user_id', '=', 'mahasiswa_user.id')
            ->join('dosen', 'groups.dosen_id', '=', 'dosen.id')
            ->join('users as dosen_user', 'dosen.user_id', '=', 'dosen_user.id')
            ->join('project', 'groups.project_id', '=', 'project.id')
            ->join('class_room', 'mahasiswa.class_id', '=', 'class_room.id')
            ->join('prodi', 'class_room.prodi_id', '=', 'prodi.id')
            ->where('project.batch_year', $this->tahunAjaran)
            ->where('project.project_name', $this->namaProyek)
            ->select(
                'groups.batch_year',
                'project.project_name',
                'mahasiswa_user.name as mahasiswa_name',
                'mahasiswa.nim',
                DB::raw("CONCAT(dosen_user.name, ' - ', dosen.kode_dosen) as dosen_manajer"),
                'groups.group'
            )
            ->orderBy('groups.group', 'asc')
            ->get();

        if ($kelompokData->isNotEmpty()) {
            $dataWithNo = $kelompokData->map(function ($item, $index) {
                return (object) array_merge(['no' => $index + 1], (array) $item);
            });

            return $dataWithNo;
        }

        Log::info("Tidak ada data kelompok untuk tahun ajaran {$this->tahunAjaran} dan proyek {$this->namaProyek}.");

        $mahasiswa = DB::table('mahasiswa')
            ->join('users', 'mahasiswa.user_id', '=', 'users.id')
            ->join('class_room', 'mahasiswa.class_id', '=', 'class_room.id')
            ->join('project', 'class_room.prodi_id', '=', 'project.major_id')
            ->where('users.role', 'mahasiswa')
            ->where('project.batch_year', $this->tahunAjaran)
            ->where('project.project_name', $this->namaProyek)
            ->select(
                'users.name',
                'mahasiswa.nim'
            )
            ->orderBy('users.name', 'asc')
            ->get();

        $data = [];
        foreach ($mahasiswa as $index => $mahasiswaItem) {
            $data[] = [
                'no' => $index + 1,
                'batch_year' => $this->tahunAjaran,
                'project_name' => $this->namaProyek,
                'mahasiswa_name' => $mahasiswaItem->name,
                'nim' => $mahasiswaItem->nim,
                'dosen_manajer' => '',
                'group' => ''
            ];
        }

        return collect($data);
    }

    public function headings(): array
    {
        return ['No', 'Tahun Ajaran', 'Proyek', 'Nama', 'NIM', 'Dosen Manajer', 'Kelompok'];
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
                                      
                $dosenList = DB::table('users')
                    ->join('dosen', 'users.id', '=', 'dosen.user_id')
                    ->where('users.role', 'dosen')
                    ->where('dosen.major_id', $projectMajor)
                    ->whereNull('users.deleted_at')
                    ->select(DB::raw("CONCAT(users.name, ' - ', dosen.kode_dosen) as dosen_label"))
                    ->distinct()
                    ->pluck('dosen_label')
                    ->toArray();

                $validation = new DataValidation();
                $validation->setType(DataValidation::TYPE_LIST)
                    ->setErrorStyle(DataValidation::STYLE_INFORMATION)
                    ->setAllowBlank(false)
                    ->setShowDropDown(true)
                    ->setFormula1('"' . implode(',', array_unique($dosenList)) . '"');

                for ($row = 2; $row <= $lastRow; $row++) {
                    $worksheet->getCell('F' . $row)->setDataValidation(clone $validation);
                }

                $worksheet->getStyle('A1:G' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle('thin');
                $worksheet->getStyle('A1:G1')->getFont()->setBold(true);
                $worksheet->getStyle('A1:G1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('A2:A' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $worksheet->getStyle('B2:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
            }
        ];
    }
}
