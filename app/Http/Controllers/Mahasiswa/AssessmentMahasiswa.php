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
use App\Notifications\AssessmentNotifications;


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

            $assessments = DB::table('groups')
                ->join('project', 'groups.project_id', '=', 'project.id')
                ->join('assessment', function($join) {
                    $join->on('project.id', '=', 'assessment.project_id')
                        ->where('assessment.type', '=', 'selfAssessment');
                })
                ->where('groups.mahasiswa_id', $mahasiswa->id)
                ->select([
                    'groups.id',
                    'project.batch_year',
                    'project.project_name',
                    'project.status',
                    'groups.created_at',
                    'assessment.assessment_order'
                ])
                ->selectRaw('(SELECT COUNT(*) FROM assessment WHERE project_id = project.id AND type = "selfAssessment" AND is_published = 1) as total_questions')
                ->selectRaw('(SELECT is_published FROM assessment WHERE project_id = project.id AND type = "selfAssessment" AND assessment_order = assessment.assessment_order LIMIT 1) as is_published')
                ->distinct()
                ->orderBy('assessment.assessment_order')
                ->get();

            $filteredAssessments = $assessments->filter(function($assessment) {
                return $assessment->total_questions > 0 && $assessment->is_published == 1;
            })->values();

            return response()->json([
                'success' => true,
                'assessments' => $filteredAssessments
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
                        $query->where('type', 'peerAssessment')
                              ->where('is_published', 1);
                    }]);
                }])
                ->where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('project', function($query) {
                    $query->whereHas('assessments', function($query) {
                        $query->where('type', 'peerAssessment')
                              ->where('is_published', 1);
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
            })->filter(function ($assessment) {
                return $assessment['total_questions'] > 0;
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

}
