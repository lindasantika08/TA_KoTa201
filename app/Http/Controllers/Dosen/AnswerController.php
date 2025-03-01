<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Kelompok;
use App\Models\Answers;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use App\Models\Project;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Group;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnswerController extends Controller
{
    public function answerSelf(Request $request)
    {
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
        ]);

        return Inertia::render('Dosen/AnswerSelf', [
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
        ]);
    }

    public function answerPeer(Request $request)
    {
        $validated = $request->validate([
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        Log::info('AnswerPeer method called', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);

        return Inertia::render('Dosen/AnswerPeer', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
    }

    public function saveAllAnswersPeerDosen(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string'
            ]);

            foreach ($validated['answers'] as $answerData) {
                AnswersPeer::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'user_id' => auth()->id()
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );
            }

            DB::commit();
            Log::info('Transaction committed');
            return response()->json([
                'success' => true,
                'message' => 'Semua jawaban berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction rolled back', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getQuestionId(Request $request)
    {
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
        ]);

        $assessment = Assessment::join('project', 'assessment.project_id', '=', 'project.id')
            ->where('assessment.batch_year', $validated['batch_year'])
            ->where('project.project_name', $validated['project_name'])
            ->where('assessment.type', 'selfAssessment')
            ->select('assessment.id')
            ->first();

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        return response()->json([
            'questionId' => $assessment->id,
        ]);
    }

    public function getListAnswers(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectId = $request->query('project_id');

        // Validate input
        if (!$batchYear || !$projectId) {
            return response()->json(['message' => 'Batch year and project ID are required.'], 400);
        }

        // Count total self-assessment questions for the project and batch year
        $totalQuestions = Assessment::where('batch_year', $batchYear)
            ->where('project_id', $projectId)
            ->where('type', 'selfAssessment')
            ->count();

        if ($totalQuestions == 0) {
            return response()->json(['message' => 'No self-assessment questions found for the specified batch year and project.'], 404);
        }

        // Get user IDs in the group for the specific project and batch year
        $usersInGroup = Group::where('batch_year', $batchYear)
            ->where('project_id', $projectId)
            ->pluck('mahasiswa_id');

        // Fetch answers for the specific project, batch year, and self-assessment type
        $answers = Answers::whereIn('mahasiswa_id', $usersInGroup)
            ->join('assessment', 'answers.question_id', '=', 'assessment.id')
            ->where('assessment.batch_year', $batchYear)
            ->where('assessment.project_id', $projectId)
            ->where('assessment.type', 'selfAssessment')
            ->get();

        $userAnswers = $answers->groupBy('mahasiswa_id');

        $result = [];

        foreach ($usersInGroup as $mahasiswaId) {
            $mahasiswa = Mahasiswa::find($mahasiswaId);

            $userAnswered = isset($userAnswers[$mahasiswaId]) ? $userAnswers[$mahasiswaId] : collect();

            $answeredQuestionIds = $userAnswered->pluck('question_id');

            // Determine submission status
            if ($answeredQuestionIds->count() === 0) {
                $status = 'unsubmitted';
            } elseif ($answeredQuestionIds->count() < $totalQuestions) {
                $status = 'on progress';
            } elseif ($answeredQuestionIds->count() === $totalQuestions) {
                $status = 'submitted';
            } else {
                $status = 'unsubmitted';
            }

            $result[] = [
                'mahasiswa' => $mahasiswa,
                'status' => $status,
                'answers' => $userAnswered,
            ];
        }

        return response()->json($result);
    }

    public function getStatisticsPeer(Request $request)
    {
        try {
            $batchYear = $request->query('batch_year');
            $projectName = $request->query('project_name');

            // Validate required parameters
            if (!$batchYear || !$projectName) {
                return response()->json([
                    'message' => 'Batch year and project name are required'
                ], 400);
            }

            // Find the specific project
            $project = Project::where('batch_year', $batchYear)
                ->where('project_name', $projectName)
                ->first();

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found for the specified batch year and project name'
                ], 404);
            }

            // Get all groups for this specific project with their members and class information
            $allGroups = Group::where('project_id', $project->id)
                ->with(['mahasiswa.classRoom'])
                ->select('id', 'group', 'project_id', 'mahasiswa_id')
                ->get();

            // Group the groups by group name AND class_id
            $uniqueGroups = $allGroups->groupBy(function ($group) {
                return $group->group . '_' . ($group->mahasiswa->class_id ?? 'unknown');
            })->map(function ($groups) {
                $firstMember = $groups->first()->mahasiswa;
                return [
                    'group_id' => $groups->first()->id,
                    'group_name' => $groups->first()->group,
                    'project_id' => $groups->first()->project_id,
                    'class_id' => $firstMember ? $firstMember->class_id : null,
                    'class_name' => $firstMember && $firstMember->classRoom ? $firstMember->classRoom->class_name : null
                ];
            })->values();

            $groupStatistics = $uniqueGroups->map(function ($uniqueGroup) use ($project) {
                // Find all members for this specific group and class
                $groupMembers = Mahasiswa::whereHas('group', function ($query) use ($uniqueGroup, $project) {
                    $query->where('project_id', $project->id)
                        ->where('group', $uniqueGroup['group_name']);
                })
                    ->where('class_id', $uniqueGroup['class_id'])
                    ->with('user', 'classRoom')
                    ->get();

                // Log for debugging
                Log::info('Group details', [
                    'group_name' => $uniqueGroup['group_name'],
                    'class_id' => $uniqueGroup['class_id'],
                    'member_count' => $groupMembers->count()
                ]);

                // If no members found
                if ($groupMembers->isEmpty()) {
                    return [
                        'group_id' => $uniqueGroup['group_id'],
                        'group_name' => $uniqueGroup['group_name'],
                        'is_completed' => false,
                        'total_members' => 0,
                        'class_id' => $uniqueGroup['class_id'],
                        'class_name' => $uniqueGroup['class_name']
                    ];
                }

                // Check if every member has completed peer assessments for every other member
                $isGroupCompleted = $groupMembers->every(function ($member) use ($groupMembers, $project) {
                    $otherMemberIds = $groupMembers->pluck('id')
                        ->filter(fn($peerId) => $peerId != $member->id);

                    return $otherMemberIds->every(function ($peerId) use ($member, $project) {
                        return AnswersPeer::where('mahasiswa_id', $member->id)
                            ->where('peer_id', $peerId)
                            ->whereHas('question', function ($query) use ($project) {
                                $query->where('project_id', $project->id);
                            })
                            ->count() > 0;
                    });
                });

                return [
                    'group_id' => $uniqueGroup['group_id'],
                    'group_name' => $uniqueGroup['group_name'],
                    'is_completed' => $isGroupCompleted,
                    'total_members' => $groupMembers->count(),
                    'class_id' => $uniqueGroup['class_id'],
                    'class_name' => $uniqueGroup['class_name'],
                    'members' => $groupMembers->map(function ($member) {
                        return [
                            'id' => $member->id,
                            'nim' => $member->nim,
                            'name' => $member->user->name ?? null,
                            'class_name' => $member->classRoom->class_name ?? null
                        ];
                    })
                ];
            });

            // Group statistics by class for better organization
            $statisticsByClass = $groupStatistics->groupBy('class_id')
                ->map(function ($groups) {
                    $totalGroups = $groups->count();
                    $completedGroups = $groups->where('is_completed', true)->count();

                    return [
                        'class_name' => $groups->first()['class_name'],
                        'totalGroups' => $totalGroups,
                        'completedGroups' => $completedGroups,
                        'incompleteGroups' => $totalGroups - $completedGroups,
                        'groups' => $groups
                    ];
                });

            // Calculate overall statistics
            $totalGroups = $groupStatistics->count();
            $completedGroups = $groupStatistics->where('is_completed', true)->count();

            return response()->json([
                'totalGroup' => $totalGroups,
                'groupSudahLengkap' => $completedGroups,
                'groupBelumLengkap' => $totalGroups - $completedGroups,
                'statisticsByClass' => $statisticsByClass,
                'groupStatistics' => $groupStatistics,
                'projectInfo' => [
                    'batch_year' => $project->batch_year,
                    'project_name' => $project->project_name
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getStatisticsPeer', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'batch_year' => $request->query('batch_year'),
                'project_name' => $request->query('project_name')
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getListAnswersPeer(Request $request)
    {
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');

        Log::info('Parameter diterima:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
        ]);

        if (!$tahunAjaran || !$namaProyek) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap.',
            ], 400);
        }

        try {
            $project = Project::where('project_name', $namaProyek)
                ->where('batch_year', $tahunAjaran)
                ->with(['groups' => function ($query) {
                    $query->select('id', 'project_id', 'group')
                        ->distinct('group');
                }])
                ->firstOrFail();

            $groups = $project->groups->pluck('group')->unique()->values();

            $answers = AnswersPeer::whereHas('question', function ($query) use ($tahunAjaran) {
                $query->where('batch_year', $tahunAjaran);
            })
                ->whereHas('mahasiswa.group.project', function ($query) use ($namaProyek) {
                    $query->where('project_name', $namaProyek);
                })
                ->with([
                    'mahasiswa' => function ($query) {
                        $query->with(['user' => function ($q) {
                            $q->select('id', 'name', 'email');
                        }]);
                    },
                    'peer' => function ($query) {
                        $query->with(['user' => function ($q) {
                            $q->select('id', 'name', 'email');
                        }]);
                    },
                    'question' => function ($query) {
                        $query->select('id', 'question');
                    }
                ])
                ->get();

            $transformedAnswers = $answers->map(function ($answer) {
                $group = Group::where('mahasiswa_id', $answer->mahasiswa_id)
                    ->value('group');
                try {
                    return [
                        'id' => $answer->id,
                        'user' => $answer->mahasiswa->user ? [
                            'id' => $answer->mahasiswa->user->id,
                            'name' => $answer->mahasiswa->user->name,
                            'email' => $answer->mahasiswa->user->email,
                        ] : null,
                        'peer' => $answer->peer->user ? [
                            'id' => $answer->peer->user->id,
                            'name' => $answer->peer->user->name,
                            'email' => $answer->peer->user->email,
                        ] : null,
                        'pertanyaan' => optional($answer->question)->question,
                        'answer' => $answer->answer,
                        'score' => $answer->score,
                        'status' => $answer->status,
                        'kelompok' => $group ?? '-'
                    ];
                } catch (\Exception $e) {
                    Log::error('Error transforming answer: ' . $e->getMessage(), [
                        'answer_id' => $answer->id
                    ]);
                    return null;
                }
            })->filter()->values();

            if ($transformedAnswers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $transformedAnswers,
                'groups' => $groups
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::error('Project not found: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching answers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getListAnswerPeer()
    {
        return Inertia::render('Dosen/ListAnswerPeer');
    }

    public function getListAnswersView(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');

        $project = Project::where('project_name', $project_name)
            ->where('batch_year', $batch_year)
            ->firstOrFail();

        return Inertia::render('Dosen/AnswersSelfAssessment', [
            'batch_year' => $batch_year,
            'projectId' => $project->id,
            'project_name' => $project->project_name,  // Add this line
        ]);
    }

    public function getListAnswersPeerView(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');

        $project = Project::where('project_name', $project_name)
            ->where('batch_year', $batch_year)
            ->firstOrFail();

        $groupCount = Group::where('project_id', $project->id)
            ->distinct('group')
            ->count('group');

        return Inertia::render('Dosen/AnswersPeerAssessment', [
            'batch_year' => $batch_year,
            'projectId' => $project->id,
            'project_name' => $project->project_name,
            'totalGroups' => $groupCount
        ]);
    }

    public function searchByNip(Request $request)
    {
        $nim = $request->query('nip');
        $user = User::where('nip', $nim)->first();
        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function getAnswerPeerDosen($questionId)
    {
        try {
            $answer = AnswersPeer::where('question_id', $questionId)
                ->where('user_id', auth()->id())
                ->first();

            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function answeredPeersDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'nullable|string',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            $peer_id = $validated['peer_id'] ?? null;

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $peer_id,
                'question_id' => $validated['question_id'],
            ])->first();

            if ($existingAnswer) {
                $existingAnswer->update([
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]);
                $answer = $existingAnswer;
            } else {
                $answer = AnswersPeer::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function AnswersPeerDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => '-',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $validated['peer_id'],
                'question_id' => $validated['question_id'],
            ])->first();

            if ($existingAnswer) {
                $existingAnswer->update([
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]);
                $answer = $existingAnswer;
            } else {
                $answer = AnswersPeer::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStatistics(Request $request)
    {
        try {
            $batchYear = $request->query('batch_year');
            $projectId = $request->query('project_id');

            // Find groups for specific project with mahasiswa loaded
            $groups = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->with('mahasiswa.user') // Eager load mahasiswa and user
                ->get();

            $usersAlreadyFilled = Answers::whereHas('question', function ($query) use ($batchYear, $projectId) {
                $query->where('batch_year', $batchYear)
                    ->where('project_id', $projectId);
            })
                ->whereIn('mahasiswa_id', $groups->pluck('mahasiswa_id'))
                ->distinct('mahasiswa_id')
                ->count();

            // Prepare details of users who haven't submitted
            $submissionStatus = $groups->map(function ($item) use ($batchYear, $projectId) {
                $isSubmitted = Answers::whereHas('question', function ($query) use ($batchYear, $projectId) {
                    $query->where('batch_year', $batchYear)
                        ->where('project_id', $projectId);
                })
                    ->where('mahasiswa_id', $item->mahasiswa_id)
                    ->exists();

                return [
                    'index' => $item->id,
                    'mahasiswaName' => optional($item->mahasiswa->user)->name ?? 'Unknown', // Safely retrieve name
                    'status' => $isSubmitted ? 'submitted' : 'unsubmitted'
                ];
            });

            return response()->json([
                'totalKeseluruhan' => $groups->count(),
                'totalSudahMengisi' => $usersAlreadyFilled,
                'submissionStatus' => $submissionStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Statistics Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Fatal Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getDetails(Request $request)
    {
        $validated = $request->validate([
            'mahasiswaName' => 'required|string', // Changed from userName
            'batch_year' => 'required|string', // Changed from tahun_ajaran
            'project_name' => 'required|string', // Changed from nama_proyek
            'project_id' => 'required|string', // Added project_id
        ]);

        return Inertia::render('Dosen/AnswerDetailSelf', [
            'mahasiswaName' => $validated['mahasiswaName'],
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
            'project_id' => $validated['project_id'],
        ]);
    }

    public function getDetailsAnswer(Request $request)
    {
        $validated = $request->validate([
            'mahasiswaName' => 'required|string', // Changed from userName
            'batch_year' => 'required|string', // Changed from tahun_ajaran
            'project_name' => 'required|string', // Changed from nama_proyek
            'project_id' => 'required|string', // Added project_id
        ]);

        // Find user by name
        $user = User::where('name', $validated['mahasiswaName'])->first();

        if (!$user) {
            return response()->json(['error' => 'Pengguna tidak ditemukan'], 404);
        }

        // Find mahasiswa associated with user
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        if (!$mahasiswa) {
            return response()->json(['error' => 'Mahasiswa tidak ditemukan'], 404);
        }

        $answers = Answers::with('question')
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('question', function ($query) use ($validated) {
                $query->where('batch_year', $validated['batch_year'])
                    ->where('project_id', $validated['project_id']);
            })
            ->get();

        if ($answers->isEmpty()) {
            return response()->json(['message' => 'Jawaban tidak ditemukan untuk mahasiswa ini'], 404);
        }

        return response()->json([
            'answers' => $answers->map(function ($answer) {
                return [
                    'pertanyaan' => $answer->question->question,
                    'jawaban' => $answer->answer,
                    'skor' => $answer->score,
                ];
            }),
        ]);
    }
}
