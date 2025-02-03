<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Models\Assessment;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function report()
    {
        return Inertia::render('Dosen/Report');
    }

    public function getDropdownOptions(Request $request): JsonResponse
    {
        // Ambil data batch_year yang unik
        $batchYearOptions = Project::select('batch_year')->distinct()->pluck('batch_year');

        // Jika ada batch_year yang dipilih, ambil project_name berdasarkan batch_year tersebut
        $projectNameOptions = Project::select('project_name', 'batch_year')
            ->when($request->batch_year, function ($query, $batchYear) {
                return $query->where('batch_year', $batchYear);
            })
            ->distinct()
            ->get();

        // Gabungkan data batch_year dan project_name
        $combinedOptions = [];
        foreach ($batchYearOptions as $year) {
            foreach ($projectNameOptions->where('batch_year', $year) as $project) {
                $combinedOptions[] = [
                    'value' => "{$year} - {$project->project_name}",
                    'label' => "{$year} - {$project->project_name}",
                    'batchYear' => $year,
                    'projectName' => $project->project_name,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'options' => $combinedOptions,
        ]);
    }


    public function getKelompokReport(Request $request): JsonResponse
    {
        // Validasi parameter yang diterima
        $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
        ]);

        // Ambil data kelompok berdasarkan batch_year dan project_name
        $kelompokData = Group::whereHas('project', function ($query) use ($request) {
            $query->where('batch_year', $request->batch_year)
                ->where('project_name', $request->project_name);
        })
            ->with(['mahasiswa.user']) // Eager load relasi mahasiswa dan user
            ->get(['id', 'group', 'mahasiswa_id']);

        // Jika tidak ada data, beri respon kosong
        if ($kelompokData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kelompok yang ditemukan.',
            ]);
        }

        // Group data berdasarkan nama kelompok (group)
        $groupedKelompok = $kelompokData->groupBy('group');

        // Format data kelompok dan merge jika ada kelompok yang sama
        $kelompokList = $groupedKelompok->map(function ($group, $kelompokName) {
            // Ambil ID pertama dari kelompok
            $kelompok = [
                'id' => $group->pluck('id')->first(),
                'nama_kelompok' => $kelompokName,
                'anggota' => [],
            ];

            // Ambil semua anggota dengan mahasiswa_id terkait
            foreach ($group as $item) {
                // Pastikan mahasiswa dan user ada
                if ($item->mahasiswa && $item->mahasiswa->user) {
                    $kelompok['anggota'][] = [
                        'mahasiswa_id' => $item->mahasiswa_id,
                        'name' => $item->mahasiswa->user->name, // Ambil nama dari relasi user
                        'nim' => $item->mahasiswa->nim, // Optional: tambahkan NIM jika diperlukan
                    ];
                }
            }

            return $kelompok;
        });

        return response()->json([
            'success' => true,
            'kelompok' => $kelompokList,
        ]);
    }


    public function getScoreKelompok(Request $request)
    {
        // Ambil parameter dari query string
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');
        $kelompok = $request->query('kelompok');

        // Log data untuk melihat apakah parameter diterima
        Log::info('Data diterima di controller:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);

        // Validasi input
        if (!$tahunAjaran || !$namaProyek || !$kelompok) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap'
            ], 400);
        }

        // Mencari project_id berdasarkan nama proyek
        $project = Project::where('project_name', $namaProyek)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan'
            ], 404);
        }

        // Ambil data kelompok dengan relasi
        $groupMembers = Group::with(['mahasiswa', 'project'])
            ->where('batch_year', $tahunAjaran)
            ->where('project_id', $project->id)
            ->where('group', $kelompok)
            ->get();

        if ($groupMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelompok tidak ditemukan'
            ], 404);
        }

        return Inertia::render('Dosen/ReportScore', [
            'batch_year' => $tahunAjaran,
            'project_name' => $namaProyek,
            'kelompok' => $kelompok,
            'initialData' => [
                'groupMembers' => $groupMembers,
                'project' => $project
            ]
        ]);
    }

    public function getKelompokAnswers(Request $request)
    {
        $tahunAjaran = $request->input('batch_year');
        $namaProyek = $request->input('project_name');
        $kelompok = $request->input('kelompok');

        try {
            // Get project ID first
            $project = Project::where('batch_year', $tahunAjaran)
                ->where('project_name', $namaProyek)
                ->first();

            if (!$project) {
                return response()->json([], 200);
            }

            // Retrieve mahasiswa_ids for the specific group
            $mahasiswaIds = Group::where('batch_year', $tahunAjaran)
                ->where('project_id', $project->id)
                ->where('group', $kelompok)
                ->pluck('mahasiswa_id');

            if ($mahasiswaIds->isEmpty()) {
                return response()->json([], 200);
            }

            // Get mahasiswa names and their associated user IDs
            $mahasiswaDetails = Mahasiswa::whereIn('id', $mahasiswaIds)
                ->with('user')
                ->get()
                ->mapWithKeys(function ($mahasiswa) {
                    return [$mahasiswa->id => [
                        'name' => $mahasiswa->user->name,
                        'user_id' => $mahasiswa->user_id
                    ]];
                });

            // Process each mahasiswa's assessments
            $mahasiswaResults = $mahasiswaIds->mapWithKeys(function ($mahasiswaId) use ($tahunAjaran, $project, $mahasiswaDetails) {
                // Self Assessments
                $selfAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'selfAssessment')
                    ->with('typeCriteria')
                    ->get();

                $selfAspekKriteriaAnalysis = $this->analyzeAssessments($selfAssessments, $mahasiswaId, 'self', $project->id);

                // Peer Assessments
                $peerAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'peerAssessment')
                    ->with('typeCriteria')
                    ->get();

                $peerAspekKriteriaAnalysis = $this->analyzeAssessments($peerAssessments, $mahasiswaId, 'peer', $project->id);

                // Get all peer evaluations for this mahasiswa
                $peerEvaluations = AnswersPeer::select(
                    'answers_peer.*',
                    'assessment.id as assessment_id',
                    'assessment.question',
                    'type_criteria.aspect',
                    'type_criteria.criteria'
                )
                    ->join('assessment', 'answers_peer.question_id', '=', 'assessment.id')
                    ->join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
                    ->where('assessment.batch_year', $tahunAjaran)
                    ->where('assessment.project_id', $project->id)
                    ->where('answers_peer.peer_id', $mahasiswaId)
                    ->get();

                // Group peer evaluations by aspect and criteria
                $groupedPeerEvaluations = $peerEvaluations->groupBy(function ($item) {
                    return $item->aspect . '_' . $item->criteria;
                })->map(function ($groupAnswers) use ($mahasiswaDetails) {
                    $aspek = $groupAnswers->first()->aspect;
                    $kriteria = $groupAnswers->first()->criteria;

                    // Group answers by evaluator (mahasiswa_id)
                    $evaluatorGroups = $groupAnswers->groupBy('mahasiswa_id');

                    $evaluatedBy = $evaluatorGroups->map(function ($answers, $evaluatorId) use ($mahasiswaDetails) {
                        return [
                            'name' => $mahasiswaDetails[$evaluatorId]['name'] ?? 'Unknown',
                            'total_score' => $answers->avg('score'),
                            'answers' => $answers->map(function ($answer) {
                                return [
                                    'question_id' => $answer->assessment_id,
                                    'pertanyaan' => $answer->question, // Include the question text
                                    'score' => $answer->score,
                                    'answer' => $answer->answer
                                ];
                            })->values()
                        ];
                    });

                    return [
                        'aspek' => $aspek,
                        'kriteria' => $kriteria,
                        'total_score' => $groupAnswers->avg('score'),
                        'evaluated_by' => $evaluatedBy
                    ];
                })->values();

                return [
                    $mahasiswaId => [
                        'mahasiswa_id' => $mahasiswaId,
                        'name' => $mahasiswaDetails[$mahasiswaId]['name'] ?? 'Unknown',
                        'self_assessment' => $selfAspekKriteriaAnalysis->values(),
                        'peer_assessment' => $peerAspekKriteriaAnalysis->values(),
                        'evaluated_by_peers' => $groupedPeerEvaluations
                    ]
                ];
            });

            return response()->json($mahasiswaResults);
        } catch (\Exception $e) {
            Log::error('Error in getKelompokAnswers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $projectId)
    {
        if ($assessments->isEmpty()) {
            return collect([]);
        }

        return $assessments->groupBy(function ($assessment) {
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType) {
            $questionIds = $groupAssessments->pluck('id');

            $answers = $assessmentType === 'self'
                ? Answers::whereIn('question_id', $questionIds)
                ->where('mahasiswa_id', $mahasiswaId)
                ->get()
                : AnswersPeer::whereIn('question_id', $questionIds)
                ->where('peer_id', $mahasiswaId)
                ->get();

            return [
                'aspek' => $groupAssessments->first()->typeCriteria->aspect,
                'kriteria' => $groupAssessments->first()->typeCriteria->criteria,
                'total_score' => $answers->avg('score'),
                'total_answers' => $answers->count(),
                'questions' => $groupAssessments->map(function ($assessment) use ($answers) {
                    $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                    return [
                        'question_id' => $assessment->id,
                        'pertanyaan' => $assessment->question,
                        'score' => $relatedAnswer ? $relatedAnswer->score : null,
                        'answer' => $relatedAnswer ? $relatedAnswer->answer : null
                    ];
                })
            ];
        });
    }
}
