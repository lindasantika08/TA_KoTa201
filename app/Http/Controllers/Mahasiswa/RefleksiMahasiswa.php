<?php

namespace App\Http\Controllers\Mahasiswa;

use Inertia\Inertia;
use App\Http\Controllers\Controller;

use App\Models\Mahasiswa;
use App\Models\Project;
use App\Models\Group;
use App\Models\Reflective;
use App\Models\ReflectiveAnswer;
use App\Models\ReflectiveRubric;
use App\Models\reflective_ai;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;

class RefleksiMahasiswa extends Controller
{
    public function reflectiveAssessment()
    {
        return Inertia::render('Mahasiswa/ProjectReflectiveAssessment');
    }

    public function getDataReflective()
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

            // Get the student's reflective assessments - modified to show each order separately
            $assessments = DB::table('groups')
                ->join('project', 'groups.project_id', '=', 'project.id')
                ->join('reflective_assessment', function ($join) {
                    $join->on('project.id', '=', 'reflective_assessment.project_id')
                        ->where('reflective_assessment.is_published', '=', 1); // Filter published assessments
                })
                ->where('groups.mahasiswa_id', $mahasiswa->id)
                ->select([
                    'groups.id as group_id',
                    'project.id as project_id',
                    'project.batch_year',
                    'project.project_name',
                    'project.status',
                    'groups.created_at',
                    'reflective_assessment.reflective_assessment_order as assessment_order'
                ])
                ->selectRaw('COUNT(reflective_assessment.id) as total_questions')
                ->selectRaw('(SELECT COUNT(DISTINCT ra.reflective_assessment_order) 
                       FROM reflective_assessment ra 
                       WHERE ra.project_id = project.id 
                       AND ra.is_published = 1) as total_orders')
                ->groupBy(
                    'groups.id',
                    'project.id',
                    'project.batch_year',
                    'project.project_name',
                    'project.status',
                    'groups.created_at',
                    'reflective_assessment.reflective_assessment_order'
                )
                ->having('total_questions', '>', 0)
                ->orderBy('project.batch_year', 'desc')
                ->orderBy('project.project_name')
                ->orderBy('reflective_assessment.reflective_assessment_order')
                ->get();

            return response()->json([
                'success' => true,
                'assessments' => $assessments
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching reflective assessments: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getReflectiveAssessment(Request $request)
    {
        Log::info('Request received in assessment:', $request->all());

        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'assessment_order' => 'required|string',
        ]);

        Log::info('Validated data:', $validated);

        return Inertia::render('Mahasiswa/ReflectiveAssessmentMahasiswa', [
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
            'assessment_order' => $validated['assessment_order'],
        ]);
    }

    public function getReflectiveQuestions(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');
        $assessmentOrder = $request->query('assessment_order');

        Log::info('Getting reflective questions for:', [
            'batch_year' => $batchYear,
            'project_name' => $projectName,
            'assessment_order' => $assessmentOrder
        ]);

        try {
            // Get project ID from project name
            // Using project_name column instead of name
            $project = Project::where('project_name', $projectName)
                ->where('batch_year', $batchYear)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            // Get questions with rubric details
            $questions = Reflective::where('batch_year', $batchYear)
                ->where('project_id', $project->id)
                ->where('reflective_assessment_order', $assessmentOrder)
                ->where('is_published', true)
                ->with('rubric')
                ->get();

            // Format questions with criteria from rubric
            $formattedQuestions = $questions->map(function ($question) {
                $questionData = $question->toArray();

                if ($question->rubric) {
                    $questionData['criteria_reflective'] = $question->rubric->criteria_reflective;
                    $questionData['bobot_1'] = $question->rubric->bobot_1;
                    $questionData['bobot_2'] = $question->rubric->bobot_2;
                    $questionData['bobot_3'] = $question->rubric->bobot_3;
                    $questionData['bobot_4'] = $question->rubric->bobot_4;
                    $questionData['bobot_5'] = $question->rubric->bobot_5;
                }

                return $questionData;
            });

            return response()->json($formattedQuestions);
        } catch (\Exception $e) {
            Log::error('Error getting reflective questions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load questions: ' . $e->getMessage()], 500);
        }
    }

    public function saveReflectiveAllAnswer(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.status' => 'required|string'
            ]);

            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa data not found'
                ], 404);
            }

            $savedAnswers = [];
            $projectId = null;
            $allAnswers = [];

            foreach ($validated['answers'] as $answer) {
                $reflectiveAnswer = ReflectiveAnswer::updateOrCreate(
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'question_id' => $answer['question_id'],
                        'answer' => $answer['answer'],
                        'status' => $answer['status'],
                    ]
                );

                // Get the question to determine project_id
                $question = Reflective::find($answer['question_id']);
                if ($question) {
                    $projectId = $question->project_id;

                    // Store question and answer for summary
                    $allAnswers[] = [
                        'question' => $question->question,
                        'answer' => $answer['answer']
                    ];
                }

                $savedAnswers[] = $reflectiveAnswer;
            }

            // Generate and save AI summary if we have a project ID
            if ($projectId && !empty($allAnswers)) {
                $this->generateAndSaveReflectiveSummary($mahasiswa->id, $projectId, $allAnswers);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All answers saved successfully.',
                'data' => $savedAnswers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in saveReflectiveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate a summary of reflective answers using Gemini AI and save to database
     *
     * @param string $mahasiswaId
     * @param string $projectId
     * @param array $answers
     * @return void
     */
    private function generateAndSaveReflectiveSummary($mahasiswaId, $projectId, $answers)
    {
        try {
            // Format questions and answers for the prompt
            $qaText = '';
            foreach ($answers as $qa) {
                $qaText .= "Pertanyaan: {$qa['question']}\n";
                $qaText .= "Jawaban: {$qa['answer']}\n\n";
            }

            // Get student information for more context
            $mahasiswa = Mahasiswa::with('user')->find($mahasiswaId);
            $project = Project::find($projectId);

            // Create the prompt for Gemini
            $prompt = "Analisis Reflektif Mahasiswa: {$mahasiswa->user->name} (NIM: {$mahasiswa->nim})
Proyek: {$project->project_name}

Berikut ini adalah jawaban mahasiswa untuk penilaian reflektif:

{$qaText}

Instruksi untuk Pembuatan Ringkasan:
1. Buat ringkasan deskriptif yang menjelaskan:
   - Pemahaman mahasiswa terhadap materi/proyek
   - Kemampuan mahasiswa untuk melakukan refleksi diri
   - Wawasan penting dari jawaban reflektif mahasiswa
   - Pola pikir dan pendekatan mahasiswa dalam menyelesaikan masalah

2. Ringkasan harus:
   - Objektif dan berdasarkan jawaban yang diberikan
   - Konstruktif dan berfokus pada pengembangan
   - Terstruktur dengan paragraf yang kohesif
   - Bersifat deskriptif, bukan dalam format poin per poin

3. Hindari:
   - Penilaian yang terlalu kritis
   - Pernyataan yang bersifat menghakimi
   - Kesimpulan yang tidak didukung oleh jawaban mahasiswa

Hasilkan ringkasan yang komprehensif, profesional, dan bermanfaat untuk penilaian akademik.";

            // Call Gemini API to generate summary
            $summary = $this->callGeminiWithErrorHandling($prompt);

            // Save or update the reflective_ai entry
            reflective_ai::updateOrCreate(
                [
                    'mahasiswa_id' => $mahasiswaId,
                    'project_id' => $projectId,
                ],
                [
                    'mahasiswa_id' => $mahasiswaId,
                    'project_id' => $projectId,
                    'summary' => Str::limit($summary, 65535, '...')
                ]
            );

            Log::info('Reflective summary generated successfully', [
                'mahasiswa_id' => $mahasiswaId,
                'project_id' => $projectId
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating reflective summary', [
                'mahasiswa_id' => $mahasiswaId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Continue execution even if summary generation fails
            // This ensures the main functionality of saving answers still works
        }
    }

    private function callGeminiWithErrorHandling($prompt)
    {
        $apiKey = config('services.gemini.api_key');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal menghasilkan ringkasan.";
    }

    public function saveReflectiveAnswer(Request $request)
    {
        DB::beginTransaction();

        try {
            // Log data yang diterima untuk debugging
            Log::info('Request data saveReflectiveAnswer:', [
                'answer' => $request->input('answer'),
                'temporaryAnswer' => $request->input('temporaryAnswer')
            ]);

            $validated = $request->validate([
                'answer' => 'required|array',
                'answer.*.question_id' => 'required|uuid',
                'answer.*.answer' => 'required|string',
                'answer.*.status' => 'required|string',
                'temporaryAnswer' => 'nullable|string'
            ]);

            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mahasiswa data not found'
                ], 404);
            }

            $savedAnswers = [];

            // Simpan jawaban utama
            foreach ($validated['answer'] as $answer) {
                $reflectiveAnswer = ReflectiveAnswer::updateOrCreate(
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'question_id' => $answer['question_id'],
                        'answer' => $answer['answer'],
                        'status' => $answer['status'],
                    ]
                );

                $savedAnswers[] = $reflectiveAnswer;
            }

            // Tangani temporaryAnswer jika string JSON
            if ($request->has('temporaryAnswer') && is_string($request->input('temporaryAnswer'))) {
                try {
                    $tempData = json_decode($request->input('temporaryAnswer'), true);

                    if (json_last_error() === JSON_ERROR_NONE && is_array($tempData)) {
                        foreach ($tempData as $questionId => $tempAnswer) {
                            // Lewati pertanyaan yang sudah disimpan di loop pertama
                            $alreadySaved = false;
                            foreach ($validated['answer'] as $mainAnswer) {
                                if ($mainAnswer['question_id'] === $questionId) {
                                    $alreadySaved = true;
                                    break;
                                }
                            }

                            if ($alreadySaved) {
                                continue;
                            }

                            if (isset($tempAnswer['answer'])) {
                                $status = $request->input('answer.0.status', 'submitted');

                                $reflectiveAnswer = ReflectiveAnswer::updateOrCreate(
                                    [
                                        'question_id' => $questionId,
                                        'mahasiswa_id' => $mahasiswa->id
                                    ],
                                    [
                                        'question_id' => $questionId,
                                        'mahasiswa_id' => $mahasiswa->id,
                                        'answer' => $tempAnswer['answer'],
                                        'status' => $status
                                    ]
                                );

                                $savedAnswers[] = $reflectiveAnswer;
                            }
                        }
                    }
                } catch (\Exception $jsonError) {
                    Log::warning('Error parsing temporaryAnswer JSON:', [
                        'error' => $jsonError->getMessage(),
                        'temporaryAnswer' => $request->input('temporaryAnswer')
                    ]);
                    // Lanjutkan eksekusi, kesalahan JSON tidak fatal
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All answers saved successfully.',
                'answers' => $savedAnswers
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();

            Log::error('Validation error in saveReflectiveAnswer:', [
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . implode(', ', array_map(function ($err) {
                    return implode(' ', $err);
                }, $e->errors()))
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in saveReflectiveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to save answers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAnswer($questionId)
    {
        try {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan');
            }

            $answer = ReflectiveAnswer::where([
                'question_id' => $questionId,
                'mahasiswa_id' => $mahasiswa->id
            ])->first();

            if (!$answer) {
                return response()->json(null);
            }

            return response()->json([
                'answer' => $answer->answer,
                'score' => $answer->score
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get answer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetailReflective(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');

        return Inertia::render('Mahasiswa/DetailReflectiveAssessment', [
            'batchYear' => $batchYear,
            'projectName' => $projectName
        ]);
    }

    public function getAnswerReflective(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');

        $user = Auth::user();

        // Get the mahasiswa record
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        // Get the group for this specific project and batch year
        $group = Group::whereHas('project', function ($query) use ($project_name, $batch_year) {
            $query->where('project_name', $project_name)
                ->where('batch_year', $batch_year);
        })
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if (!$group) {
            return response()->json([
                'error' => 'Group not found'
            ], 404);
        }

        // Get all reflective assessment questions for this project
        $questions = Reflective::where('project_id', $group->project_id)
            ->with('rubric')
            ->get();

        // Get the student's answers
        $answers = ReflectiveAnswer::where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('question_id', $questions->pluck('id'))
            ->get();

        // Create a mapping of question IDs to questions for easy lookup
        $questionMap = $questions->keyBy('id');

        // Group answers by criteria
        $groupedAnswers = $answers->map(function ($answer) use ($questionMap) {
            // Get the question for this answer
            $question = $questionMap->get($answer->question_id);

            if (!$question) {
                return null;
            }

            return [
                'question_id' => $answer->question_id,
                'question' => $question->question,
                'answer' => $answer->answer,
                'criteria' => $question->rubric->criteria_reflective ?? 'Uncategorized'
            ];
        })
            ->filter() // Remove null entries
            ->groupBy('criteria')
            ->map(function ($criteriaAnswers, $criteriaName) {
                return [
                    'kriteria_reflective' => $criteriaName,
                    'answers' => $criteriaAnswers->map(function ($item) {
                        return [
                            'question' => $item['question'],
                            'reason' => $item['answer']
                        ];
                    })
                ];
            })
            ->values();

        return response()->json([
            'answers' => $groupedAnswers
        ]);
    }
}
