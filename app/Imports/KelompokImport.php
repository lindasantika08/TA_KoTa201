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
    public function model(array $row)
    {
        try {
            DB::beginTransaction();

            Log::info('Importing row:', $row);

            // Mencari mahasiswa berdasarkan nim dengan eager loading relationships
            $mahasiswa = Mahasiswa::with(['user', 'classRoom.prodi.major'])
                ->where('nim', $row['nim'])
                ->first();

            if (!$mahasiswa) {
                Log::warning('Mahasiswa not found for NIM:', ['nim' => $row['nim']]);
                throw new \Exception("Mahasiswa dengan NIM {$row['nim']} tidak ditemukan");
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

            // Memecah kolom dosen_manager untuk mendapatkan kode_dosen
            $dosenManager = explode(' - ', $row['dosen_manajer']);
            $kodeDosen = count($dosenManager) > 1 ? trim($dosenManager[1]) : null;

            // Mencari dosen berdasarkan kode_dosen
            $dosen = null;
            if ($kodeDosen) {
                $dosen = Dosen::where('kode_dosen', $kodeDosen)->first();
                if (!$dosen) {
                    Log::warning('Dosen not found for kode_dosen:', ['kode_dosen' => $kodeDosen]);
                    throw new \Exception("Dosen dengan kode {$kodeDosen} tidak ditemukan");
                }
            }

            // Mencari atau membuat project berdasarkan data yang ada
            $project = Project::firstOrCreate(
                [
                    'batch_year' => $mahasiswa->classRoom->batch_year,
                    'project_name' => $row['proyek'],
                    'major_id' => $mahasiswa->classRoom->prodi->major->id
                ],
                [
                    'id' => Str::uuid(),
                    'semester' => $row['semester'] ?? 'Ganjil', // Ambil dari Excel jika ada, default 'Ganjil'
                    'status' => 'active',
                    'start_date' => now(),
                    'end_date' => now()->addMonths(6),
                ]
            );

            // Data baru yang sedang diimpor
            $groupBaru = [
                'mahasiswa_id' => $mahasiswa->id,
                'group' => $row['kelompok'],
                'batch_year' => $mahasiswa->classRoom->batch_year,
                'project_id' => $project->id,
            ];

            // Tambahkan data baru ke koleksi
            static $dataBaru = [];
            $dataBaru[] = $groupBaru;

            // Simpan atau perbarui data group
            $group = Group::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'mahasiswa_id' => $mahasiswa->id,
                ],
                [
                    'id' => Str::uuid(),
                    'group' => $row['kelompok'],
                    'batch_year' => $mahasiswa->classRoom->batch_year,
                    'dosen_id' => $dosen ? $dosen->id : null,
                ]
            );

            // Hapus data lama yang tidak ada di data baru
            if (end($dataBaru) === $groupBaru) {
                $this->cleanupOldData($project->id, $dataBaru);
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
        $dataLama = Group::where('project_id', $projectId)->get();

        foreach ($dataLama as $data) {
            $isFound = collect($dataBaru)->contains(function ($item) use ($data) {
                return $item['mahasiswa_id'] == $data->mahasiswa_id &&
                    $item['group'] == $data->group;
            });

            if (!$isFound) {
                $data->delete();
                Log::info('Deleted old group:', [
                    'mahasiswa_id' => $data->mahasiswa_id,
                    'group' => $data->group,
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