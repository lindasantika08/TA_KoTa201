<?php

namespace App\Imports;

use App\Models\Group;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Models\Project;
use App\Models\Prodi;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Throwable;

class KelompokImport implements ToModel, WithHeadingRow, SkipsOnError
{
    private $dataBaru = [];
    private $requiredColumns = [
        'nim',
        'angkatan',
        'proyek',
        'kelas',
        'kode_dosen',
        'kelompok'
    ];

    public function model(array $row)
    {
        try {
            // Validate required columns
            foreach ($this->requiredColumns as $column) {
                if (!isset($row[$column]) || empty($row[$column])) {
                    Log::warning('Missing required column:', [
                        'column' => $column,
                        'row' => $row
                    ]);
                    return null; // Skip this row
                }
            }

            DB::beginTransaction();

            Log::info('Importing row:', $row);

            $mahasiswa = Mahasiswa::with(['user', 'classRoom.prodi.major'])
                ->where('nim', $row['nim'])
                ->first();

            if (!$mahasiswa) {
                Log::warning('Mahasiswa not found for nim:', ['nim' => $row['nim']]);
                throw new \Exception("Mahasiswa dengan nim {$row['nim']} tidak ditemukan");
            }

            // Validate angkatan
            if ($mahasiswa->classRoom->angkatan != $row['angkatan']) {
                Log::warning('Angkatan mismatch:', [
                    'nim' => $row['nim'],
                    'mahasiswa_angkatan' => $mahasiswa->classRoom->angkatan,
                    'input_angkatan' => $row['angkatan']
                ]);
                throw new \Exception("Angkatan tidak sesuai untuk mahasiswa dengan nim {$row['nim']}");
            }

            // Validate class
            if ($mahasiswa->classRoom->class_name != $row['kelas']) {
                Log::warning('Class mismatch:', [
                    'nim' => $row['nim'],
                    'mahasiswa_class' => $mahasiswa->classRoom->class_name,
                    'input_class' => $row['kelas']
                ]);
                throw new \Exception("Kelas tidak sesuai untuk mahasiswa dengan nim {$row['nim']}");
            }

            if (!$mahasiswa->classRoom) {
                Log::warning('Class not found for mahasiswa:', ['nim' => $row['nim']]);
                throw new \Exception("Kelas tidak ditemukan untuk mahasiswa dengan nim {$row['nim']}");
            }

            if (!$mahasiswa->classRoom->prodi) {
                Log::warning('Prodi not found for class:', ['class_id' => $mahasiswa->class_id]);
                throw new \Exception("Program studi tidak ditemukan untuk kelas mahasiswa dengan nim {$row['nim']}");
            }

            if (!$mahasiswa->classRoom->prodi->major) {
                Log::warning('Major not found for prodi:', ['prodi_id' => $mahasiswa->classRoom->prodi_id]);
                throw new \Exception("Jurusan tidak ditemukan untuk program studi mahasiswa dengan nim {$row['nim']}");
            }

            $project = Project::where('project_name', $row['proyek'])
                ->where('prodi_id', $mahasiswa->classRoom->prodi_id)
                ->first();

            if (!$project) {
                Log::warning('Project not found', [
                    'project_name' => $row['proyek'],
                    'prodi_id' => $mahasiswa->classRoom->prodi_id
                ]);
                throw new \Exception("Project tidak ditemukan untuk proyek {$row['proyek']}");
            }

            // Find dosen by kode_dosen
            $dosen = Dosen::where('kode_dosen', trim($row['kode_dosen']))
                ->where('major_id', $mahasiswa->classRoom->prodi->major_id)
                ->first();

            if (!$dosen) {
                Log::warning('Dosen not found for code:', ['kode_dosen' => $row['kode_dosen']]);
                throw new \Exception("Dosen dengan kode {$row['kode_dosen']} tidak ditemukan");
            }

            $groupBaru = [
                'mahasiswa_id' => $mahasiswa->id,
                'group' => $row['kelompok'],
                'batch_year' => $project->batch_year,
                'project_id' => $project->id,
                'angkatan' => $row['angkatan']
            ];

            $this->dataBaru[] = $groupBaru;

            $group = Group::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'mahasiswa_id' => $mahasiswa->id,
                ],
                [
                    'id' => Str::uuid(),
                    'group' => $row['kelompok'],
                    'batch_year' => $project->batch_year,
                    'dosen_id' => $dosen->id,
                    'angkatan' => $row['angkatan']
                ]
            );

            if (end($this->dataBaru) === $groupBaru) {
                $this->cleanupOldData($project->id, $this->dataBaru);
            }

            DB::commit();
            return null;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in import:', [
                'message' => $e->getMessage(),
                'row' => $row,
            ]);
            throw $e;
        }
    }

    private function cleanupOldData($projectId, $dataBaru)
    {
        $angkatanTerbaru = collect($dataBaru)->pluck('angkatan')->unique();

        $dataLama = Group::where('project_id', $projectId)
            ->whereIn('angkatan', $angkatanTerbaru)
            ->get();

        foreach ($dataLama as $data) {
            $isFound = collect($dataBaru)->contains(function ($item) use ($data) {
                return $item['mahasiswa_id'] == $data->mahasiswa_id &&
                    $item['group'] == $data->group &&
                    $item['angkatan'] == $data->angkatan;
            });

            if (!$isFound) {
                $data->delete();
                Log::info('Deleted old group:', [
                    'mahasiswa_id' => $data->mahasiswa_id,
                    'group' => $data->group,
                    'angkatan' => $data->angkatan,
                ]);
            }
        }
    }

    public function onError(Throwable $e)
    {
        Log::error('Row import error:', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
