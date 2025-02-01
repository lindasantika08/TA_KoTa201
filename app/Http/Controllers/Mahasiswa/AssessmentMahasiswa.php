<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Models\Group;
use App\Models\User;
use App\Models\Assessment;
use App\Models\AnswersPeer;
use App\Models\Mahasiswa;
use App\Models\Dosen;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AssessmentMahasiswa extends Controller
{
    public function selfAssessment()
    {
        return Inertia::render('Mahasiswa/ProjectSelfAssessment');
    }

    public function peerAssessment()
    {
        return Inertia::render('Mahasiswa/ProjectPeerAssessment');
    }

    public function getDataSelf()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa data not found'
                ], 404);
            }

            $groups = Group::with(['project' => function($query) {
                    $query->withCount(['assessments' => function($query) {
                        $query->where('type', 'selfAssessment'); 
                    }]);
                }])
                ->where('mahasiswa_id', $mahasiswa->id)
                ->get();

            $assessments = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'batch_year' => $group->batch_year,
                    'project_name' => $group->project->project_name,
                    'status' => $group->project->status,
                    'created_at' => $group->created_at,
                    'total_questions' => $group->project->assessments_count,
                ];
            });

            return response()->json([
                'success' => true,
                'assessments' => $assessments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching assessments: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDataPeer()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa data not found'
                ], 404);
            }

            $groups = Group::with(['project' => function($query) {
                    $query->withCount(['assessments' => function($query) {
                        $query->where('type', 'peerAssessment');
                    }]);
                }])
                ->where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('project', function($query) {
                    $query->whereHas('assessments', function($query) {
                        $query->where('type', 'peerAssessment');
                    });
                })
                ->get();

            $assessments = $groups->map(function ($group) {
                return [
                    'id' => $group->id,
                    'batch_year' => $group->batch_year,
                    'project_name' => $group->project->project_name,
                    'status' => $group->project->status,
                    'created_at' => $group->created_at,
                    'total_questions' => $group->project->assessments_count,
                ];
            });

            return response()->json([
                'success' => true,
                'assessments' => $assessments
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getDataPeer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error fetching peer assessments: ' . $e->getMessage()
            ], 500);
        }
    }
    public function checkData()
    {
        $assessments = Assessment::where('type', 'peerAssessment')->get();
        $groups = Group::all();
        $projects = Project::whereHas('assessments', function($query) {
            $query->where('type', 'peerAssessment');
        })->get();

        return response()->json([
            'assessments_count' => $assessments->count(),
            'groups_count' => $groups->count(),
            'projects_with_peer_assessments' => $projects->count(),
            'sample_assessment' => $assessments->first(),
            'sample_group' => $groups->first(),
            'sample_project' => $projects->first(),
        ]);
    }

    public function getKelompokByUser(Request $request)
    {
        $userId = auth()->id();
        $tahunAjaran = $request->input('batch_year');
        $proyek = $request->input('proyek');

        if (!$tahunAjaran || !$proyek) {
            Log::warning('Tahun Ajaran atau Proyek tidak ditemukan pada request', [
                'batch_year' => $tahunAjaran,
                'proyek' => $proyek
            ]);
            return response()->json(['message' => 'Tahun ajaran atau proyek tidak ditemukan'], 400);
        }

        Log::info('Menerima request untuk getKelompokByUser', [
            'user_id' => $userId,
            'batch_year' => $tahunAjaran,
            'proyek' => $proyek
        ]);

        $totalQuestions = Assessment::where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->where('type', 'peerAssessment')
            ->count();

        $userGroup = Kelompok::where('user_id', $userId)
            ->where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->value('kelompok');

        $kelompok = Kelompok::with('user')
            ->where('kelompok', $userGroup)
            ->where('batch_year', $tahunAjaran)
            ->where('project_name', $proyek)
            ->where('user_id', '!=', $userId)
            ->get();

        $filteredKelompok = $kelompok->filter(function ($member) use ($userId, $totalQuestions) {
            $completedAnswers = AnswersPeer::where('user_id', $userId)
                ->where('peer_id', $member->user_id)
                ->count();

            return $completedAnswers < $totalQuestions;
        });

        Log::info('Query untuk getKelompokByUser', [
            'total_questions' => $totalQuestions,
            'original_members' => $kelompok->count(),
            'filtered_members' => $filteredKelompok->count(),
        ]);

        if ($filteredKelompok->isEmpty()) {
            Log::info('Tidak ada anggota kelompok yang tersisa untuk dinilai', [
                'user_id' => $userId,
                'batch_year' => $tahunAjaran,
                'proyek' => $proyek
            ]);
        }

        return response()->json($filteredKelompok->values());
    }

    public function searchByNim(Request $request)
    {
        $nim = $request->query('nim');
        $user = User::where('nim', $nim)->first();

        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }
}
