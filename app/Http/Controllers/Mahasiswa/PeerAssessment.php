<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Assessment;
use App\Models\AnswersPeer;
use App\Models\Group;
use App\Models\project;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Jobs\ProcessFlaskPeerAssessment;

class PeerAssessment extends Controller
{
    public function PeerAssessment(Request $request)
    {
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'assessment_order' => 'sometimes|string', 
        ]);

        return Inertia::render('Mahasiswa/PeerAssessmentMahasiswa', [
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
            'assessment_order' => $validated['assessment_order'],
        ]);
    }

    public function getUserInfo()
    {
        try {
            $user = auth()->user();

            $mahasiswa = Mahasiswa::with(['user', 'classRoom'])
                ->where('user_id', $user->id)
                ->first();

            if (!$mahasiswa) {
                return response()->json([
                    'message' => 'Data mahasiswa tidak ditemukan'
                ], 404);
            }

            // Ambil parameter dari query string atau gunakan null jika tidak ada
            $batchYear = request()->query('batch_year');
            $projectName = request()->query('project_name');

            Log::info('User Info Peer Request', [
                'user_id' => $user->id,
                'batch_year' => $batchYear,
                'project_name' => $projectName
            ]);

            // Cari group yang sesuai dengan batch_year dan project_name
            $specificGroup = Group::with('project')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->when($batchYear && $projectName, function ($query) use ($batchYear, $projectName) {
                    return $query->whereHas('project', function ($q) use ($batchYear, $projectName) {
                        $q->where('batch_year', $batchYear)
                            ->where('project_name', $projectName);
                    });
                })
                ->first();

            // Jika tidak ditemukan group spesifik, cari group terakhir
            $latestGroup = $specificGroup ?? Group::with('project')
                ->where('mahasiswa_id', $mahasiswa->id)
                ->orderBy('created_at', 'desc')
                ->first();

            $responseData = [
                'id' => $mahasiswa->id,
                'nim' => $mahasiswa->nim,
                'name' => $user->name,
                'kelas' => $mahasiswa->classRoom->class_name ?? '',
                'group' => $latestGroup ? $latestGroup->group : null,
                'project_name' => $latestGroup && $latestGroup->project ? $latestGroup->project->project_name : null,
                'batch_year' => $latestGroup && $latestGroup->project ? $latestGroup->project->batch_year : null
            ];

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error('Error in getUserInfoPeer: ' . $e->getMessage());
            return response()->json([
                'message' => 'Terjadi kesalahan saat mengambil data user'
            ], 500);
        }
    }

    public function getPeerByGroup(Request $request)
    {
        $user = auth()->user();
        $mahasiswaId = Mahasiswa::where('user_id', $user->id)->first()->id;
        $tahunAjaran = $request->input('batch_year');
        $projectName = $request->input('project_name');

        if (!$tahunAjaran || !$projectName) {
            return response()->json(['message' => 'Tahun ajaran atau proyek tidak ditemukan'], 400);
        }

        $project = Project::where('batch_year', $tahunAjaran)
            ->where('project_name', $projectName)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $userGroup = Group::where('mahasiswa_id', $mahasiswaId)
            ->where('batch_year', $tahunAjaran)
            ->where('project_id', $project->id)
            ->first();

        if (!$userGroup) {
            return response()->json(['message' => 'Group tidak ditemukan'], 404);
        }

        // Dapatkan semua pertanyaan assessment untuk project ini
        $assessmentQuestions = Assessment::where('batch_year', $tahunAjaran)
            ->where('project_id', $project->id)
            ->where('type', 'peerAssessment')
            ->get();

        if ($assessmentQuestions->isEmpty()) {
            return response()->json(['message' => 'Tidak ada pertanyaan assessment untuk project ini'], 404);
        }

        // Dapatkan semua ID pertanyaan
        $questionIds = $assessmentQuestions->pluck('id')->toArray();
        $totalQuestions = count($questionIds);

        // Dapatkan semua peer dalam group yang sama, kecuali mahasiswa yang login
        $allPeers = Group::with(['mahasiswa.user'])
            ->where('group', $userGroup->group)
            ->where('batch_year', $tahunAjaran)
            ->where('project_id', $project->id)
            ->where('mahasiswa_id', '!=', $mahasiswaId)
            ->get();

        $result = [];

        // Untuk setiap peer, periksa apakah semua pertanyaan sudah dijawab
        foreach ($allPeers as $group) {
            $peerId = $group->mahasiswa_id;

            // Dapatkan jawaban yang sudah diisi oleh mahasiswa untuk peer ini
            $answeredQuestions = AnswersPeer::where('mahasiswa_id', $mahasiswaId)
                ->where('peer_id', $peerId)
                ->whereIn('question_id', $questionIds)
                ->pluck('question_id')
                ->toArray();

            // Hitung jumlah jawaban yang sudah diisi
            $answeredCount = count($answeredQuestions);

            // Pastikan apakah benar-benar perlu ditampilkan
            // Jika jumlah pertanyaan yang terjawab kurang dari total pertanyaan
            if ($answeredCount < $totalQuestions) {
                // Verifikasi status peer review yang belum selesai
                $missingQuestionIds = array_diff($questionIds, $answeredQuestions);

                if (!empty($missingQuestionIds)) {
                    $result[] = [
                        'id' => $group->mahasiswa->user->id,
                        'mahasiswa_id' => $group->mahasiswa_id,
                        'name' => $group->mahasiswa->user->name,
                        'nim' => $group->mahasiswa->nim,
                        'user' => [
                            'id' => $group->mahasiswa->user->id,
                            'name' => $group->mahasiswa->user->name
                        ],
                        'answered_count' => $answeredCount,
                        'total_questions' => $totalQuestions,
                        'missing_questions' => count($missingQuestionIds)
                    ];
                }
            }
        }

        return response()->json($result);
    }

    public function searchByNim(Request $request)
    {
        try {
            $nim = $request->query('nim');

            $mahasiswa = Mahasiswa::where('nim', $nim)->first();

            if (!$mahasiswa) {
                return response()->json(['error' => 'Mahasiswa tidak ditemukan'], 404);
            }

            return response()->json([
                'mahasiswa_id' => $mahasiswa->id,
                'name' => $mahasiswa->user->name
            ]);
        } catch (\Exception $e) {
            Log::error('Error in searchByNim: ' . $e->getMessage());
            return response()->json([
                'error' => 'Terjadi kesalahan saat mencari data'
            ], 500);
        }
    }

    public function getQuestionsByProject(Request $request)
{
    try {
        $user = auth()->user();
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');
        $assessment_order = $request->query('assessment_order', '1'); // Tambahkan parameter assessment_order dengan default '1'

        Log::info('getQuestionsByProject request:', [
            'batch_year' => $batch_year,
            'project_name' => $project_name,
            'assessment_order' => $assessment_order,
            'user_id' => $user->id
        ]);

        // Validasi input
        if (empty($batch_year) || empty($project_name)) {
            return response()->json(['message' => 'Tahun batch dan nama proyek harus diisi'], 400);
        }

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        if (!$mahasiswa) {
            return response()->json(['message' => 'Data mahasiswa tidak ditemukan'], 404);
        }

        $project = Project::where('project_name', $project_name)
                          ->where('batch_year', $batch_year)
                          ->first();
        if (!$project) {
            return response()->json(['message' => 'Project tidak ditemukan'], 404);
        }

        $group = Group::where('mahasiswa_id', $mahasiswa->id)
                      ->where('project_id', $project->id)
                      ->first();

        if (!$group) {
            return response()->json(['message' => 'Data kelompok tidak ditemukan'], 404);
        }

        $assessments = Assessment::join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'assessment.skill_type',
                'assessment.assessment_order', // Tambahkan field assessment_order
                'type_criteria.aspect',       
                'type_criteria.criteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->where('assessment.project_id', $project->id)
            ->where('assessment.type', 'peerAssessment')
            ->where('assessment.assessment_order', $assessment_order) // Filter berdasarkan assessment_order
            ->where('assessment.is_published', 1) // Hanya tampilkan yang sudah dipublish
            ->get()
            ->map(function ($assessment) {
                return [
                    'id' => $assessment->id,
                    'type' => $assessment->type,
                    'question' => $assessment->question,
                    'skill_type' => $assessment->skill_type,
                    'assessment_order' => $assessment->assessment_order, // Sertakan di hasil
                    'aspect' => $assessment->aspect,
                    'criteria' => $assessment->criteria,
                    'bobot_1' => $assessment->bobot_1,
                    'bobot_2' => $assessment->bobot_2,
                    'bobot_3' => $assessment->bobot_3,
                    'bobot_4' => $assessment->bobot_4,
                    'bobot_5' => $assessment->bobot_5
                ];
            });

        Log::info('Assessment query result:', [
            'count' => $assessments->count(),
            'assessment_order' => $assessment_order,
            'first_item' => $assessments->first()
        ]);

        $groupMembers = Group::where('project_id', $project->id)
            ->where('group', $group->group)
            ->where('mahasiswa_id', '!=', $mahasiswa->id)
            ->with(['mahasiswa.user:id,name'])
            ->get()
            ->map(function ($member) {
                return [
                    'mahasiswa_id' => $member->mahasiswa_id,
                    'name' => $member->mahasiswa->user->name
                ];
            });

        $response = [
            'status' => 'success',
            'data' => [
                'assessments' => $assessments,
                'group_members' => $groupMembers,
                'project_details' => [
                    'project_id' => $project->id,
                    'project_name' => $project->project_name,
                    'group' => $group->group,
                    'batch_year' => $project->batch_year,
                    'assessment_order' => $assessment_order
                ]
            ]
        ];

        Log::info('Final response:', $response);

        return response()->json($response);
    } catch (\Exception $e) {
        Log::error('Error in getQuestionsByProject: ' . $e->getMessage(), [
            'trace' => $e->getTrace()
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan saat mengambil data pertanyaan',
            'error' => $e->getMessage()
        ], 500);
    }
}

    public function AnswersPeer(Request $request)
    {
        try {
            $validated = $request->validate([
                'mahasiswa_id' => 'required|string|exists:mahasiswa,id',
                'peer_id' => 'required|string|exists:mahasiswa,id',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            if ($validated['mahasiswa_id'] === $validated['peer_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak dapat menilai diri sendiri.',
                ], 400);
            }

            $mahasiswaGroup = Group::where('mahasiswa_id', $validated['mahasiswa_id'])
                ->where('project_id', function ($query) use ($validated) {
                    $query->select('project_id')
                        ->from('assessment')
                        ->where('id', $validated['question_id']);
                })
                ->first();

            $peerGroup = Group::where('mahasiswa_id', $validated['peer_id'])
                ->where('project_id', $mahasiswaGroup->project_id ?? null)
                ->first();

            if (!$mahasiswaGroup || !$peerGroup || $mahasiswaGroup->group !== $peerGroup->group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa dan peer harus berada dalam grup yang sama.',
                ], 400);
            }

            DB::beginTransaction();
            try {
                $existingAnswer = AnswersPeer::where([
                    'mahasiswa_id' => $validated['mahasiswa_id'],
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

                $simpleAnswerData = [
                    'question_id' => $validated['question_id'],
                    'answer' => $validated['answer'],
                    'score' => $validated['score']
                ];

                ProcessFlaskPeerAssessment::dispatch($simpleAnswerData, $answer->id)
                    ->onQueue('flask-peer-processing');

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Answers saved successfully.',
                    'data' => $answer,
                ], 201);
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
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

    // public function saveAllAnswersPeer(Request $request)
    // {
    //     $request->validate([
    //         'answers' => 'required|array',
    //         'answers.*.mahasiswa_id' => 'required|string|exists:mahasiswa,id',
    //         'answers.*.peer_id' => 'required|string|exists:mahasiswa,id',
    //         'answers.*.question_id' => 'required|exists:assessment,id',
    //         'answers.*.answer' => 'required|string',
    //         'answers.*.score' => 'required|numeric|min:0|max:100',
    //         'answers.*.status' => 'required|string'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         foreach ($request->answers as $answerData) {
    //             AnswersPeer::updateOrCreate(
    //                 [
    //                     'mahasiswa_id' => $answerData['mahasiswa_id'],
    //                     'peer_id' => $answerData['peer_id'],
    //                     'question_id' => $answerData['question_id']
    //                 ],
    //                 [
    //                     'answer' => $answerData['answer'],
    //                     'score' => $answerData['score'],
    //                     'status' => $answerData['status']
    //                 ]
    //             );
    //         }

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Answers saved successfully',
    //             'status' => 'success'
    //         ]);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Failed to save answers',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }



    public function saveAllAnswersPeer(Request $request)
    {
        $request->validate([
            'answers' => 'required|array',
            'answers.*.mahasiswa_id' => 'required|string|exists:mahasiswa,id',
            'answers.*.peer_id' => 'required|string|exists:mahasiswa,id',
            'answers.*.question_id' => 'required|exists:assessment,id',
            'answers.*.answer' => 'required|string',
            'answers.*.score' => 'required|numeric|min:0|max:100',
            'answers.*.status' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $savedAnswers = [];

            foreach ($request->answers as $answerData) {
                // Simpan jawaban ke database
                $answer = AnswersPeer::updateOrCreate(
                    [
                        'mahasiswa_id' => $answerData['mahasiswa_id'],
                        'peer_id' => $answerData['peer_id'],
                        'question_id' => $answerData['question_id']
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );

                $savedAnswers[] = $answer;

                // Prepare simplified data for the Flask job
                $simpleAnswerData = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score']
                ];

                // Kirim job ke queue untuk diproses di background
                ProcessFlaskPeerAssessment::dispatch($simpleAnswerData, $answer->id)
                    ->onQueue('flask-peer-processing');
            }

            DB::commit();

            return response()->json([
                'message' => 'Answers saved successfully.',
                'status' => 'success'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAllAnswersPeer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to save answers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getAnswerPeer($questionId, Request $request)
    {
        try {
            $validated = $request->validate([
                'mahasiswa_id' => 'required|string|exists:mahasiswa,id',
                'peer_id' => 'required|string|exists:mahasiswa,id',
            ]);

            if (!Assessment::where('id', $questionId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Question ID tidak valid.'
                ], 404);
            }

            $answer = AnswersPeer::where([
                'mahasiswa_id' => $validated['mahasiswa_id'],
                'peer_id' => $validated['peer_id'],
                'question_id' => $questionId
            ])->first();

            return response()->json($answer);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in getAnswerPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil jawaban.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getExistingPeerAnswers(Request $request)
    {
        try {
            $validated = $request->validate([
                'mahasiswa_id' => 'required|string|exists:mahasiswa,id',
                'peer_id' => 'required|string|exists:mahasiswa,id',
                'question_id' => 'required|string|exists:assessment,id'
            ]);

            $answers = AnswersPeer::where([
                'mahasiswa_id' => $validated['mahasiswa_id'],
                'peer_id' => $validated['peer_id'],
                'question_id' => $validated['question_id'],
            ])->get();

            return response()->json($answers);
        } catch (\Exception $e) {
            Log::error('Error in getExistingPeerAnswers:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil jawaban.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function answeredPeers(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
            if (!$mahasiswa) {
                return response()->json(['message' => 'Mahasiswa data not found'], 404);
            }

            $userGroup = Group::where('mahasiswa_id', $mahasiswa->id)
                ->where('project_id', $request->project_id)
                ->value('group');

            if (!$userGroup) {
                return response()->json(['answered_peers' => []]);
            }

            $answeredPeers = AnswersPeer::where('mahasiswa_id', $mahasiswa->id)
                ->whereIn('peer_id', function ($query) use ($userGroup, $request) {
                    $query->select('mahasiswa_id')
                        ->from('group')
                        ->where('group', $userGroup)
                        ->where('project_id', $request->project_id);
                })
                ->select('peer_id')
                ->distinct()
                ->pluck('peer_id');

            return response()->json([
                'answered_peers' => $answeredPeers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error fetching answered peers',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
