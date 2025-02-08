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

    public function model(array $row)
    {
        try {
            DB::beginTransaction();

            Log::info('Importing row:', $row);

            $mahasiswa = Mahasiswa::with(['user', 'classRoom.prodi.major'])
                ->where('nim', $row['nim'])
                ->first();

            if (!$mahasiswa) {
                Log::warning('Mahasiswa not found for NIM:', ['nim' => $row['nim']]);
                throw new \Exception("Mahasiswa dengan NIM {$row['nim']} tidak ditemukan");
            }

            // Validate angkatan
            if ($mahasiswa->classRoom->angkatan != $row['angkatan']) {
                Log::warning('Angkatan mismatch:', [
                    'nim' => $row['nim'],
                    'mahasiswa_angkatan' => $mahasiswa->classRoom->angkatan,
                    'input_angkatan' => $row['angkatan']
                ]);
                throw new \Exception("Angkatan tidak sesuai untuk mahasiswa dengan NIM {$row['nim']}");
            }

            if (!$mahasiswa->classRoom) {
                Log::warning('Class not found for mahasiswa:', ['nim' => $row['nim']]);
                throw new \Exception("Kelas tidak ditemukan untuk mahasiswa dengan NIM {$row['nim']}");
            }

            if (!$mahasiswa->classRoom->prodi) {
                Log::warning('Prodi not found for class:', ['class_id' => $mahasiswa->class_id]);
                throw new \Exception("Program studi tidak ditemukan untuk kelas mahasiswa dengan NIM {$row['nim']}");
            }

            if (!$mahasiswa->classRoom->prodi->major) {
                Log::warning('Major not found for prodi:', ['prodi_id' => $mahasiswa->classRoom->prodi_id]);
                throw new \Exception("Jurusan tidak ditemukan untuk program studi mahasiswa dengan NIM {$row['nim']}");
            }

            $dosenManager = explode(' - ', $row['dosen_manajer']);
            $kodeDosen = count($dosenManager) > 1 ? trim($dosenManager[1]) : null;

            $dosen = null;
            if ($kodeDosen) {
                $dosen = Dosen::where('kode_dosen', $kodeDosen)->first();
                if (!$dosen) {
                    Log::warning('Dosen not found for kode_dosen:', ['kode_dosen' => $kodeDosen]);
                    throw new \Exception("Dosen dengan kode {$kodeDosen} tidak ditemukan");
                }
            }

            $project = Project::where('project_name', $row['proyek'])
                ->where('major_id', $mahasiswa->classRoom->prodi->major->id)
                ->first();

            if (!$project) {
                Log::warning('Project not found', [
                    'project_name' => $row['proyek'],
                    'major_id' => $mahasiswa->classRoom->prodi->major->id
                ]);
                throw new \Exception("Project tidak ditemukan untuk proyek {$row['proyek']}");
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
                    'dosen_id' => $dosen ? $dosen->id : null,
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
        // Ambil angkatan yang sedang diimpor dari data terbaru
        $angkatanTerbaru = collect($dataBaru)->pluck('angkatan')->unique();

        // Ambil semua data lama yang memiliki project_id yang sama
        $dataLama = Group::where('project_id', $projectId)
            ->whereIn('angkatan', $angkatanTerbaru) // Hanya ambil data lama dengan angkatan yang sama
            ->get();

        foreach ($dataLama as $data) {
            $isFound = collect($dataBaru)->contains(function ($item) use ($data) {
                return $item['mahasiswa_id'] == $data->mahasiswa_id &&
                    $item['group'] == $data->group &&
                    $item['angkatan'] == $data->angkatan;
            });

            // Hanya hapus data jika tidak ditemukan dalam batch terbaru dengan angkatan yang sama
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
