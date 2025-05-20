<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\Project;
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

            $studentGroups = DB::table('groups')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->select('id', 'project_id')
                ->get();
            
            $projectIds = $studentGroups->pluck('project_id')->unique()->toArray();
            
            if (empty($projectIds)) {
                return response()->json([
                    'success' => true,
                    'assessments' => []
                ]);
            }

            $projects = DB::table('project')
                ->whereIn('id', $projectIds)
                ->where('status', 'Active')
                ->get()
                ->keyBy('id');
            
            $result = [];
            
            foreach ($projects as $projectId => $project) {
                $assessmentOrders = DB::table('assessment')
                    ->where('project_id', $projectId)
                    ->where('type', 'selfAssessment')
                    ->where('is_published', 1)
                    ->select('assessment_order')
                    ->distinct()
                    ->orderBy('assessment_order')
                    ->pluck('assessment_order');
                
                $studentGroup = $studentGroups->where('project_id', $projectId)->first();
                    
                foreach ($assessmentOrders as $order) {
                    $totalQuestions = DB::table('assessment')
                        ->where('project_id', $projectId)
                        ->where('type', 'selfAssessment')
                        ->where('assessment_order', $order)
                        ->where('is_published', 1)
                        ->count();

                    $questionIds = DB::table('assessment')
                        ->where('project_id', $projectId)
                        ->where('type', 'selfAssessment')
                        ->where('assessment_order', $order)
                        ->where('is_published', 1)
                        ->pluck('id');

                    $answeredQuestions = DB::table('answers')
                        ->where('mahasiswa_id', $mahasiswa->id)
                        ->whereIn('question_id', $questionIds)
                        ->count();
      
                    if ($totalQuestions > 0) {
                        $result[] = [
                            'id' => $studentGroup->id,
                            'batch_year' => $project->batch_year,
                            'project_name' => $project->project_name,
                            'status' => $project->status,
                            'created_at' => $project->created_at,
                            'assessment_order' => $order,
                            'total_questions' => $totalQuestions,
                            'answered_questions' => $answeredQuestions,
                        ];
                    }
                }
            }
            
            usort($result, function($a, $b) {
                $batchYearComparison = strcmp($b['batch_year'], $a['batch_year']);
                if ($batchYearComparison !== 0) {
                    return $batchYearComparison;
                }
                
                $projectNameComparison = strcmp($a['project_name'], $b['project_name']);
                if ($projectNameComparison !== 0) {
                    return $projectNameComparison;
                }
                
                return $a['assessment_order'] - $b['assessment_order'];
            });

            return response()->json([
                'success' => true,
                'assessments' => $result
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

            // Get the projects that the student is part of
            $studentGroups = DB::table('groups')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->select('id', 'project_id')
                ->get();
            
            $projectIds = $studentGroups->pluck('project_id')->unique()->toArray();
            
            if (empty($projectIds)) {
                return response()->json([
                    'success' => true,
                    'assessments' => []
                ]);
            }

            // Get all projects the student is part of
            $projects = DB::table('project')
                ->whereIn('id', $projectIds)
                ->where('status', 'Active')
                ->get()
                ->keyBy('id');
            
            $result = [];
            
            // For each project, get the assessment orders that are published and of type peerAssessment
            foreach ($projects as $projectId => $project) {
                $assessmentOrders = DB::table('assessment')
                    ->where('project_id', $projectId)
                    ->where('type', 'peerAssessment')
                    ->where('is_published', 1)
                    ->select('assessment_order')
                    ->distinct()
                    ->orderBy('assessment_order')
                    ->pluck('assessment_order');
                
                // Get the student's group for this project
                $studentGroup = $studentGroups->where('project_id', $projectId)->first();
                    
                // For each published assessment order, add an entry
                foreach ($assessmentOrders as $order) {
                    // Count the number of questions in this assessment
                    $totalQuestions = DB::table('assessment')
                        ->where('project_id', $projectId)
                        ->where('type', 'peerAssessment')
                        ->where('assessment_order', $order)
                        ->where('is_published', 1)
                        ->count();
                    
                    $questionIds = DB::table('assessment')
                        ->where('project_id', $projectId)
                        ->where('type', 'peerAssessment')
                        ->where('assessment_order', $order)
                        ->where('is_published', 1)
                        ->pluck('id');

                    $answeredQuestions = DB::table('answers_peer')
                        ->where('mahasiswa_id', $mahasiswa->id)
                        ->whereIn('question_id', $questionIds)
                        ->count();
                    
                    if ($totalQuestions > 0) {
                        $result[] = [
                            'id' => $studentGroup->id,
                            'batch_year' => $project->batch_year,
                            'project_name' => $project->project_name,
                            'status' => $project->status,
                            'created_at' => $project->created_at,
                            'assessment_order' => $order,
                            'total_questions' => $totalQuestions,
                            'answered_questions' => $answeredQuestions
                        ];
                    }
                }
            }
            
            // Sort by batch year (desc), project name, and assessment order
            usort($result, function($a, $b) {
                // First compare batch year in descending order
                $batchYearComparison = strcmp($b['batch_year'], $a['batch_year']);
                if ($batchYearComparison !== 0) {
                    return $batchYearComparison;
                }
                
                // Then compare project name
                $projectNameComparison = strcmp($a['project_name'], $b['project_name']);
                if ($projectNameComparison !== 0) {
                    return $projectNameComparison;
                }
                
                // Finally compare assessment order
                return $a['assessment_order'] - $b['assessment_order'];
            });

            return response()->json([
                'success' => true,
                'assessments' => $result
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
