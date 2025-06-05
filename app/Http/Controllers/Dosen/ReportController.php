<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Project;
use App\Models\Answers;
use App\Models\TypeCriteria;
use App\Models\AnswersPeer;
use App\Models\User;
use App\Models\Report;
use App\Models\Mahasiswa;
use App\Models\Assessment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
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
            ->with(['mahasiswa.user', 'mahasiswa.classRoom']) // Eager load relasi mahasiswa, user, dan class
            ->get(['id', 'group', 'mahasiswa_id']);

        // Jika tidak ada data, beri respon kosong
        if ($kelompokData->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kelompok yang ditemukan.',
            ]);
        }

        // Group data berdasarkan nama kelompok dan class_id
        $groupedKelompok = collect();

        // Pertama, kelompokkan berdasarkan nama kelompok
        $tempGroups = $kelompokData->groupBy('group');

        foreach ($tempGroups as $groupName => $members) {
            $byClass = $members->groupBy(function ($item) {
                return $item->mahasiswa->classRoom->name ?? 'unknown';
            });

            foreach ($byClass as $className => $classMembers) {
                $firstMember = $classMembers->first();
                $kelompok = [
                    'id' => $firstMember->id,
                    'nama_kelompok' => $groupName,
                    'class_name' => $className,
                    'class_id' => $firstMember->mahasiswa->class_id ?? 'unknown',
                    'anggota' => $classMembers->map(function ($item) {
                        return [
                            'mahasiswa_id' => $item->mahasiswa_id,
                            'name' => $item->mahasiswa->user->name ?? '',
                            'nim' => $item->mahasiswa->nim ?? '',
                            'class_id' => $item->mahasiswa->class_id,
                        ];
                    })->filter()->values()->all()
                ];

                if (!empty($kelompok['anggota'])) {
                    $groupedKelompok->push($kelompok);
                }
            }
        }

        // Buat fungsi untuk ekstrak nomor dan huruf dari nama kelompok (misalnya "1A" -> [1, "A"])
        $extractGroupInfo = function ($groupName) {
            // Menggunakan regex untuk memisahkan angka dan huruf
            preg_match('/(\d+)([A-Za-z]*)/', $groupName, $matches);

            if (count($matches) >= 3) {
                return [
                    'number' => (int)$matches[1],
                    'letter' => strtoupper($matches[2] ?? '')
                ];
            }

            // Jika format tidak sesuai, gunakan default values
            return [
                'number' => 999,  // Nomor tinggi untuk kelompok yang tidak sesuai format
                'letter' => $groupName
            ];
        };

        // Urutkan kelompok berdasarkan nomor dan huruf
        $sortedKelompok = $groupedKelompok->sort(function ($a, $b) use ($extractGroupInfo) {
            $infoA = $extractGroupInfo($a['nama_kelompok']);
            $infoB = $extractGroupInfo($b['nama_kelompok']);

            // Bandingkan nomor terlebih dahulu
            if ($infoA['number'] !== $infoB['number']) {
                return $infoA['number'] <=> $infoB['number'];
            }

            // Jika nomor sama, bandingkan huruf
            return $infoA['letter'] <=> $infoB['letter'];
        });

        return response()->json([
            'success' => true,
            'kelompok' => $sortedKelompok->values(),
        ]);
    }


    public function getScoreKelompok(Request $request)
    {
        // Validate parameters
        $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'kelompok' => 'required|string',
            'class_id' => 'required' // Ensure class_id is validated
        ]);

        // Find the project by name
        $project = Project::where('project_name', $request->project_name)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan'
            ], 404);
        }

        // Fetch group members filtered by class_id
        $groupMembers = Group::with(['mahasiswa.classRoom', 'mahasiswa.user'])
            ->whereHas('mahasiswa', function ($query) use ($request) {
                $query->where('class_id', $request->class_id);
            })
            ->where('batch_year', $request->batch_year)
            ->where('project_id', $project->id)
            ->where('group', $request->kelompok)
            ->get();

        if ($groupMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelompok tidak ditemukan'
            ], 404);
        }

        // Format the data for the response
        $formattedGroup = [
            'class_id' => $request->class_id,
            'class_name' => $groupMembers->first()->mahasiswa->classRoom->class_name ?? 'Unknown Class',
            'members' => $groupMembers->map(function ($member) {
                return [
                    'id' => $member->id,
                    'mahasiswa_id' => $member->mahasiswa_id,
                    'name' => $member->mahasiswa->user->name ?? '',
                    'nim' => $member->mahasiswa->nim ?? '',
                    'class_id' => $member->mahasiswa->class_id,
                    'class_name' => $member->mahasiswa->classRoom->class_name ?? 'Unknown Class',
                    'group' => $member->group
                ];
            })->values()->all() // Ensure values are reset
        ];

        return Inertia::render('Dosen/ReportScore', [
            'batch_year' => $request->batch_year,
            'project_name' => $request->project_name,
            'kelompok' => $request->kelompok,
            'initialData' => [
                'groupMembers' => [$formattedGroup], // Wrap in array for single group
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
            // Get project
            $project = Project::where('batch_year', $tahunAjaran)
                ->where('project_name', $namaProyek)
                ->first();

            if (!$project) {
                return response()->json(['message' => 'Project not found'], 404);
            }

            // Get group
            $group = Group::where('batch_year', $tahunAjaran)
                ->where('project_id', $project->id)
                ->where('group', $kelompok)
                ->first();

            if (!$group) {
                return response()->json(['message' => 'Group not found'], 404);
            }

            // Retrieve mahasiswa IDs for the group
            $mahasiswaIds = Group::where('batch_year', $tahunAjaran)
                ->where('project_id', $project->id)
                ->where('group', $kelompok)
                ->pluck('mahasiswa_id');

            if ($mahasiswaIds->isEmpty()) {
                return response()->json(['message' => 'No students found'], 404);
            }

            // Get mahasiswa names and user IDs
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
            $mahasiswaResults = $mahasiswaIds->mapWithKeys(function ($mahasiswaId) use ($tahunAjaran, $project, $group, $mahasiswaDetails) {
                // Self Assessments
                $selfAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'selfAssessment')
                    ->with('typeCriteria')
                    ->get();

                $selfAspekKriteriaAnalysis = $this->analyzeAssessments($selfAssessments, $mahasiswaId, 'self', $project->id, $group->id);

                // Peer Assessments
                $peerAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'peerAssessment')
                    ->with('typeCriteria')
                    ->get();

                $peerAspekKriteriaAnalysis = $this->analyzeAssessments($peerAssessments, $mahasiswaId, 'peer', $project->id, $group->id);

                // Get all peer evaluations for this mahasiswa
                $peerEvaluations = AnswersPeer::select(
                    'answers_peer.*',
                    'assessment.id as assessment_id',
                    'assessment.question',
                    'type_criteria.aspect',
                    'type_criteria.criteria',
                    'type_criteria.id as typeCriteria_id',
                    'answers_peer.score_SLA',
                )
                    ->join('assessment', 'answers_peer.question_id', '=', 'assessment.id')
                    ->join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
                    ->where('assessment.batch_year', $tahunAjaran)
                    ->where('assessment.project_id', $project->id)
                    ->where('answers_peer.peer_id', $mahasiswaId)
                    ->get();

                // Initialize collection for all selisih values
                $allSelisih = collect();

                // First, calculate all selisih values
                $peerEvaluations->groupBy(function ($item) {
                    return $item->aspect . '_' . $item->criteria;
                })->each(function ($groupAnswers) use ($selfAspekKriteriaAnalysis, &$allSelisih) {
                    $aspek = $groupAnswers->first()->aspect;
                    $kriteria = $groupAnswers->first()->criteria;

                    // Find matching self assessment based on both aspect AND criteria
                    $matchingSelfAssessment = $selfAspekKriteriaAnalysis->first(function ($item) use ($aspek, $kriteria) {
                        return $item['aspek'] === $aspek && $item['kriteria'] === $kriteria;
                    });

                    $skorSelf = $matchingSelfAssessment ? $matchingSelfAssessment['total_score'] : 0;
                    $skorPeer = $groupAnswers->avg('score');
                    $selisih = abs($skorSelf - $skorPeer);

                    $allSelisih->push($selisih);
                });

                // Calculate ranges once
                $minSelisih = $allSelisih->min();
                $maxSelisih = $allSelisih->max();
                $range = $maxSelisih > $minSelisih ? ($maxSelisih - $minSelisih) / 4 : 0;

                $ranges = [
                    ['min' => $minSelisih, 'max' => $minSelisih + $range, 'score' => 100],
                    ['min' => $minSelisih + $range, 'max' => $minSelisih + (2 * $range), 'score' => 90],
                    ['min' => $minSelisih + (2 * $range), 'max' => $minSelisih + (3 * $range), 'score' => 80],
                    ['min' => $minSelisih + (3 * $range), 'max' => $minSelisih + (4 * $range), 'score' => 70],
                    ['min' => $maxSelisih, 'max' => $maxSelisih, 'score' => 60]
                ];

                // Process evaluations with calculated ranges
                $groupedPeerEvaluations = $peerEvaluations->groupBy(function ($item) {
                    return $item->aspect . '_' . $item->criteria;
                })->map(function ($groupAnswers) use ($mahasiswaDetails, $project, $group, $mahasiswaId, $selfAspekKriteriaAnalysis, $ranges) {
                    $aspek = $groupAnswers->first()->aspect;
                    $kriteria = $groupAnswers->first()->criteria;
                    $typeCriteriaId = $groupAnswers->first()->typeCriteria_id;

                    // Find matching self assessment based on both aspect AND criteria
                    $matchingSelfAssessment = $selfAspekKriteriaAnalysis->first(function ($item) use ($aspek, $kriteria) {
                        return $item['aspek'] === $aspek && $item['kriteria'] === $kriteria;
                    });

                    $skorSelf = $matchingSelfAssessment ? $matchingSelfAssessment['total_score'] : 0;
                    $skorPeer = $groupAnswers->avg('score');
                    $selisih = abs($skorSelf - $skorPeer);

                    $evaluatorGroups = $groupAnswers->groupBy('mahasiswa_id');
                    $evaluatedBy = $evaluatorGroups->map(function ($answers, $evaluatorId) use ($mahasiswaDetails) {
                        return [
                            'name' => $mahasiswaDetails[$evaluatorId]['name'] ?? 'Unknown',
                            'total_score' => $answers->avg('score'),
                            'answers' => $answers->map(function ($answer) {
                                return [
                                    'question_id' => $answer->assessment_id,
                                    'pertanyaan' => $answer->question,
                                    'score' => $answer->score,
                                    'score_SLA' => $answer->score_SLA,
                                    'answer' => $answer->answer
                                ];
                            })->values()
                        ];
                    });

                    return [
                        'aspek' => $aspek,
                        'kriteria' => $kriteria,
                        'total_score' => $skorPeer,
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

    private function analyzeAssessments($assessments, $mahasiswaId, $assessmentType, $projectId, $groupId)
    {
        if ($assessments->isEmpty()) {
            return collect([]);
        }
        Log::info("Analyzing assessments for mahasiswa: {$mahasiswaId}, type: {$assessmentType}, project: {$projectId}, group: {$groupId}");

        return $assessments->groupBy(function ($assessment) {
            return $assessment->typeCriteria->aspect . '_' . $assessment->typeCriteria->criteria;
        })->map(function ($groupAssessments) use ($mahasiswaId, $assessmentType, $projectId, $groupId) {
            $questionIds = $groupAssessments->pluck('id');
            $typeCriteriaId = $groupAssessments->first()->typeCriteria->id;
            Log::info("Processing typeCriteria ID: {$typeCriteriaId}");
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
                'total_score_SLA' => $answers->avg('score_SLA'),
                'total_answers' => $answers->count(),
                'questions' => $groupAssessments->map(function ($assessment) use ($answers, $mahasiswaId, $assessmentType, $projectId, $groupId, $typeCriteriaId) {
                    $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                    // Ambil final_score dari tabel Report berdasarkan question_id dan typeCriteria_id
                    Log::info("Query Report Parameters", [
                        'project_id' => $projectId,
                        'group_id' => $groupId,
                        'mahasiswa_id' => $mahasiswaId,
                        'typeCriteria_id' => $typeCriteriaId,
                        'question_id' => $assessment->id,
                        'assessment_type' => $assessmentType
                    ]);
                    $report = Report::where('project_id', $projectId)
                        ->where('group_id', $groupId)
                        ->where('mahasiswa_id', $mahasiswaId)
                        ->where('typeCriteria_id', $typeCriteriaId)
                        ->where('question_id', $assessment->id);

                    if ($assessmentType === 'self') {
                        $report = $report->whereNull('peer_id')->first();
                    } else {
                        // Untuk peer assessment, mungkin perlu disesuaikan berdasarkan struktur data
                        $report = $report->whereNotNull('peer_id')->first();
                    }
                    // Logging untuk debug
                    Log::info("Report Query Result", [
                        'mahasiswa_id' => $mahasiswaId,
                        'question_id' => $assessment->id,
                        'typeCriteria_id' => $typeCriteriaId,
                        'assessment_type' => $assessmentType,
                        'final_score_self' => $report ? $report->final_score_self : 'NULL',
                        'final_score_peer' => $report ? $report->final_score_peer : 'NULL',
                        'report_exists' => $report !== null
                    ]);
                    return [
                        'question_id' => $assessment->id,
                        'pertanyaan' => $assessment->question,
                        'score' => $relatedAnswer ? $relatedAnswer->score : null,
                        'score_SLA' => $relatedAnswer ? $relatedAnswer->score_SLA : null,
                        'nilai_akhir_self' => $assessmentType === 'self' && $report ? $report->final_score_self : null,
                        'nilai_akhir_peer' => $assessmentType === 'peer' && $report ? $report->final_score_peer : null,
                        'answer' => $relatedAnswer ? $relatedAnswer->answer : null
                    ];
                })
            ];
        });
    }

    public function getStudentPeerData(Request $request)
    {
        $tahunAjaran = $request->input('batch_year');
        $namaProyek = $request->input('project_name');

        try {
            // Get project
            $project = Project::where('batch_year', $tahunAjaran)
                ->where('project_name', $namaProyek)
                ->first();

            if (!$project) {
                return response()->json(['message' => 'Project not found'], 404);
            }

            // Get all mahasiswa IDs for this project
            $mahasiswaIds = Group::where('batch_year', $tahunAjaran)
                ->where('project_id', $project->id)
                ->pluck('mahasiswa_id');

            if ($mahasiswaIds->isEmpty()) {
                return response()->json(['message' => 'No students found'], 404);
            }

            // Collect all selisih values for range calculation
            $allSelisih = collect();

            // Process each mahasiswa's assessments
            $studentsData = $mahasiswaIds->map(function ($mahasiswaId) use ($tahunAjaran, $project, &$allSelisih) {
                // Get student details
                $student = Mahasiswa::with(['user', 'group' => function ($query) use ($project) {
                    $query->where('project_id', $project->id);
                }])->find($mahasiswaId);

                if (!$student) {
                    return null;
                }

                $group = $student->group->first();

                // Self Assessments
                $selfAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'selfAssessment')
                    ->with('typeCriteria')
                    ->get();

                $selfAspekKriteriaAnalysis = $this->analyzeAssessmentsReport($selfAssessments, $mahasiswaId, 'self', $project->id);

                // Peer Assessments
                $peerAssessments = Assessment::where('batch_year', $tahunAjaran)
                    ->where('project_id', $project->id)
                    ->where('type', 'peerAssessment')
                    ->with('typeCriteria')
                    ->get();

                $peerAspekKriteriaAnalysis = $this->analyzeAssessmentsReport($peerAssessments, $mahasiswaId, 'peer', $project->id);

                // Calculate average scores and differences
                $result = $this->calculateAverages($selfAspekKriteriaAnalysis, $peerAspekKriteriaAnalysis);
                $allSelisih->push($result['selisih']);

                // Save to Report table if group exists
                if ($group) {
                    // Get all assessments to map typeCriteria IDs
                    $allAssessments = $selfAssessments->concat($peerAssessments);
                    $typeCriteriaMap = $allAssessments->pluck('typeCriteria')->unique('id')->keyBy(function ($criteria) {
                        return $criteria->aspect . '_' . $criteria->criteria;
                    });

                    foreach ($result['aspect_details'] as $detail) {
                        $typeCriteria = $typeCriteriaMap->get($detail['aspek'] . '_' . $detail['kriteria']);

                        if ($typeCriteria) {
                            Report::updateOrCreate(
                                [
                                    'project_id' => $project->id,
                                    'group_id' => $group->id,
                                    'mahasiswa_id' => $mahasiswaId,
                                    'typeCriteria_id' => $typeCriteria->id
                                ],
                                [
                                    'skor_self' => $detail['self_score'],
                                    'skor_peer' => $detail['peer_score'],
                                    'selisih' => $detail['selisih'],
                                    'nilai_total' => 60, // Default value, will be updated later
                                ]
                            );
                        }
                    }
                }

                return [
                    'id' => $mahasiswaId,
                    'name' => $student->user->name,
                    'nim' => $student->nim,
                    'kelompok' => $group ? $group->group : 'Unknown',
                    'skor_self' => $result['self_score'],
                    'skor_peer' => $result['peer_score'],
                    'selisih' => $result['selisih'],
                    'aspect_details' => $result['aspect_details']
                ];
            })->filter()->values();

            // Calculate ranges and final scores
            $minSelisih = $allSelisih->min();
            $maxSelisih = $allSelisih->max();
            $range = $maxSelisih > $minSelisih ? ($maxSelisih - $minSelisih) / 4 : 0;

            // Define the ranges
            $ranges = [
                ['min' => $minSelisih, 'max' => $minSelisih + $range, 'score' => 100],
                ['min' => $minSelisih + $range, 'max' => $minSelisih + (2 * $range), 'score' => 90],
                ['min' => $minSelisih + (2 * $range), 'max' => $minSelisih + (3 * $range), 'score' => 80],
                ['min' => $minSelisih + (3 * $range), 'max' => $minSelisih + (4 * $range), 'score' => 70],
                ['min' => $maxSelisih, 'max' => $maxSelisih, 'score' => 60]
            ];

            // Apply final scores to each student and update Report table
            $studentsData = $studentsData->map(function ($student) use ($ranges, $project) {
                $nilaiTotal = 60; // Default value
                foreach ($ranges as $range) {
                    if ($student['selisih'] >= $range['min'] && $student['selisih'] <= $range['max']) {
                        $nilaiTotal = $range['score'];
                        break;
                    }
                }

                // Update nilai_total in Report table for this student
                if (isset($student['kelompok']) && $student['kelompok'] !== 'Unknown') {
                    $group = Group::where('project_id', $project->id)
                        ->where('mahasiswa_id', $student['id'])
                        ->first();

                    if ($group) {
                        Report::where('project_id', $project->id)
                            ->where('group_id', $group->id)
                            ->where('mahasiswa_id', $student['id'])
                            ->update(['nilai_total' => $nilaiTotal]);
                    }
                }

                return array_merge($student, [
                    'nilai_total' => $nilaiTotal
                ]);
            });

            // Sort students by nilai_total (descending) and then by selisih (ascending)
            $sortedStudentsData = $studentsData->sortBy([
                ['nilai_total', 'desc'],
                ['selisih', 'asc']
            ])->values();

            return response()->json([
                'success' => true,
                'students' => $sortedStudentsData,
                'ranges' => $ranges
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getStudentPeerData: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function calculateAverages($selfAnalysis, $peerAnalysis)
    {
        $totalSelfScore = 0;
        $totalPeerScore = 0;
        $totalSelisih = 0;
        $aspectCount = 0;
        $aspectDetails = [];

        foreach ($selfAnalysis as $key => $selfData) {
            $peerData = $peerAnalysis->get($key);
            if ($peerData) {
                $selfScore = $selfData['total_score'] ?? 0;
                $peerScore = $peerData['total_score'] ?? 0;

                $totalSelfScore += $selfScore;
                $totalPeerScore += $peerScore;
                $aspectSelisih = abs($selfScore - $peerScore);
                $totalSelisih += $aspectSelisih;
                $aspectCount++;

                $aspectDetails[] = [
                    'aspek' => $selfData['aspek'],
                    'kriteria' => $selfData['kriteria'],
                    'self_score' => $selfScore,
                    'peer_score' => $peerScore,
                    'selisih' => $aspectSelisih
                ];
            }
        }

        $avgSelfScore = $aspectCount > 0 ? $totalSelfScore / $aspectCount : 0;
        $avgPeerScore = $aspectCount > 0 ? $totalPeerScore / $aspectCount : 0;

        return [
            'self_score' => round($avgSelfScore, 2),
            'peer_score' => round($avgPeerScore, 2),
            'selisih' => round($totalSelisih, 2), // Now this is the sum of all differences
            'aspect_details' => $aspectDetails
        ];
    }

    private function analyzeAssessmentsReport($assessments, $mahasiswaId, $assessmentType, $projectId)
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
                'total_score_SLA' => $answers->avg('score_SLA'),
                'total_answers' => $answers->count(),
                'questions' => $groupAssessments->map(function ($assessment) use ($answers) {
                    $relatedAnswer = $answers->where('question_id', $assessment->id)->first();
                    return [
                        'question_id' => $assessment->id,
                        'pertanyaan' => $assessment->question,
                        'score' => $relatedAnswer ? $relatedAnswer->score : null,
                        'score_SLA' => $relatedAnswer ? $relatedAnswer->score_SLA : null,
                        'answer' => $relatedAnswer ? $relatedAnswer->answer : null
                    ];
                })
            ];
        });
    }

    public function getQuestionsByProjectPeerReport(Request $request)
    {
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');


        $assessments = Assessment::join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
            ->join('project', 'assessment.project_id', '=', 'project.id')
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'assessment.skill_type',
                'type_criteria.aspect',
                'type_criteria.criteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('assessment.batch_year', $tahunAjaran);
            })
            ->when($namaProyek, function ($query, $namaProyek) {
                $query->where('project.project_name', $namaProyek);
            })
            ->where('assessment.type', 'peerAssessment')

            ->get();

        return response()->json($assessments);
    }

    public function saveFinalScoresSelf(Request $request)
    {
        // Validasi data
        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*.mahasiswa_id' => 'required',
            'answers.*.typeCriteria_id' => 'required',
            'answers.*.question_id' => 'required',
            'answers.*.final_score_self' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();

        try {
            // Mapping untuk menyimpan project_id berdasarkan question_id
            $projectIdMap = [];
            $typeCriteriaMap = [];
            $assessmentTypeMap = []; // Untuk menyimpan tipe assessment

            foreach ($validated['answers'] as $answer) {
                $mahasiswaId = $answer['mahasiswa_id'];
                $typeCriteriaId = $answer['typeCriteria_id'];
                $questionId = $answer['question_id'];
                $finalScoreSelf = $answer['final_score_self'];

                // Cek dan mapping typeCriteria_id jika itu adalah string nama kriteria (bukan UUID)
                if (!isset($typeCriteriaMap[$typeCriteriaId]) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $typeCriteriaId)) {
                    // FIX: Gunakan exact match terlebih dahulu untuk mencari kriteria
                    $typeCriteria = TypeCriteria::where('criteria', $typeCriteriaId)
                        ->orWhere('aspect', $typeCriteriaId)
                        ->first();

                    // Jika tidak ditemukan dengan exact match, baru coba dengan partial match
                    if (!$typeCriteria) {
                        // Cari dengan partial match tapi lebih spesifik
                        $typeCriteria = TypeCriteria::where(function ($query) use ($typeCriteriaId) {
                            $query->where('criteria', 'like', "{$typeCriteriaId}%")
                                ->orWhere('criteria', 'like', "% {$typeCriteriaId}")
                                ->orWhere('criteria', 'like', "% {$typeCriteriaId} %");
                        })
                            ->orWhere(function ($query) use ($typeCriteriaId) {
                                $query->where('aspect', 'like', "{$typeCriteriaId}%")
                                    ->orWhere('aspect', 'like', "% {$typeCriteriaId}")
                                    ->orWhere('aspect', 'like', "% {$typeCriteriaId} %");
                            })
                            ->first();
                    }

                    // Jika masih belum ditemukan, gunakan metode pencarian lain
                    if (!$typeCriteria && strlen($typeCriteriaId) > 5) {
                        // Coba dengan pencarian yang lebih ketat untuk string yang panjang
                        $words = explode(' ', $typeCriteriaId);

                        // Jika lebih dari satu kata, coba cari yang mengandung semua kata
                        if (count($words) > 1) {
                            $query = TypeCriteria::query();

                            foreach ($words as $word) {
                                if (strlen($word) > 3) { // Abaikan kata pendek seperti "dan", "di", dll
                                    $query->where(function ($q) use ($word) {
                                        $q->where('criteria', 'like', "%{$word}%")
                                            ->orWhere('aspect', 'like', "%{$word}%");
                                    });
                                }
                            }

                            $typeCriteria = $query->first();
                        }
                    }

                    if ($typeCriteria) {
                        $typeCriteriaMap[$typeCriteriaId] = $typeCriteria->id;
                        Log::info("Menemukan ID kriteria: {$typeCriteria->id} untuk nama: {$typeCriteriaId}");
                    } else {
                        // Jika tidak ditemukan, lempar exception
                        throw new \Exception("Kriteria dengan nama '{$typeCriteriaId}' tidak ditemukan di database");
                    }
                }

                // Gunakan ID yang valid dari mapping jika ada
                $typeCriteriaIdValid = isset($typeCriteriaMap[$typeCriteriaId])
                    ? $typeCriteriaMap[$typeCriteriaId]
                    : $typeCriteriaId;

                // Cek type assessment dari question_id
                if (!isset($assessmentTypeMap[$questionId])) {
                    $assessment = Assessment::find($questionId);
                    if ($assessment) {
                        $assessmentTypeMap[$questionId] = $assessment->type;

                        // Validasi tipe assessment
                        if ($assessment->type !== 'selfAssessment' && $assessment->type !== 'selfAssessment') {
                            Log::warning("Question ID: {$questionId} bukan tipe selfAssessment, melainkan: {$assessment->type}");
                        }
                    }
                }

                // Cari project_id dari question_id jika belum ada di map
                if (!isset($projectIdMap[$questionId])) {
                    $assessment = Assessment::find($questionId);
                    if ($assessment && $assessment->project_id) {
                        $projectIdMap[$questionId] = $assessment->project_id;
                    } else {
                        // Cek jika mahasiswa memiliki jawaban dengan question_id ini
                        $existingAnswer = Answers::where('mahasiswa_id', $mahasiswaId)
                            ->where('question_id', $questionId)
                            ->first();

                        if ($existingAnswer) {
                            // Cari assessment dari jawaban ini
                            $assessment = $existingAnswer->question;
                            if ($assessment && $assessment->project_id) {
                                $projectIdMap[$questionId] = $assessment->project_id;
                            }
                        }
                    }
                }

                $projectId = $projectIdMap[$questionId] ?? null;

                if (!$projectId) {
                    $project = Project::where('status', 'active')->latest()->first();
                    $projectId = $project ? $project->id : null;

                    if ($projectId) {
                        $projectIdMap[$questionId] = $projectId;
                    } else {
                        throw new \Exception("Tidak dapat menemukan project untuk pertanyaan ID: $questionId");
                    }
                }

                $mahasiswa = Mahasiswa::find($mahasiswaId);
                $groupId = null;

                if ($mahasiswa) {
                    $group = $mahasiswa->group()
                        ->where('project_id', $projectId)
                        ->latest()
                        ->first();

                    if ($group) {
                        $groupId = $group->id;
                    } else {
                        Log::warning("Mahasiswa ID $mahasiswaId tidak memiliki grup untuk project ID $projectId");

                        $latestGroup = $mahasiswa->group()->latest()->first();
                        if ($latestGroup) {
                            $groupId = $latestGroup->id;
                            Log::info("Menggunakan grup terbaru dengan ID: $groupId untuk mahasiswa: $mahasiswaId");
                        }
                    }
                } else {
                    Log::warning("Mahasiswa dengan ID $mahasiswaId tidak ditemukan");
                }

                $report = Report::where('mahasiswa_id', $mahasiswaId)
                    ->where('typeCriteria_id', $typeCriteriaIdValid)
                    ->where('project_id', $projectId)
                    ->where('question_id', $questionId)
                    ->where('assessment_type', 'selfAssessment')
                    ->first();

                if ($groupId) {
                    if ($report) {
                        $report->final_score_self = $finalScoreSelf;
                        $report->question_id = $questionId;
                        $report->group_id = $groupId;
                        $report->save();
                        Log::info("Report diupdate untuk mahasiswa ID: $mahasiswaId, kriteria ID: $typeCriteriaIdValid");
                    } else {
                        $newReport = Report::create([
                            'mahasiswa_id' => $mahasiswaId,
                            'typeCriteria_id' => $typeCriteriaIdValid,
                            'final_score_self' => $finalScoreSelf,
                            'project_id' => $projectId,
                            'group_id' => $groupId,
                            'question_id' => $questionId,
                            'assessment_type' => 'selfAssessment',
                        ]);
                        Log::info("Report baru dibuat dengan ID: {$newReport->id} untuk mahasiswa ID: $mahasiswaId, kriteria ID: $typeCriteriaIdValid");
                    }
                } else {
                    throw new \Exception("Mahasiswa dengan ID {$mahasiswaId} tidak memiliki grup untuk project ID {$projectId}");
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Jawaban self-assessment berhasil disimpan!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveFinalScoresSelf: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveFinalScoresPeer(Request $request)
    {
        // Validasi data - project_id and group_id are no longer required from frontend
        $validated = $request->validate([
            'answersPeer' => 'required|array',
            'answersPeer.*.mahasiswa_id' => 'required',
            'answersPeer.*.typeCriteria_id' => 'required',
            'answersPeer.*.question_id' => 'required',
            'answersPeer.*.peer_id' => 'required',
            'answersPeer.*.final_score_peer' => 'required|integer|min:1|max:5',
        ]);

        DB::beginTransaction();

        try {
            // Mapping untuk menyimpan data
            $typeCriteriaMap = [];
            $projectMap = []; // Map question_id to project_id
            $groupMap = []; // Map mahasiswa_id and project_id to group_id
            $assessmentTypeMap = []; // Untuk menyimpan tipe assessment

            foreach ($validated['answersPeer'] as $answerPeer) {
                $mahasiswaId = $answerPeer['mahasiswa_id'];
                $typeCriteriaId = $answerPeer['typeCriteria_id'];
                $questionId = $answerPeer['question_id'];
                $peerId = $answerPeer['peer_id'];
                $finalScorePeer = $answerPeer['final_score_peer'];

                // Get project_id from question_id
                if (!isset($projectMap[$questionId])) {
                    // Get assessment and project relation
                    $assessment = Assessment::with('project')->find($questionId);

                    if (!$assessment) {
                        throw new \Exception("Question dengan ID {$questionId} tidak ditemukan");
                    }

                    if (!$assessment->project_id || !$assessment->project) {
                        throw new \Exception("Project untuk question ID {$questionId} tidak ditemukan");
                    }

                    $projectMap[$questionId] = $assessment->project_id;
                    $assessmentTypeMap[$questionId] = $assessment->type;

                    // Validasi tipe assessment
                    if ($assessment->type !== 'peerAssessment' && $assessment->type !== 'peerAssessment') {
                        Log::warning("Question ID: {$questionId} bukan tipe peerAssessment, melainkan: {$assessment->type}");
                    }

                    Log::info("Question ID: {$questionId} terkait dengan Project ID: {$assessment->project_id}");
                }

                $projectId = $projectMap[$questionId];

                // Get group_id for this mahasiswa in this project
                $cacheKey = $mahasiswaId . '_' . $projectId;
                if (!isset($groupMap[$cacheKey])) {
                    $group = Group::where('mahasiswa_id', $mahasiswaId)
                        ->where('project_id', $projectId)
                        ->first();

                    if (!$group) {
                        throw new \Exception("Group untuk mahasiswa ID {$mahasiswaId} di project ID {$projectId} tidak ditemukan");
                    }

                    $groupMap[$cacheKey] = $group->id;
                    Log::info("Menemukan Group ID: {$group->id} untuk Mahasiswa ID: {$mahasiswaId} di Project ID: {$projectId}");
                }

                $groupId = $groupMap[$cacheKey];

                // Cek dan mapping typeCriteria_id jika itu adalah string nama kriteria (bukan UUID)
                if (!isset($typeCriteriaMap[$typeCriteriaId]) && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $typeCriteriaId)) {
                    // FIX: Gunakan exact match terlebih dahulu untuk mencari kriteria
                    $typeCriteria = TypeCriteria::where('criteria', $typeCriteriaId)
                        ->orWhere('aspect', $typeCriteriaId)
                        ->first();

                    // Jika tidak ditemukan dengan exact match, baru coba dengan partial match
                    if (!$typeCriteria) {
                        // Cari dengan partial match tapi lebih spesifik
                        $typeCriteria = TypeCriteria::where(function ($query) use ($typeCriteriaId) {
                            $query->where('criteria', 'like', "{$typeCriteriaId}%")
                                ->orWhere('criteria', 'like', "% {$typeCriteriaId}")
                                ->orWhere('criteria', 'like', "% {$typeCriteriaId} %");
                        })
                            ->orWhere(function ($query) use ($typeCriteriaId) {
                                $query->where('aspect', 'like', "{$typeCriteriaId}%")
                                    ->orWhere('aspect', 'like', "% {$typeCriteriaId}")
                                    ->orWhere('aspect', 'like', "% {$typeCriteriaId} %");
                            })
                            ->first();
                    }

                    // Jika masih belum ditemukan, gunakan metode pencarian lain
                    if (!$typeCriteria && strlen($typeCriteriaId) > 5) {
                        // Coba dengan pencarian yang lebih ketat untuk string yang panjang
                        $words = explode(' ', $typeCriteriaId);

                        // Jika lebih dari satu kata, coba cari yang mengandung semua kata
                        if (count($words) > 1) {
                            $query = TypeCriteria::query();

                            foreach ($words as $word) {
                                if (strlen($word) > 3) { // Abaikan kata pendek seperti "dan", "di", dll
                                    $query->where(function ($q) use ($word) {
                                        $q->where('criteria', 'like', "%{$word}%")
                                            ->orWhere('aspect', 'like', "%{$word}%");
                                    });
                                }
                            }

                            $typeCriteria = $query->first();
                        }
                    }

                    if ($typeCriteria) {
                        $typeCriteriaMap[$typeCriteriaId] = $typeCriteria->id;
                        Log::info("Menemukan ID kriteria: {$typeCriteria->id} untuk nama: {$typeCriteriaId}");
                    } else {
                        // Jika tidak ditemukan, lempar exception
                        throw new \Exception("Kriteria dengan nama '{$typeCriteriaId}' tidak ditemukan di database");
                    }
                }

                // Gunakan ID yang valid dari mapping jika ada
                $typeCriteriaIdValid = isset($typeCriteriaMap[$typeCriteriaId])
                    ? $typeCriteriaMap[$typeCriteriaId]
                    : $typeCriteriaId;


                // Handle peer_id yang berupa objek kompleks
                $peerIdValid = $peerId;
                if (is_object($peerId) || is_array($peerId)) {
                    // Ambil key pertama dari objek sebagai peer_id
                    // atau gunakan JSON jika memang perlu menyimpan semua
                    $peerIdValid = is_object($peerId) ? json_encode($peerId) : key($peerId);
                    Log::info("Mengkonversi peer_id kompleks menjadi: {$peerIdValid}");
                }

                // Update atau buat report
                $report = Report::where('mahasiswa_id', $mahasiswaId)
                    ->where('typeCriteria_id', $typeCriteriaIdValid)
                    ->where('project_id', $projectId)
                    ->where('question_id', $questionId)
                    ->where('peer_id', $peerId)
                    ->where('assessment_type', 'peerAssessment')
                    ->first();

                if ($report) {
                    // Update report yang sudah ada
                    $report->final_score_peer = $finalScorePeer;
                    $report->group_id = $groupId;
                    $report->question_id = $questionId;
                    $report->peer_id = $peerIdValid;
                    $report->assessment_type = $assessmentTypeMap[$questionId] ?? 'peerAssessment';
                    $report->save();
                    Log::info("Report diupdate untuk mahasiswa ID: $mahasiswaId, kriteria ID: $typeCriteriaIdValid");
                } else {
                    // Buat report baru
                    $newReport = Report::create([
                        'mahasiswa_id' => $mahasiswaId,
                        'typeCriteria_id' => $typeCriteriaIdValid,
                        'final_score_peer' => $finalScorePeer,
                        'project_id' => $projectId,
                        'group_id' => $groupId,
                        'question_id' => $questionId,
                        'peer_id' => $peerIdValid,
                        'assessment_type' => $assessmentTypeMap[$questionId] ?? 'peerAssessment',
                        // 'skor_self' => 0,
                        // 'skor_peer' => 0,
                        // 'selisih' => 0,
                        // 'nilai_total' => 0,
                    ]);
                    Log::info("Report baru dibuat dengan ID: {$newReport->id} untuk mahasiswa ID: $mahasiswaId, kriteria ID: $typeCriteriaIdValid");
                }
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Jawaban penilaian peer berhasil disimpan!']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveFinalScoresPeer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban: ' . $e->getMessage()
            ], 500);
        }
    }
}
