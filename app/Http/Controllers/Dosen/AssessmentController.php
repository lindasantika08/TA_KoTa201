<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\TypeCriteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\Project;
use App\Models\User;
use App\Models\Dosen;
use App\Models\Mahasiswa;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exports\AssessmentExport;
use App\Jobs\ImportCriteriaToFlask;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelType;
use PhpOffice\PhpSpreadsheet\IOFactory;



class AssessmentController extends Controller
{
    public function create()
    {
        return Inertia::render('Dosen/CreateAssessment');
    }

    public function self()
    {
        return Inertia::render('Dosen/SelfAssessment');
    }

    public function peer()
    {
        return Inertia::render('Dosen/PeerAssessment');
    }


    public function exportExcel(Request $request)
    {
        try {
            $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
            ]);

            $project = Project::where('batch_year', $request->batch_year)
                ->where('project_name', $request->project_name)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            return Excel::download(
                new AssessmentExport(
                    $request->batch_year,
                    $request->project_name,
                    $project->id
                ),
                'assessment-template.xlsx'
            );
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Internal Server Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
            'end_date' => 'required|date',
        ]);

        try {
            DB::beginTransaction();

            $spreadsheet = IOFactory::load($request->file('file'));

            $sheet2 = $spreadsheet->getSheetByName('Type Criteria');
            $highestRow2 = $sheet2->getHighestRow();

            for ($row = 5; $row <= $highestRow2; $row++) {
                $aspect = $sheet2->getCellByColumnAndRow(2, $row)->getValue();
                $criteria = $sheet2->getCellByColumnAndRow(3, $row)->getValue();

                if (!empty($aspect) && !empty($criteria)) {
                    TypeCriteria::updateOrCreate(
                        [
                            'aspect' => trim($aspect),
                            'criteria' => trim($criteria)
                        ],
                        [
                            'bobot_1' => trim($sheet2->getCellByColumnAndRow(4, $row)->getValue() ?? ''),
                            'bobot_2' => trim($sheet2->getCellByColumnAndRow(5, $row)->getValue() ?? ''),
                            'bobot_3' => trim($sheet2->getCellByColumnAndRow(6, $row)->getValue() ?? ''),
                            'bobot_4' => trim($sheet2->getCellByColumnAndRow(7, $row)->getValue() ?? ''),
                            'bobot_5' => trim($sheet2->getCellByColumnAndRow(8, $row)->getValue() ?? '')
                        ]
                    );
                }
            }

            $sheet1 = $spreadsheet->getSheet(0);
            $highestRow1 = $sheet1->getHighestRow();

            $projectsInImport = [];

            for ($row = 2; $row <= $highestRow1; $row++) {
                $batchYear = trim($sheet1->getCellByColumnAndRow(2, $row)->getValue());
                $projectName = trim($sheet1->getCellByColumnAndRow(3, $row)->getValue());

                if (!empty($batchYear) && !empty($projectName)) {
                    $project = Project::firstOrCreate(
                        [
                            'batch_year' => $batchYear,
                            'project_name' => $projectName
                        ]
                    );

                    $projectsInImport[$project->id] = true;
                }
            }

            $projectOrders = [];
            foreach (array_keys($projectsInImport) as $projectId) {
                $maxOrder = Assessment::where('project_id', $projectId)
                    ->max('assessment_order') ?? 0;
                $projectOrders[$projectId] = $maxOrder + 1;
            }

            $createdAssessmentIds = []; // Track assessment IDs created during import

            for ($row = 2; $row <= $highestRow1; $row++) {
                $batchYear = trim($sheet1->getCellByColumnAndRow(2, $row)->getValue());
                $projectName = trim($sheet1->getCellByColumnAndRow(3, $row)->getValue());
                $type = trim($sheet1->getCellByColumnAndRow(4, $row)->getValue());
                $question = trim($sheet1->getCellByColumnAndRow(5, $row)->getValue());
                $skilltype = trim($sheet1->getCellByColumnAndRow(6, $row)->getValue());
                $aspect = trim($sheet1->getCellByColumnAndRow(7, $row)->getValue());
                $criteria = trim($sheet1->getCellByColumnAndRow(8, $row)->getValue());

                if (!empty($batchYear) && !empty($projectName)) {
                    $project = Project::where('batch_year', $batchYear)
                        ->where('project_name', $projectName)
                        ->first();

                    $typeCriteria = TypeCriteria::where('aspect', $aspect)
                        ->where('criteria', $criteria)
                        ->first();

                    if ($typeCriteria && $project) {
                        $assessment = Assessment::create([
                            'batch_year' => $batchYear,
                            'project_id' => $project->id,
                            'type' => $type,
                            'question' => $question,
                            'criteria_id' => $typeCriteria->id,
                            'end_date' => $request->end_date,
                            'skill_type' => $skilltype,
                            'assessment_order' => $projectOrders[$project->id]
                        ]);

                        // Add assessment ID to the import list
                        $createdAssessmentIds[] = $assessment->id;
                    }
                }
            }

            DB::commit();

            // After successful import, dispatch jobs to send criteria to Flask
            $this->dispatchAssessmentsToFlask($createdAssessmentIds);

            return response()->json(['message' => 'Data berhasil diimpor dan sedang dikirim ke Flask']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * Dispatch jobs to send assessment data to Flask
     * 
     * @param array $assessmentIds IDs of assessments to send
     * @return void
     */
    private function dispatchAssessmentsToFlask(array $assessmentIds)
    {
        foreach ($assessmentIds as $assessmentId) {
            // Dispatch with a small delay between each job to prevent overwhelming the Flask server
            ImportCriteriaToFlask::dispatch($assessmentId)->delay(now()->addSeconds(rand(1, 10)));

            Log::info("Dispatched ImportCriteriaToFlask job for assessment ID: {$assessmentId}");
        }
    }

    public function getData()
    {
        $assessments = Assessment::all();
        return response()->json($assessments);
    }

    public function getAssessmentsWithBobotSelf(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');
        $assessmentOrder = $request->query('assessment_order', 1);

        $assessments = Assessment::with('typeCriteria')
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'assessment.criteria_id',
                'assessment.batch_year',
                'assessment.project_id',
                'assessment.assessment_order'
            )
            ->join('project', 'assessment.project_id', '=', 'project.id')
            ->when($batchYear, function ($query, $batchYear) {
                $query->where('assessment.batch_year', $batchYear);
            })
            ->when($projectName, function ($query, $projectName) {
                $query->where('project.project_name', $projectName);
            })
            ->where('assessment.type', 'selfAssessment')
            ->where('assessment.assessment_order', $assessmentOrder)
            ->orderBy('assessment.id', 'asc')
            ->get();

        $totalOrders = Assessment::join('project', 'assessment.project_id', '=', 'project.id')
            ->where('assessment.batch_year', $batchYear)
            ->where('project.project_name', $projectName)
            ->where('assessment.type', 'selfAssessment')
            ->distinct('assessment_order')
            ->count('assessment_order');

        return Inertia::render('Dosen/SelfAssessment', [
            'assessments' => $assessments,
            'batchYear' => $batchYear,
            'projectName' => $projectName,
            'currentOrder' => (int)$assessmentOrder,
            'totalOrders' => $totalOrders
        ]);
    }

    public function CreteProyek()
    {
        $tahunAjaranList = Assessment::distinct()->pluck('tahun_ajaran');
        $namaProyekList = Assessment::distinct()->pluck('nama_proyek');

        return Inertia::render('Dosen/CreateAssessment', [
            'tahunAjaranList' => $tahunAjaranList,
            'namaProyekList' => $namaProyekList,
        ]);
    }

    public function saveAnswer(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi input
            $validated = $request->validate([
                'question_id' => 'required|uuid',
                'answer' => 'required|string',
                'score' => 'required|integer|between:1,5',
                'status' => 'required|string',
                'role' => 'required|string|in:dosen,mahasiswa' // Validasi role
            ]);

            // Ambil informasi pengguna yang sedang login
            $user = Auth::user();
            $userId = $user->id;

            // Ambil data dosen berdasarkan user_id
            $dosen = Dosen::where('user_id', $userId)->first();

            // Log informasi tentang dosen
            Log::info('Dosen retrieved:', [
                'dosen_id' => $dosen ? $dosen->id : null,
                'user_id' => $userId
            ]);

            // Siapkan data untuk disimpan
            $answerData = [
                'question_id' => $validated['question_id'],
                'answer' => $validated['answer'],
                'score' => $validated['score'],
                'status' => $validated['status'],
            ];

            // Tentukan ID berdasarkan role yang diterima dari frontend
            if ($validated['role'] === 'dosen') {
                if ($dosen) {
                    $answerData['dosen_id'] = $dosen->id; // Simpan ID dosen
                } else {
                    throw new \Exception('Dosen tidak ditemukan untuk user_id: ' . $userId);
                }
                $answerData['mahasiswa_id'] = null; // Pastikan mahasiswa_id null
            } elseif ($validated['role'] === 'mahasiswa') {
                $answerData['mahasiswa_id'] = $userId; // Simpan ID mahasiswa
                $answerData['dosen_id'] = null; // Pastikan dosen_id null
            }

            // Simpan atau perbarui jawaban
            $answer = Answers::updateOrCreate(
                [
                    'question_id' => $validated['question_id'],
                    'dosen_id' => $answerData['dosen_id'],
                    'mahasiswa_id' => $answerData['mahasiswa_id'],
                ],
                $answerData
            );

            DB::commit();

            return response()->json([
                'message' => $answer->wasRecentlyCreated ?
                    'Answer saved successfully' :
                    'Answer updated successfully',
                'answer' => $answer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAnswer:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to save answer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function saveAllAnswers(Request $request)
    {
        DB::beginTransaction();
        try {
            // Validasi input
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string'
            ]);

            // Ambil informasi pengguna yang sedang login
            $user = Auth::user();
            $userId = $user->id;

            // Ambil dosen berdasarkan user_id
            $dosen = Dosen::where('user_id', $userId)->first();
            Log::info('Dosen retrieved:', ['dosen' => $dosen]);

            // Loop melalui setiap jawaban dan simpan
            foreach ($validated['answers'] as $answerData) {
                // Siapkan data untuk disimpan
                $dataToSave = [
                    'question_id' => $answerData['question_id'],
                    'answer' => $answerData['answer'],
                    'score' => $answerData['score'],
                    'status' => $answerData['status'],
                    'dosen_id' => $dosen ? $dosen->id : null, // Simpan ID dosen jika ada
                    'mahasiswa_id' => null // Atur mahasiswa_id jika diperlukan
                ];

                // Jika role adalah mahasiswa, ambil mahasiswa_id
                if ($request->input('role') === 'mahasiswa') {
                    $dataToSave['mahasiswa_id'] = $userId; // Simpan ID mahasiswa
                    $dataToSave['dosen_id'] = null; // Pastikan dosen_id null
                }

                // Simpan atau perbarui jawaban
                Answers::updateOrCreate(
                    [
                        'question_id' => $dataToSave['question_id'],
                        'dosen_id' => $dataToSave['dosen_id'],
                        'mahasiswa_id' => $dataToSave['mahasiswa_id'],
                    ],
                    $dataToSave
                );
            }

            DB::commit();

            return response()->json(['message' => 'All answers saved successfully.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAllAnswers:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to save answers: ' . $e->getMessage()], 500);
        }
    }
}
