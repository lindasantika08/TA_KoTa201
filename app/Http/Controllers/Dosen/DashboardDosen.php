<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Dosen;
use App\Models\User;
use App\Models\Group;
use App\Models\Answers;
use App\Models\TypeCriteria;
use App\Models\Mahasiswa;
use App\Models\AnswersPeer;
use App\Models\Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class DashboardDosen extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Dosen/Dashboard');
    }

    public function notifications()
    {
        return Inertia::render('Dosen/Notifications');
    }

    public function dashboardself()
    {
        return Inertia::render('Dosen/AnswerSelf');
    }

    public function dashboardpeer()
    {
        return Inertia::render('Dosen/AnswerPeer');
    }

    public function getActiveProjects(Request $request)
    {
        $projects = Project::with(['major'])
            ->where('status', 'Active')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'semester' => $project->semester,
                    'batch_year' => $project->batch_year,
                    'project_name' => $project->project_name,
                    'major' => $project->major ? $project->major->name : null,
                    'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                    'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                    'status' => $project->status,
                ];
            });

        return response()->json($projects);
    }

    public function getDosensInSameMajor()
    {
        try {
            $user = Auth::user();
            if (!$user || !$user->dosen) {
                return response()->json([
                    'error' => 'Unauthorized or user not found',
                    'data' => []
                ], 401);
            }

            $dosenData = $user->dosen;
            $sameMajorDosen = Dosen::where('major_id', $dosenData->major_id)->get();

            return response()->json([
                'data' => $sameMajorDosen
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting dosens in same major: ' . $e->getMessage());
            return response()->json([
                'error' => 'Internal server error',
                'data' => []
            ], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $batchYear = $request->query('batch_year');
            $projectName = $request->query('project_name');

            // Find the project first
            $project = Project::where('batch_year', $batchYear)
                ->where('project_name', $projectName)
                ->first();

            if (!$project) {
                return response()->json([
                    'error' => 'Project not found',
                    'message' => 'No project found with the given batch year and project name'
                ], 404);
            }

            $projectId = $project->id;

            // Find groups for specific project with mahasiswa loaded
            $groups = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->with('mahasiswa.user')
                ->get();

            $usersAlreadyFilled = Answers::whereHas('question', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
                ->whereIn('mahasiswa_id', $groups->pluck('mahasiswa_id'))
                ->distinct('mahasiswa_id')
                ->count();

            // Prepare details of users who haven't submitted
            $submissionStatus = $groups->map(function ($item) use ($projectId) {
                $isSubmitted = Answers::whereHas('question', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                    ->where('mahasiswa_id', $item->mahasiswa_id)
                    ->exists();

                return [
                    'index' => $item->id,
                    'mahasiswaName' => optional($item->mahasiswa->user)->name ?? 'Unknown',
                    'status' => $isSubmitted ? 'submitted' : 'unsubmitted'
                ];
            });

            return response()->json([
                'totalKeseluruhan' => $groups->count(),
                'totalSudahMengisi' => $usersAlreadyFilled,
                'submissionStatus' => $submissionStatus
            ]);
        } catch (\Exception $e) {

            return response()->json([
                'error' => 'Fatal Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getClassComparison(Request $request)
    {
        try {
            $batchYear = $request->query('batch_year');
            $projectName = $request->query('project_name');
            $scoreType = $request->query('score_type', 'peer'); // default to peer

            if (!$batchYear || !$projectName) {
                return response()->json([
                    'error' => 'Missing parameters',
                    'message' => 'batch_year and project_name are required'
                ], 400);
            }

            // Validate score_type
            if (!in_array($scoreType, ['self', 'peer', 'combined'])) {
                return response()->json([
                    'error' => 'Invalid score_type',
                    'message' => 'score_type must be one of: self, peer, combined'
                ], 400);
            }

            // Find the project
            $project = Project::where('batch_year', $batchYear)
                ->where('project_name', $projectName)
                ->first();

            if (!$project) {
                return response()->json([
                    'error' => 'Project not found',
                    'message' => 'No project found with the given batch year and project name'
                ], 404);
            }

            // Get all groups for this project with their class information
            $groups = Group::where('batch_year', $batchYear)
                ->where('project_id', $project->id)
                ->with(['mahasiswa.user', 'mahasiswa.classRoom.prodi'])
                ->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'project_info' => [
                        'batch_year' => $batchYear,
                        'project_name' => $projectName
                    ],
                    'aspects' => [],
                    'class_comparison' => [],
                    'message' => 'No groups found for this project'
                ]);
            }

            // Debug: Log groups data
            // \Log::info('Groups found for project: ' . $groups->count());
            // \Log::info('Sample group data: ', $groups->first()?->toArray());

            // Group by class (classRoom)
            $classesByMajor = $groups->groupBy(function ($group) {
                if ($group->mahasiswa && $group->mahasiswa->classRoom) {
                    return $group->mahasiswa->classRoom->class_name;
                }
                return 'Unknown';
            });

            // Debug: Log classes found
            // \Log::info('Classes found: ', $classesByMajor->keys()->toArray());

            $classComparison = [];

            foreach ($classesByMajor as $className => $classGroups) {
                $mahasiswaIds = $classGroups->pluck('mahasiswa_id')->toArray();

                // Debug: Log mahasiswa IDs for this class
                // \Log::info("Class: $className, Mahasiswa IDs: ", $mahasiswaIds);

                // Get scores from reports table grouped by aspect based on score type
                try {
                    $query = DB::table('reports')
                        ->join('type_criteria', 'reports.typeCriteria_id', '=', 'type_criteria.id')
                        ->where('reports.project_id', $project->id)
                        ->whereIn('reports.mahasiswa_id', $mahasiswaIds);

                    // Build query based on score type
                    switch ($scoreType) {
                        case 'self':
                            $query->whereNotNull('reports.final_score_self')
                                ->where('reports.final_score_self', '>', 0)
                                ->select(
                                    'type_criteria.aspect',
                                    DB::raw('AVG(CAST(reports.final_score_self AS DECIMAL(8,2))) as avg_score'),
                                    DB::raw('COUNT(*) as total_assessments')
                                );
                            break;

                        case 'peer':
                            $query->whereNotNull('reports.final_score_peer')
                                ->where('reports.final_score_peer', '>', 0)
                                ->select(
                                    'type_criteria.aspect',
                                    DB::raw('AVG(CAST(reports.final_score_peer AS DECIMAL(8,2))) as avg_score'),
                                    DB::raw('COUNT(*) as total_assessments')
                                );
                            break;

                        case 'combined':
                            $query->where(function ($q) {
                                $q->whereNotNull('reports.final_score_self')
                                    ->orWhereNotNull('reports.final_score_peer');
                            })
                                ->select(
                                    'type_criteria.aspect',
                                    DB::raw('AVG(
                                          CASE 
                                              WHEN reports.final_score_self IS NOT NULL AND reports.final_score_peer IS NOT NULL 
                                              THEN (CAST(reports.final_score_self AS DECIMAL(8,2)) + CAST(reports.final_score_peer AS DECIMAL(8,2))) / 2
                                              WHEN reports.final_score_self IS NOT NULL 
                                              THEN CAST(reports.final_score_self AS DECIMAL(8,2))
                                              WHEN reports.final_score_peer IS NOT NULL 
                                              THEN CAST(reports.final_score_peer AS DECIMAL(8,2))
                                          END
                                      ) as avg_score'),
                                    DB::raw('COUNT(*) as total_assessments')
                                );
                            break;
                    }

                    $reportScores = $query->groupBy('type_criteria.aspect')->get();
                } catch (\Exception $e) {
                    Log::error("Error in reportScores query for class $className: " . $e->getMessage());
                    $reportScores = collect(); // Empty collection if query fails
                }

                // Debug: Log report scores for this class
                // \Log::info("Report scores for class $className: ", $reportScores->toArray());

                // Prepare aspect scores
                $aspectScores = [];

                foreach ($reportScores as $score) {
                    $aspectScores[$score->aspect] = [
                        'avg_score' => round($score->avg_score, 2),
                        'total_assessments' => $score->total_assessments,
                        'score_type' => $scoreType
                    ];
                }

                // Add class even if no data, with default 0 scores for common aspects
                $classComparison[] = [
                    'class_name' => $className,
                    'total_students' => count($mahasiswaIds),
                    'aspect_scores' => $aspectScores
                ];
            }

            // Get all unique aspects for chart labels from type_criteria table SPECIFIC TO THIS PROJECT
            try {
                // Log project info for debugging
                Log::info("Getting aspects for project", [
                    'project_id' => $project->id,
                    'project_name' => $projectName,
                    'batch_year' => $batchYear
                ]);

                // First try to get aspects from assessments related to this project
                $allAspects = DB::table('assessment')
                    ->join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
                    ->where('assessment.project_id', $project->id)
                    ->distinct()
                    ->pluck('type_criteria.aspect')
                    ->filter()
                    ->values()
                    ->toArray();

                Log::info("Aspects from assessment table", ['aspects' => $allAspects]);

                // If no aspects found from assessments, try from reports for this project
                if (empty($allAspects)) {
                    $allAspects = DB::table('reports')
                        ->join('type_criteria', 'reports.typeCriteria_id', '=', 'type_criteria.id')
                        ->where('reports.project_id', $project->id)
                        ->distinct()
                        ->pluck('type_criteria.aspect')
                        ->filter()
                        ->values()
                        ->toArray();

                    Log::info("Aspects from reports table", ['aspects' => $allAspects]);
                }

                // If still no aspects, get from type_criteria that have been used in this project  
                if (empty($allAspects)) {
                    $usedTypeCriteriaIds = DB::table('assessment')
                        ->where('project_id', $project->id)
                        ->distinct()
                        ->pluck('criteria_id')
                        ->toArray();

                    Log::info("Used type criteria IDs for project from assessment", ['ids' => $usedTypeCriteriaIds]);

                    if (!empty($usedTypeCriteriaIds)) {
                        $allAspects = DB::table('type_criteria')
                            ->whereIn('id', $usedTypeCriteriaIds)
                            ->distinct()
                            ->pluck('aspect')
                            ->filter()
                            ->values()
                            ->toArray();

                        Log::info("Aspects from type_criteria with specific IDs", ['aspects' => $allAspects]);
                    }
                }

                // If STILL no aspects found for this project, get ALL aspects from type_criteria
                // This ensures we don't lose data and show all available aspects
                if (empty($allAspects)) {
                    $allAspects = DB::table('type_criteria')
                        ->distinct()
                        ->pluck('aspect')
                        ->filter()
                        ->values()
                        ->toArray();

                    Log::info("Using ALL aspects from type_criteria as fallback", ['aspects' => $allAspects]);
                }

                // Only use default aspects as very last resort if database has no data at all
                if (empty($allAspects)) {
                    $allAspects = ['Komunikasi', 'Kerjasama', 'Kreativitas', 'Kepemimpinan', 'Inisiatif', 'Kemandirian', 'Tanggung Jawab', 'Adaptasi', 'Problem Solving'];
                    Log::info("Using comprehensive default aspects", ['aspects' => $allAspects]);
                }

                Log::info("Final aspects for project", [
                    'project_id' => $project->id,
                    'aspects' => $allAspects,
                    'count' => count($allAspects)
                ]);
            } catch (\Exception $e) {
                Log::error("Error getting aspects for project {$project->id}: " . $e->getMessage());
                $allAspects = ['Komunikasi', 'Kerjasama', 'Kreativitas', 'Kepemimpinan'];
            }

            // Ensure all classes have data for all aspects (fill missing with 0)
            foreach ($classComparison as &$class) {
                foreach ($allAspects as $aspect) {
                    if (!isset($class['aspect_scores'][$aspect])) {
                        $class['aspect_scores'][$aspect] = [
                            'avg_score' => 0,
                            'total_assessments' => 0,
                            'score_type' => $scoreType
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'project_info' => [
                    'batch_year' => $batchYear,
                    'project_name' => $projectName,
                    'score_type' => $scoreType
                ],
                'aspects' => $allAspects,
                'class_comparison' => $classComparison
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}