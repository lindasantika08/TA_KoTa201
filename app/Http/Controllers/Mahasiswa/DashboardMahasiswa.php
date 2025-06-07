<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Group;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Report;
use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Models\Feedback;
use App\Models\feedback_ai;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;


class DashboardMahasiswa extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Mahasiswa/Dashboard');
    }

    public function getMahasiswaByClass($classId)
    {
        $mahasiswaKelas = User::whereHas('mahasiswa', function ($query) use ($classId) {
            $query->where('class_id', $classId);
        })->get();

        return response()->json([
            'data' => $mahasiswaKelas
        ]);
    }

    public function getUserProject()
    {
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json(['projects' => []]);
        }

        $projects = $mahasiswa->group()->with('project')
            ->whereHas('project', function ($query) {
                $query->where('status', 'Active');
            })
            ->get()
            ->map(function ($group) {
                return [
                    'project_name' => $group->project->project_name,
                    'batch_year' => $group->project->batch_year,
                    'project_id' => $group->project->id,
                    'group_id' => $group->id
                ];
            });

        return response()->json(['projects' => $projects]);
    }

    public function getSelfAssessmentStatus()
    {
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started',
                'projects' => []
            ]);
        }

        $userGroups = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with('project')
            ->get();

        if ($userGroups->isEmpty()) {
            return response()->json([
                'selfAssessmentStatus' => 'Not Started',
                'peerAssessmentStatus' => 'Not Started',
                'projects' => []
            ]);
        }

        $projectStatuses = $userGroups->map(function ($group) use ($mahasiswa) {
            $project = $group->project;

            $selfAssessmentQuestions = Assessment::where('type', 'selfAssessment')
                ->where('project_id', $project->id)
                ->where('is_published', true)
                ->count();

            $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')
                ->where('project_id', $project->id)
                ->where('is_published', true)
                ->count();

            $groupPeers = Group::where('project_id', $project->id)
                ->where('group', $group->group)
                ->where('mahasiswa_id', '!=', $mahasiswa->id)
                ->pluck('mahasiswa_id');

            $selfAssessmentCount = Answers::whereHas('question', function ($query) use ($project) {
                $query->where('type', 'selfAssessment')
                    ->where('project_id', $project->id)
                    ->where('is_published', true);
            })
                ->where('mahasiswa_id', $mahasiswa->id)
                ->count();

            $totalExpectedPeerAnswers = $peerAssessmentQuestions * count($groupPeers);

            $peerAssessmentCount = Answers::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('question', function ($query) use ($project) {
                    $query->where('type', 'peerAssessment')
                        ->where('project_id', $project->id)
                        ->where('is_published', true);
                })
                ->count();

            $latestSelfAssessment = Assessment::where('type', 'selfAssessment')
                ->where('project_id', $project->id)
                ->where('is_published', true)
                ->orderBy('assessment_order', 'desc')
                ->first();

            $latestPeerAssessment = Assessment::where('type', 'peerAssessment')
                ->where('project_id', $project->id)
                ->where('is_published', true)
                ->orderBy('assessment_order', 'desc')
                ->first();

            $selfAssessmentStatus = $selfAssessmentCount == 0
                ? 'Not Started'
                : ($selfAssessmentCount < $selfAssessmentQuestions ? 'Pending' : 'Completed');

            $peerAssessmentStatus = $peerAssessmentCount == 0
                ? 'Not Started'
                : ($peerAssessmentCount < $totalExpectedPeerAnswers ? 'Pending' : 'Completed');

            return [
                'batch_year' => $project->batch_year,
                'project_name' => $project->project_name,
                'selfAssessmentStatus' => $selfAssessmentStatus,
                'peerAssessmentStatus' => $peerAssessmentStatus,
                'selfAssessmentCount' => $selfAssessmentCount,
                'peerAssessmentCount' => $peerAssessmentCount,
                'selfAssessmentQuestions' => $selfAssessmentQuestions,
                'totalExpectedPeerAnswers' => $totalExpectedPeerAnswers,
                'groupPeersCount' => count($groupPeers),
                'latest_self_assessment_order' => $latestSelfAssessment ? $latestSelfAssessment->assessment_order : null,
                'latest_peer_assessment_order' => $latestPeerAssessment ? $latestPeerAssessment->assessment_order : null,
                'latest_self_assessment_end_date' => $latestSelfAssessment ? $latestSelfAssessment->end_date : null,
                'latest_peer_assessment_end_date' => $latestPeerAssessment ? $latestPeerAssessment->end_date : null
            ];
        });

        $overallSelfAssessmentStatus = $projectStatuses->every(function ($status) {
            return $status['selfAssessmentStatus'] === 'Completed';
        }) ? 'Completed' : ($projectStatuses->every(function ($status) {
            return $status['selfAssessmentStatus'] === 'Not Started';
        }) ? 'Not Started' : 'Pending');

        $overallPeerAssessmentStatus = $projectStatuses->every(function ($status) {
            return $status['peerAssessmentStatus'] === 'Completed';
        }) ? 'Completed' : ($projectStatuses->every(function ($status) {
            return $status['peerAssessmentStatus'] === 'Not Started';
        }) ? 'Not Started' : 'Pending');

        return response()->json([
            'overallSelfAssessmentStatus' => $overallSelfAssessmentStatus,
            'overallPeerAssessmentStatus' => $overallPeerAssessmentStatus,
            'projects' => $projectStatuses
        ]);
    }

    public function getPeerAssessmentDetails(Request $request)
    {
        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json([
                'status' => 'error',
                'message' => 'Mahasiswa not found'
            ], 404);
        }

        $selectedProject = $request->input('project');

        $query = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with('project');

        if ($selectedProject) {
            $query->whereHas('project', function ($q) use ($selectedProject) {
                $q->where('project_name', $selectedProject);
            });
        }

        $group = $query->first();

        if (!$group) {
            return response()->json([
                'status' => 'error',
                'message' => 'No projects found'
            ], 404);
        }

        $project = $group->project;

        $groupPeers = Group::where('project_id', $project->id)
            ->where('group', $group->group)
            ->where('mahasiswa_id', '!=', $mahasiswa->id)
            ->with('mahasiswa')
            ->get();

        $groupPeerIds = $groupPeers->pluck('mahasiswa_id');

        $hasPeerAssessment = Assessment::where('type', 'peerAssessment')
            ->where('project_id', $project->id)
            ->where('is_published', true)

            ->exists();

        if (!$hasPeerAssessment) {
            return response()->json([
                'group_size' => $groupPeers->count(),
                'group_peers' => $groupPeers->map(function ($peer) {
                    return [
                        'id' => $peer->mahasiswa_id,
                        'name' => $peer->mahasiswa->user->name
                    ];
                }),
                'completed_peer_assessments' => [],
                'peer_completed_count' => 0
            ]);
        }

        $peerAssessmentQuestions = Assessment::where('type', 'peerAssessment')
            ->where('project_id', $project->id)
            ->where('is_published', true)

            ->count();

        $completedPeerAssessments = $groupPeerIds->mapWithKeys(function ($peerId) use ($mahasiswa, $project, $peerAssessmentQuestions) {
            $completedCount = AnswersPeer::where('mahasiswa_id', $mahasiswa->id)
                ->whereHas('question', function ($query) use ($project, $peerId) {
                    $query->where('type', 'peerAssessment')
                        ->where('project_id', $project->id)
                        ->where('is_published', true);
                })
                ->where('peer_id', $peerId)
                ->count();

            $isCompleted = $completedCount == $peerAssessmentQuestions && $peerAssessmentQuestions > 0;

            return [$peerId => [
                'total_completed' => $completedCount,
                'total_questions' => $peerAssessmentQuestions,
                'is_completed' => $isCompleted
            ]];
        });

        $peerCompletedCount = $completedPeerAssessments->filter(function ($peer) {
            return $peer['is_completed'];
        })->count();

        return response()->json([
            'group_size' => $groupPeers->count(),
            'group_peers' => $groupPeers->map(function ($peer) {
                return [
                    'id' => $peer->mahasiswa_id,
                    'name' => $peer->mahasiswa->user->name
                ];
            }),
            'completed_peer_assessments' => $completedPeerAssessments,
            'peer_completed_count' => $peerCompletedCount
        ]);
    }

    public function getProjectScoreDetails(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectId = $request->input('project_id');
        $userId = Auth::id();

        Log::info('Request parameters:', [
            'batch_year' => $batchYear,
            'project_id' => $projectId,
            'user_id' => $userId
        ]);

        try {
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            Log::info('Mahasiswa found:', ['mahasiswa' => $mahasiswa]);

            if (!$mahasiswa) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            $group = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            Log::info('Group found:', ['group' => $group]);

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Group not found'
                ], 404);
            }

            $selfAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'selfAssessment')
                ->get();

            Log::info('Self Assessments found:', ['count' => $selfAssessments->count()]);

            $selfAspekKriteriaAnalysis = $this->analyzeAssessments(
                $selfAssessments,
                $mahasiswa->id,
                'selfAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $peerAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'peerAssessment')
                ->get();

            Log::info('Peer Assessments found:', ['count' => $peerAssessments->count()]);

            $peerAspekKriteriaAnalysis = $this->analyzeAssessments(
                $peerAssessments,
                $mahasiswa->id,
                'peerAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $userResults = [
                'user_id' => $userId,
                'name' => Auth::user()->name ?? 'Tidak dikenal',  
                'kelompok' => $group->group,  
                'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
            ];

            Log::info('Final response:', ['userResults' => $userResults]);

            return response()->json([
                'status' => 'success',
                'data' => $userResults
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProjectScoreDetails:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $batchYear, $projectId, $groupId)
    {
        Log::info('Starting analyzeAssessments', [
            'assessmentType' => $assessmentType,
            'mahasiswaId' => $mahasiswaId,
            'assessmentCount' => $assessments->count()
        ]);

        $filteredAssessments = $assessments->filter(function ($assessment) use ($batchYear, $projectId) {
            return $assessment->batch_year === $batchYear && $assessment->project_id === $projectId;
        });

        Log::info('Filtered assessments count:', ['count' => $filteredAssessments->count()]);

        if ($filteredAssessments->isEmpty()) {
            Log::warning('No assessments found after filtering');
            return collect([]);
        }

        $reports = Report::where('project_id', $projectId)
            ->where('group_id', $groupId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->get();
        
        Log::info('Reports found:', ['count' => $reports->count()]);

        $result = $filteredAssessments->groupBy(function ($assessment) {
            if (!$assessment->typeCriteria) {
                Log::error('typeCriteria relation not found for assessment:', ['assessment_id' => $assessment->id]);
                return null;
            }
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType, $reports) {
            $questionIds = $groupAssessments->pluck('id');

            $answers = $assessmentType === 'selfAssessment'
                ? Answers::whereIn('question_id', $questionIds)
                ->where('mahasiswa_id', $mahasiswaId)
                ->get()
                : AnswersPeer::whereIn('question_id', $questionIds)
                ->where('peer_id', $mahasiswaId)
                ->get();

            Log::info('Answers found for group:', [
                'questionIds' => $questionIds,
                'answersCount' => $answers->count()
            ]);

            $typeCriteria = $groupAssessments->first()->typeCriteria;

            $peerNames = $groupAssessments->map(function ($assessment) {
                return $assessment->peer->name ?? 'Unknown Peer'; 
            })->unique()->values();

            $questions = $groupAssessments->map(function ($assessment) use ($answers, $reports, $mahasiswaId, $assessmentType) {
                $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                $relatedReport = $reports->where('question_id', $assessment->id)
                    ->where('typeCriteria_id', $assessment->typeCriteria->id)
                    ->first();

                $finalScoreSelf = ($assessmentType === 'selfAssessment' && $relatedReport) ? $relatedReport->final_score_self : null;
                $finalScorePeer = ($assessmentType === 'peerAssessment' && $relatedReport) ? $relatedReport->final_score_peer : null;
                
                return [
                    'question_id' => $assessment->id,
                    'pertanyaan' => $assessment->question,
                    'score' => $relatedAnswer ? $relatedAnswer->score : null,
                    'answer' => $relatedAnswer ? $relatedAnswer->answer : null,
                    'final_score_self' => $finalScoreSelf,
                    'final_score_peer' => $finalScorePeer
                ];
            });

            $totalScoreSelf = null;
            $totalScorePeer = null;
            
            if ($assessmentType === 'selfAssessment') {
                $validScores = $questions->pluck('final_score_self')->filter()->values();
                $totalScoreSelf = $validScores->count() > 0 ? $validScores->avg() : null;
            } else { // peerAssessment
                $validScores = $questions->pluck('final_score_peer')->filter()->values();
                $totalScorePeer = $validScores->count() > 0 ? $validScores->avg() : null;
            }
            
            $totalScore = $answers->avg('score');

            return [
                'aspek' => $typeCriteria->aspect,
                'kriteria' => $typeCriteria->criteria,
                'total_score' => $totalScore,
                'total_score_self' => $totalScoreSelf,
                'total_score_peer' => $totalScorePeer,
                'total_answers' => $answers->count(),
                'questions' => $questions,
                'peer_names' => $peerNames 
            ];
        });

        Log::info('Analysis result count:', ['count' => $result->count()]);

        return $result;
    }    

    public function getProjectScoreDetails(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectId = $request->input('project_id');
        $userId = Auth::id();

        Log::info('Request parameters:', [
            'batch_year' => $batchYear,
            'project_id' => $projectId,
            'user_id' => $userId
        ]);

        try {
            $mahasiswa = Mahasiswa::where('user_id', $userId)->first();

            Log::info('Mahasiswa found:', ['mahasiswa' => $mahasiswa]);

            if (!$mahasiswa) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Mahasiswa not found'
                ], 404);
            }

            $group = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('mahasiswa_id', $mahasiswa->id)
                ->first();

            Log::info('Group found:', ['group' => $group]);

            if (!$group) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Group not found'
                ], 404);
            }

            $selfAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'selfAssessment')
                ->get();

            Log::info('Self Assessments found:', ['count' => $selfAssessments->count()]);

            $selfAspekKriteriaAnalysis = $this->analyzeAssessments(
                $selfAssessments,
                $mahasiswa->id,
                'selfAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $peerAssessments = Assessment::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->where('type', 'peerAssessment')
                ->get();

            Log::info('Peer Assessments found:', ['count' => $peerAssessments->count()]);

            $peerAspekKriteriaAnalysis = $this->analyzeAssessments(
                $peerAssessments,
                $mahasiswa->id,
                'peerAssessment',
                $batchYear,
                $projectId,
                $group->id
            );

            $userResults = [
                'user_id' => $userId,
                'name' => Auth::user()->name ?? 'Tidak dikenal',
                'kelompok' => $group->group,
                'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
            ];

            Log::info('Final response:', ['userResults' => $userResults]);

            return response()->json([
                'status' => 'success',
                'data' => $userResults
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getProjectScoreDetails:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $batchYear, $projectId, $groupId)
    {
        Log::info('Starting analyzeAssessments', [
            'assessmentType' => $assessmentType,
            'mahasiswaId' => $mahasiswaId,
            'assessmentCount' => $assessments->count()
        ]);

        $filteredAssessments = $assessments->filter(function ($assessment) use ($batchYear, $projectId) {
            return $assessment->batch_year === $batchYear && $assessment->project_id === $projectId;
        });

        Log::info('Filtered assessments count:', ['count' => $filteredAssessments->count()]);

        if ($filteredAssessments->isEmpty()) {
            Log::warning('No assessments found after filtering');
            return collect([]);
        }

        $reports = Report::where('project_id', $projectId)
            ->where('group_id', $groupId)
            ->where('mahasiswa_id', $mahasiswaId)
            ->get();

        Log::info('Reports found:', ['count' => $reports->count()]);

        $result = $filteredAssessments->groupBy(function ($assessment) {
            if (!$assessment->typeCriteria) {
                Log::error('typeCriteria relation not found for assessment:', ['assessment_id' => $assessment->id]);
                return null;
            }
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType, $reports) {
            $questionIds = $groupAssessments->pluck('id');

            $answers = $assessmentType === 'selfAssessment'
                ? Answers::whereIn('question_id', $questionIds)
                ->where('mahasiswa_id', $mahasiswaId)
                ->get()
                : AnswersPeer::whereIn('question_id', $questionIds)
                ->where('peer_id', $mahasiswaId)
                ->get();

            Log::info('Answers found for group:', [
                'questionIds' => $questionIds,
                'answersCount' => $answers->count()
            ]);

            $typeCriteria = $groupAssessments->first()->typeCriteria;

            $peerNames = $groupAssessments->map(function ($assessment) {
                return $assessment->peer->name ?? 'Unknown Peer';
            })->unique()->values();

            $questions = $groupAssessments->map(function ($assessment) use ($answers, $reports, $mahasiswaId, $assessmentType) {
                $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                $relatedReport = $reports->where('question_id', $assessment->id)
                    ->where('typeCriteria_id', $assessment->typeCriteria->id)
                    ->first();

                $finalScoreSelf = ($assessmentType === 'selfAssessment' && $relatedReport) ? $relatedReport->final_score_self : null;
                $finalScorePeer = ($assessmentType === 'peerAssessment' && $relatedReport) ? $relatedReport->final_score_peer : null;

                return [
                    'question_id' => $assessment->id,
                    'pertanyaan' => $assessment->question,
                    'score' => $relatedAnswer ? $relatedAnswer->score : null,
                    'answer' => $relatedAnswer ? $relatedAnswer->answer : null,
                    'final_score_self' => $finalScoreSelf,
                    'final_score_peer' => $finalScorePeer
                ];
            });

            $totalScoreSelf = null;
            $totalScorePeer = null;

            if ($assessmentType === 'selfAssessment') {
                $validScores = $questions->pluck('final_score_self')->filter()->values();
                $totalScoreSelf = $validScores->count() > 0 ? $validScores->avg() : null;
            } else { // peerAssessment
                $validScores = $questions->pluck('final_score_peer')->filter()->values();
                $totalScorePeer = $validScores->count() > 0 ? $validScores->avg() : null;
            }

            $totalScore = $answers->avg('score');

            return [
                'aspek' => $typeCriteria->aspect,
                'kriteria' => $typeCriteria->criteria,
                'total_score' => $totalScore,
                'total_score_self' => $totalScoreSelf,
                'total_score_peer' => $totalScorePeer,
                'total_answers' => $answers->count(),
                'questions' => $questions,
                'peer_names' => $peerNames
            ];
        });

        Log::info('Analysis result count:', ['count' => $result->count()]);

        return $result;
    }

    public function getFeedback(Request $request)
    {
        try {
            // Get the current authenticated user (mahasiswa)
            $user = Auth::user();
            $mahasiswaId = $user->mahasiswa->id;

            // Get project filter if provided
            $projectName = $request->get('project', null);

            // Log for debugging


            // Get lecturer feedback - ensure we load both dosen and group relationships
            $lecturerFeedbackQuery = Feedback::where('peer_id', $mahasiswaId)
                ->whereNotNull('dosen_id')
                ->with(['dosen', 'group.project']);

            // Filter by project if specified
            if ($projectName) {
                // Find the project with case-insensitive search
                $project = Project::where('project_name', 'LIKE', $projectName)
                    ->orWhere('project_name', 'LIKE', '%' . $projectName . '%')
                    ->first();



                if ($project) {
                    // Get groups for this project and student
                    $groupIds = Group::where('project_id', $project->id)
                        ->where('mahasiswa_id', $mahasiswaId)
                        ->pluck('id')
                        ->toArray();



                    // Filter feedback by these groups
                    if (!empty($groupIds)) {
                        $lecturerFeedbackQuery->whereIn('group_id', $groupIds);
                    } else {
                        // Alternative approach: filter using join instead of subquery
                        $lecturerFeedbackQuery->whereHas('group', function ($query) use ($project) {
                            $query->where('project_id', $project->id);
                        });
                    }
                } else {
                    // If project not found, return empty results
                    $lecturerFeedbackQuery->where('id', null); // This ensures no results

                }
            }

            // Execute the query and get the results
            $lecturerFeedback = $lecturerFeedbackQuery->get();

            // Loop through and check if dosen relation is loaded properly
            foreach ($lecturerFeedback as $index => $feedback) {
            }

            $lecturerFeedbackData = $lecturerFeedback->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'feedback' => $feedback->feedback,
                    'dosenName' => $feedback->dosen && $feedback->dosen->user ? $feedback->dosen->user->name : 'Unknown',
                    'createdAt' => $feedback->created_at->format('Y-m-d H:i:s'),
                    'groupId' => $feedback->group_id,
                    'projectName' => $feedback->group && $feedback->group->project ? $feedback->group->project->project_name : 'Unknown Project'
                ];
            });

            // Get peer feedback from feedback_ai
            $peerFeedbackQuery = feedback_ai::where('mahasiswa_id', $mahasiswaId);

            // Filter by project if specified
            if ($projectName) {
                if (isset($project) && $project) {
                    // Use the already found project instance
                    // Get groups for this project and student
                    if (!empty($groupIds)) {
                        // Use the already queried group IDs
                        $peerFeedbackQuery->whereIn('group_id', $groupIds);
                    } else {
                        // Alternative approach using nested relationship
                        $peerFeedbackQuery->whereHas('group', function ($query) use ($project) {
                            $query->where('project_id', $project->id);
                        });
                    }
                } else {
                    // Try to find the project again if not already found
                    $project = Project::where('project_name', 'LIKE', $projectName)
                        ->orWhere('project_name', 'LIKE', '%' . $projectName . '%')
                        ->first();

                    if ($project) {
                        // Get groups for this project and student
                        $groupIds = Group::where('project_id', $project->id)
                            ->where('mahasiswa_id', $mahasiswaId)
                            ->pluck('id')
                            ->toArray();

                        // Filter feedback by these groups
                        if (!empty($groupIds)) {
                            $peerFeedbackQuery->whereIn('group_id', $groupIds);
                        } else {
                            $peerFeedbackQuery->whereHas('group', function ($query) use ($project) {
                                $query->where('project_id', $project->id);
                            });
                        }
                    } else {
                        // If project not found, return empty results
                        $peerFeedbackQuery->where('id', null);
                    }
                }
            }

            // Execute the peer feedback query
            $peerFeedback = $peerFeedbackQuery->get();



            $peerFeedbackData = $peerFeedback->map(function ($feedback) {
                return [
                    'id' => $feedback->id,
                    'feedback' => $feedback->summary,
                    'createdAt' => $feedback->created_at instanceof \DateTime
                        ? $feedback->created_at->format('Y-m-d H:i:s')
                        : $feedback->created_at,
                    'groupId' => $feedback->group_id
                ];
            });

            // Return final response with debug info in development environment
            $response = [
                'success' => true,
                'data' => [
                    'lecturerFeedback' => $lecturerFeedbackData,
                    'peerFeedback' => $peerFeedbackData
                ]
            ];


            return response()->json($response);
        } catch (\Exception $e) {


            return response()->json([
                'success' => false,
                'message' => 'Error fetching feedback data',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
