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
use App\Models\reflective_writing;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\ReflectiveWritingAnswer;
use App\Models\reflective_writing_ai;

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

    public function getDataReflectiveWriting()
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
                ->join('reflective_writing', function ($join) {
                    $join->on('project.id', '=', 'reflective_writing.project_id')
                        ->where('reflective_writing.is_published', '=', 1); // Filter published assessments
                })
                ->where('groups.mahasiswa_id', $mahasiswa->id)
                ->select([
                    'groups.id as group_id',
                    'project.id as project_id',
                    'project.batch_year',
                    'project.project_name',
                    'project.status',
                    'groups.created_at',
                    'reflective_writing.reflective_writing_order as assessment_order'
                ])
                ->selectRaw('COUNT(reflective_writing.id) as total_questions')
                ->selectRaw('(SELECT COUNT(DISTINCT ra.reflective_writing_order) 
                       FROM reflective_writing ra 
                       WHERE ra.project_id = project.id 
                       AND ra.is_published = 1) as total_orders')
                ->groupBy(
                    'groups.id',
                    'project.id',
                    'project.batch_year',
                    'project.project_name',
                    'project.status',
                    'groups.created_at',
                    'reflective_writing.reflective_writing_order'
                )
                ->having('total_questions', '>', 0)
                ->orderBy('project.batch_year', 'desc')
                ->orderBy('project.project_name')
                ->orderBy('reflective_writing.reflective_writing_order')
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

    public function getReflectiveWriting(Request $request)
    {
        Log::info('Request received in assessment:', $request->all());

        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'assessment_order' => 'required|string',
        ]);

        Log::info('Validated data:', $validated);

        return Inertia::render('Mahasiswa/ReflectiveWritingMahasiswa', [
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

    public function getDataReflectiveWritingPoints(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');
        $assessmentOrder = $request->query('assessment_order');

        try {
            // Get project ID from project name
            // Using project_name column instead of name
            $project = Project::where('project_name', $projectName)
                ->where('batch_year', $batchYear)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            $questions = reflective_writing::where('batch_year', $batchYear)
                ->where('project_id', $project->id)
                ->where('reflective_writing_order', $assessmentOrder)
                ->where('is_published', true)
                ->get();

            // Format questions with criteria from rubric
            $formattedQuestions = $questions->map(function ($question) {
                $questionData = $question->toArray();

                return $questionData;
            });

            return response()->json($formattedQuestions);
        } catch (\Exception $e) {
            Log::error('Error getting reflective questions: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load questions: ' . $e->getMessage()], 500);
        }
    }
    public function getDataReflectiveWritingCount(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
            ]);

            // Get project ID from the project name and batch year
            $project = Project::where('project_name', $request->project_name)
                ->where('batch_year', $request->batch_year)
                ->first();

            if (!$project) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Project not found',
                    'count' => 0
                ], 404);
            }

            // Count total records instead of distinct orders
            $count = reflective_writing::where('project_id', $project->id)->count();

            return response()->json([
                'status' => 'success',
                'count' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'count' => 0
            ], 500);
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

    public function saveReflectiveWriting(Request $request)
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
                'answer.*.reflectiveWriting_id' => 'required|uuid',
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
                $reflectiveAnswer = ReflectiveWritingAnswer::updateOrCreate(
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'reflectiveWriting_id' => $answer['reflectiveWriting_id'],
                    ],
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'reflectiveWriting_id' => $answer['reflectiveWriting_id'],
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

                                $reflectiveAnswer = ReflectiveWritingAnswer::updateOrCreate(
                                    [
                                        'reflectiveWriting_id' => $questionId,
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

    public function getAnswerReflectiveWriting($assessmentOrder)
    {
        try {
            $user = Auth::user();
            $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

            if (!$mahasiswa) {
                throw new \Exception('Mahasiswa tidak ditemukan');
            }

            $answer = ReflectiveWritingAnswer::where([
                'reflectiveWriting_id' => $assessmentOrder,
                'mahasiswa_id' => $mahasiswa->id
            ])->first();

            if (!$answer) {
                return response()->json(null);
            }

            return response()->json([
                'answer' => $answer->answer,
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

    public function getTotalOrders(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');

        try {
            // First get the project ID
            $project = Project::where('project_name', $projectName)
                ->where('batch_year', $batchYear)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            // Count the distinct reflective_writing_order values
            $totalOrders = reflective_writing::where('batch_year', $batchYear)
                ->where('project_id', $project->id)
                ->where('is_published', true)
                ->distinct('reflective_writing_order')
                ->count('reflective_writing_order');

            return response()->json(['total' => $totalOrders]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

    public function getDetailReflectiveWriting(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');

        return Inertia::render('Mahasiswa/DetailReflectiveWriting', [
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


    /**
     * Save all reflective writing answers for a student
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function submitAllReflectiveWriting(Request $request)
    {
        DB::beginTransaction();

        try {
            $validated = $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
                'answers' => 'required|array',
                'answers.*.reflectiveWriting_id' => 'required|uuid',
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

            // Get the project based on name and batch year
            $project = Project::where('project_name', $validated['project_name'])
                ->where('batch_year', $validated['batch_year'])
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            $projectId = $project->id;

            foreach ($validated['answers'] as $answerData) {
                $reflectiveWritingAnswer = ReflectiveWritingAnswer::updateOrCreate(
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'reflectiveWriting_id' => $answerData['reflectiveWriting_id'],
                    ],
                    [
                        'mahasiswa_id' => $mahasiswa->id,
                        'reflectiveWriting_id' => $answerData['reflectiveWriting_id'],
                        'answer' => $answerData['answer'],
                        'status' => $answerData['status'],
                    ]
                );

                // Get the question information
                $reflectiveWriting = reflective_writing::find($answerData['reflectiveWriting_id']);
                if ($reflectiveWriting) {
                    // Store necessary data for analysis
                    $allAnswers[] = [
                        'reflectiveWriting_id' => $answerData['reflectiveWriting_id'],
                        'answer' => $answerData['answer']
                    ];
                }

                $savedAnswers[] = $reflectiveWritingAnswer;
            }

            // Generate and save AI summary if we have answers
            if ($projectId && !empty($allAnswers)) {
                $this->generateAndSaveWritingSummary($mahasiswa->id, $projectId, $allAnswers);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'All reflective writing answers saved successfully.',
                'data' => $savedAnswers
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error in submitAllReflectiveWriting:', [
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
     * Generate a summary of reflective writing answers using AI and save to database
     *
     * @param string $mahasiswaId
     * @param string $projectId
     * @param array $answers - Each answer contains ['answer', 'reflectiveWriting_id']
     * @return void
     */
    private function generateAndSaveWritingSummary($mahasiswaId, $projectId, $answers)
    {
        try {
            // Create a string to store all analyses
            $finalSummary = "";
            $reflectionNumber = 1; // Counter for reflective writings

            // Process each reflective writing answer
            foreach ($answers as $qa) {
                $reflectiveWritingId = $qa['reflectiveWriting_id'];
                $studentAnswer = $qa['answer'];

                // Get the reflective writing entry with its points
                $reflectiveWriting = reflective_writing::find($reflectiveWritingId);
                if (!$reflectiveWriting) {
                    Log::warning('Reflective writing not found', ['id' => $reflectiveWritingId]);
                    continue; // Skip if entry not found
                }

                // Extract the points to check (only non-empty ones)
                $pointsToCheck = [];
                if (!empty($reflectiveWriting->point_1)) $pointsToCheck[] = $reflectiveWriting->point_1;
                if (!empty($reflectiveWriting->point_2)) $pointsToCheck[] = $reflectiveWriting->point_2;
                if (!empty($reflectiveWriting->point_3)) $pointsToCheck[] = $reflectiveWriting->point_3;
                if (!empty($reflectiveWriting->point_4)) $pointsToCheck[] = $reflectiveWriting->point_4;
                if (!empty($reflectiveWriting->point_5)) $pointsToCheck[] = $reflectiveWriting->point_5;

                if (empty($pointsToCheck)) {
                    Log::warning('No points to check', ['reflective_id' => $reflectiveWritingId]);
                    continue; // Skip if no points to check
                }

                // Add reflective writing number to summary
                $finalSummary .= "Reflective Writing No. {$reflectionNumber}\n";

                // Process each point individually for more reliable results
                foreach ($pointsToCheck as $index => $point) {
                    $pointNumber = $index + 1;

                    // Build prompt for analyzing a single point with emphasis on semantic understanding
                    $prompt = "Analisis Jawaban Reflective Writing Mahasiswa.

Jawaban Mahasiswa: 
\"{$studentAnswer}\"

Poin yang harus dinilai:
\"{$point}\"

Tugas:
Analisis apakah jawaban mahasiswa mencakup poin tersebut secara kontekstual. 
Periksa apakah mahasiswa telah menyampaikan konsep yang sama dengan poin tersebut, meskipun menggunakan kata-kata yang berbeda.
Fokus pada makna dan konteks, bukan hanya keberadaan kata kunci.

Berikan output HANYA 'tercakup' atau 'tidak tercakup' saja tanpa penjelasan tambahan.";

                    // Call Gemini API to analyze the point coverage
                    $analysisResult = $this->callGeminiAPI($prompt);

                    // Clean and process the result
                    $cleanedResult = trim($analysisResult);

                    // Determine if point is covered based on the response
                    $isCovered = (stripos($cleanedResult, 'tercakup') !== false &&
                        stripos($cleanedResult, 'tidak tercakup') === false);

                    $coverage = $isCovered ? "tercakup" : "tidak tercakup";

                    // Add to summary
                    $finalSummary .= "Point {$pointNumber}: {$coverage}\n";

                    // Log for debugging
                    Log::debug('Point analysis', [
                        'reflective_number' => $reflectionNumber,
                        'point_number' => $pointNumber,
                        'point_text' => $point,
                        'raw_response' => $cleanedResult,
                        'coverage' => $coverage
                    ]);
                }

                $finalSummary .= "\n";
                $reflectionNumber++; // Increment counter for next reflective writing
            }

            // Save or update the reflective_writing_ai entry
            reflective_writing_ai::updateOrCreate(
                [
                    'mahasiswa_id' => $mahasiswaId,
                    'project_id' => $projectId,
                ],
                [
                    'mahasiswa_id' => $mahasiswaId,
                    'project_id' => $projectId,
                    'summary' => $finalSummary
                ]
            );

            Log::info('Reflective writing analysis generated successfully', [
                'mahasiswa_id' => $mahasiswaId,
                'project_id' => $projectId
            ]);
        } catch (\Exception $e) {
            Log::error('Error generating reflective writing analysis', [
                'mahasiswa_id' => $mahasiswaId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Continue execution even if analysis generation fails
            // This ensures the main functionality of saving answers still works
        }
    }

    /**
     * Call the Google Gemini API to analyze text
     *
     * @param string $prompt
     * @return string
     */
    private function callGeminiAPI($prompt)
    {
        try {
            // Get the API key from environment variables
            $apiKey = env('GEMINI_API_KEY');

            if (empty($apiKey)) {
                Log::error('Gemini API key is not set in the environment variables');
                return 'Error: API key not configured';
            }

            $client = new \GuzzleHttp\Client([
                'timeout' => 30, // Increase timeout to 30 seconds
            ]);

            $response = $client->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey, [
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => $prompt
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1, // Lower temperature for more predictable responses
                        'topK' => 40,
                        'topP' => 0.95,
                        'maxOutputTokens' => 512,
                    ]
                ],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            // Extract the generated text from the response
            if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
                return $result['candidates'][0]['content']['parts'][0]['text'];
            } else {
                Log::error('Unexpected Gemini API response structure', [
                    'response' => $result
                ]);
                return 'Error: Unexpected API response structure';
            }
        } catch (\Exception $e) {
            Log::error('Error calling Gemini API', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 'Error: API request failed: ' . $e->getMessage();
        }
    }
}
