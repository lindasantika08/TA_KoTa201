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
                        ->where('assessment.type', '=', 'selfAssessment')
                        ->where('assessment.is_published', '=', 1); // Filter langsung di join si publishnya
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
                ->selectRaw('COUNT(DISTINCT assessment.id) as total_questions')
                ->groupBy('groups.id', 'project.batch_year', 'project.project_name', 'project.status', 'groups.created_at', 'assessment.assessment_order')
                ->having('total_questions', '>', 0)
                ->orderBy('project.batch_year', 'desc')
                ->orderBy('project.project_name')
                ->orderBy('assessment.assessment_order')
                ->get();

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

            $assessments = DB::table('groups')
                ->join('project', 'groups.project_id', '=', 'project.id')
                ->join('assessment', function($join) {
                    $join->on('project.id', '=', 'assessment.project_id')
                        ->where('assessment.type', '=', 'peerAssessment')
                        ->where('assessment.is_published', '=', 1); // Filter hanya yang dipublish
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
                ->selectRaw('COUNT(DISTINCT assessment.id) as total_questions')
                ->groupBy('groups.id', 'project.batch_year', 'project.project_name', 'project.status', 'groups.created_at', 'assessment.assessment_order')
                ->having('total_questions', '>', 0)
                ->orderBy('project.batch_year', 'desc')
                ->orderBy('project.project_name')
                ->orderBy('assessment.assessment_order')
                ->get();

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
