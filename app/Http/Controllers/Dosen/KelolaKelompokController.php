<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Group;
use App\Models\User;
use App\Models\Mahasiswa;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\KelompokImport;
use App\Exports\KelompokExport;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class KelolaKelompokController extends Controller
{
    public function KelolaKelompok()
    {
        $allGroups = Group::select('group', 'project_id', 'dosen_id')
            ->with(['project', 'dosen.user'])
            ->get();

        Log::info(
            'All Unique Groups',
            $allGroups->map(function ($group) {
                return [
                    'group' => $group->group,
                    'project_name' => optional($group->project)->project_name,
                    'dosen_name' => optional($group->dosen->user)->name
                ];
            })->toArray()
        );

        $group2Details = Group::where('group', '2')
            ->with(['mahasiswa.user', 'dosen.user', 'project'])
            ->get();

        Log::info(
            'Group 2 Details',
            $group2Details->map(function ($group) {
                return [
                    'id' => $group->id,
                    'mahasiswa_name' => optional($group->mahasiswa->user)->name,
                    'dosen_name' => optional($group->dosen->user)->name,
                    'project_name' => optional($group->project)->project_name
                ];
            })->toArray()
        );

        $kelompokData = Group::with([
            'mahasiswa.user',
            'mahasiswa.classRoom',
            'dosen.user',
            'project'
        ])
            ->whereHas('project', function ($query) {
                $query->where('status', 'active');
            })
            ->get()
            ->groupBy('project_id')
            ->map(function ($projectGroups) {
                return $projectGroups->groupBy(function ($item) {
                    return optional($item->mahasiswa->classRoom)->class_name . '-' . $item->group;
                })
                    ->sortKeys()
                    ->map(function ($sameGroupItems) {
                        $firstGroup = $sameGroupItems->first();
                        $members = $sameGroupItems->map(function ($group) {
                            return [
                                'name' => optional($group->mahasiswa->user)->name ?? 'Unnamed',
                                'nim' => optional($group->mahasiswa)->nim ?? 'N/A',
                                'user_id' => optional($group->mahasiswa->user)->id ?? null,
                                'class' => optional($group->mahasiswa->classRoom)->class_name ?? 'N/A'
                            ];
                        })->unique('nim')->values();

                        // Ambil angkatan dari classroom mahasiswa di group
                        $angkatan = optional($firstGroup->mahasiswa->classRoom)->angkatan ?? 'N/A';
                        $class = optional($firstGroup->mahasiswa->classRoom)->class_name ?? 'N/A';
                        $project = $firstGroup->project;

                        return [
                            'dosen_name' => optional($firstGroup->dosen->user)->name ?? 'Unnamed Dosen',
                            'projects' => [
                                [
                                    'id' => $project->id ?? null, // Add this line
                                    'project_id' => $project->id ?? null, // Add this line to ensure project_id is set
                                    'project_name' => $project->project_name ?? 'N/A',
                                    'batch_year' => $project->batch_year ?? 'N/A',
                                    'group' => $firstGroup->group,
                                    'anggota' => $members,
                                    'angkatan' => $angkatan,
                                    'class' => $class,
                                    'classroom' => [
                                        'angkatan' => $angkatan,
                                        'class_name' => $class
                                    ]
                                ]
                            ]
                        ];
                    })
                    ->values();
            })
            ->flatten(1)
            ->filter()
            ->values();

        return Inertia::render('Dosen/KelolaKelompok', [
            'kelompok' => $kelompokData,
        ]);
    }

    public function CreateKelompok()
    {
        return Inertia::render('Dosen/CreateKelompok');
    }

    public function ProfileMhs(Request $request)
    {
        $userId = $request->input('user_id');
        $user = User::find($userId);

        if (!$user) {
            Log::warning('User not found:', ['user_id' => $userId]);
            return redirect()->back()->with('error', 'User tidak ditemukan');
        }

        Log::info('User found:', ['user_id' => $userId, 'name' => $user->name]);

        return Inertia::render('Dosen/DetailProfilMhs', [
            'user_id' => $userId,
            'user_name' => $user->name,
        ]);
    }

    public function getProfile($user_id)
    {
        $mahasiswa = Mahasiswa::with([
            'user',
            'classRoom.prodi.major',
        ])
            ->where('user_id', $user_id)
            ->first();

        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan.'], 404);
        }

        $photoUrl = $mahasiswa->user->photo ? asset('storage/' . $mahasiswa->user->photo) : null;

        return response()->json([
            'nama' => $mahasiswa->user->name,
            'nim' => $mahasiswa->nim,
            'prodi' => $mahasiswa->classRoom->prodi->prodi_name,
            'jurusan' => $mahasiswa->classRoom->prodi->major->major_name,
            'email' => $mahasiswa->user->email,
            'telepon' => $mahasiswa->user->phone,
            // 'photo' => $mahasiswa->user->photo,
            'photo' => $photoUrl,
        ]);
    }


    public function getProfilePhoto()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa || !$mahasiswa->user->photo) {
            return response()->json(['message' => 'Foto profil tidak ditemukan.'], 404);
        }

        $photoUrl = Storage::url($mahasiswa->user->photo);

        return response()->json(['photo_url' => $photoUrl]);
    }

    public function showDetail($id)
    {
        $kelompok = Group::with('user', 'dosen')->findOrFail($id);
        return Inertia::render('Dosen/DetailKelompok', [
            'kelompok' => $kelompok
        ]);
    }

    public function getAngkatan()
    {
        try {
            $angkatan = DB::table('class_room')
                ->distinct()
                ->orderBy('angkatan', 'desc')
                ->pluck('angkatan');

            return response()->json($angkatan);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Gagal mengambil data angkatan'], 500);
        }
    }

    public function exportTemplate(Request $request)
    {
        $request->validate([
            'batch_year' => 'required',
            'project_name' => 'required',
            'semester' => 'required',
            'angkatan' => 'required'
        ]);

        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');
        $semester = $request->input('semester');
        $angkatan = $request->input('angkatan');

        $project = Project::where('batch_year', $batchYear)
            ->where('project_name', $projectName)
            ->where('semester', $semester)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project tidak ditemukan.'], 404);
        }

        return Excel::download(
            new KelompokExport(
                $batchYear,
                $projectName,
                $semester,
                $angkatan
            ),
            'Data_Kelompok.xlsx'
        );
    }

    public function importData(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');

        try {
            Log::info('File uploaded', ['file_name' => $file->getClientOriginalName()]);

            Log::info('File contents', ['contents' => file_get_contents($file->getRealPath())]);

            Excel::import(new KelompokImport, $file);

            return response()->json(['message' => 'Data kelompok berhasil diimpor'], 200);
        } catch (\Exception $e) {
            Log::error('Import error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Terjadi kesalahan saat mengimpor data', 'details' => $e->getMessage()], 500);
        }
    }

    public function checkGroupDeletion(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!$value) {
                        $fail('The project ID cannot be empty.');
                    }

                    $projectExists = DB::table('groups')
                        ->where('project_id', $value)
                        ->exists();

                    if (!$projectExists) {
                        $fail('The selected project does not exist.');
                    }
                }
            ],
            'group_name' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed',
                'request_data' => $request->all()
            ], 422);
        }

        $validated = $validator->validated();

        // Check related data in answers_peer
        $relatedAnswersPeer = DB::table('answers_peer')
            ->join('mahasiswa', 'answers_peer.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('groups', 'mahasiswa.id', '=', 'groups.mahasiswa_id')
            ->where('groups.project_id', $validated['project_id'])
            ->where('groups.group', $validated['group_name'])
            ->exists();

        // Check related data in answers table
        $relatedAnswers = DB::table('answers')
            ->join('mahasiswa', 'answers.mahasiswa_id', '=', 'mahasiswa.id')
            ->join('groups', 'mahasiswa.id', '=', 'groups.mahasiswa_id')
            ->where('groups.project_id', $validated['project_id'])
            ->where('groups.group', $validated['group_name'])
            ->exists();

        // Check related data in reports table
        $relatedReports = DB::table('reports')
            ->join('groups', 'reports.group_id', '=', 'groups.id')
            ->where('groups.project_id', $validated['project_id'])
            ->where('groups.group', $validated['group_name'])
            ->exists();

        // If any related data exists, require confirmation
        if ($relatedAnswersPeer || $relatedAnswers || $relatedReports) {
            $warningMessage = 'This group has related data. Deleting the group will remove all associated data';

            $relatedDataTypes = [];

            if ($relatedAnswersPeer) {
                $relatedDataTypes[] = 'peer assessment answers';
            }

            if ($relatedAnswers) {
                $relatedDataTypes[] = 'regular answers';
            }

            if ($relatedReports) {
                $relatedDataTypes[] = 'reports';
            }

            $warningMessage .= ' including ' . implode(', ', $relatedDataTypes) . '.';

            return response()->json([
                'warning' => $warningMessage,
                'requires_confirmation' => true
            ]);
        }

        return response()->json(['requires_confirmation' => false]);
    }

    public function deleteGroup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'project_id' => ['required', 'string', 'exists:groups,project_id'],
            'group_name' => 'required|string',
            'force' => 'sometimes|boolean'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation Failed:', [
                'errors' => $validator->errors(),
                'input' => $request->all()
            ]);
            return response()->json([
                'errors' => $validator->errors(),
                'message' => 'Validation failed'
            ], 422);
        }

        $validated = $validator->validated();

        DB::beginTransaction();

        try {
            // Fetch the groups to delete
            $groupsToDelete = Group::where('project_id', $validated['project_id'])
                ->where('group', $validated['group_name'])
                ->get();

            if ($groupsToDelete->isEmpty()) {
                \Log::warning('No Groups Found:', [
                    'project_id' => $validated['project_id'],
                    'group_name' => $validated['group_name']
                ]);
                return response()->json(['error' => 'No groups found'], 404);
            }

            \Log::info('Groups Found for Deletion:', [
                'count' => $groupsToDelete->count(),
                'group_ids' => $groupsToDelete->pluck('id')->toArray()
            ]);

            // Track deletion statistics
            $totalAnswersDeleted = 0;
            $totalAnswersPeerDeleted = 0;
            $totalReportsDeleted = 0;
            $totalGroupMembersDeleted = 0;

            // Process each group
            foreach ($groupsToDelete as $group) {
                $groupId = $group->id;

                // 1. Delete records from reports table first (this is causing the constraint violation)
                $reportsDeleted = DB::table('reports')
                    ->where('group_id', $groupId)
                    ->delete();
                $totalReportsDeleted += $reportsDeleted;

                \Log::info("Deleted {$reportsDeleted} reports for group {$groupId}");

                // 2. Delete associated answers_peer where students in this group provided answers
                $answersPeerDeleted = DB::table('answers_peer')
                    ->join('mahasiswa', 'answers_peer.mahasiswa_id', '=', 'mahasiswa.id')
                    ->join('groups', 'mahasiswa.id', '=', 'groups.mahasiswa_id')
                    ->where('groups.id', $groupId)
                    ->delete();

                // 3. Delete associated answers_peer where the peer is in this group
                $peerAnswersDeleted = DB::table('answers_peer')
                    ->join('mahasiswa', 'answers_peer.peer_id', '=', 'mahasiswa.id')
                    ->join('groups', 'mahasiswa.id', '=', 'groups.mahasiswa_id')
                    ->where('groups.id', $groupId)
                    ->delete();

                $totalAnswersPeerDeleted += ($answersPeerDeleted + $peerAnswersDeleted);

                \Log::info("Deleted answers_peer for group {$groupId}: {$answersPeerDeleted} direct answers, {$peerAnswersDeleted} peer answers");

                // 4. Delete associated records from answers table where students in this group provided answers
                $answersDeleted = DB::table('answers')
                    ->join('mahasiswa', 'answers.mahasiswa_id', '=', 'mahasiswa.id')
                    ->join('groups', 'mahasiswa.id', '=', 'groups.mahasiswa_id')
                    ->where('groups.id', $groupId)
                    ->delete();

                $totalAnswersDeleted += $answersDeleted;

                \Log::info("Deleted {$answersDeleted} answers for group {$groupId}");

                // 5. Check for any other tables that might have foreign key relationships with groups
                // Add more delete operations as needed for other related tables

                // 6. Finally delete the group itself
                $result = $group->delete();

                if ($result) {
                    $totalGroupMembersDeleted++;
                    \Log::info("Successfully deleted group {$groupId}");
                } else {
                    \Log::warning("Failed to delete group {$groupId}");
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Group deleted successfully',
                'deleted_group_count' => $groupsToDelete->count(),
                'deleted_answers_count' => $totalAnswersDeleted,
                'deleted_answers_peer_count' => $totalAnswersPeerDeleted,
                'deleted_reports_count' => $totalReportsDeleted,
                'deleted_group_members_count' => $totalGroupMembersDeleted
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Group Deletion Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to delete group',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}
