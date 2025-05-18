<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Reflective;
use App\Models\ReflectiveRubric;
use App\Exports\ReflectiveAssessmentExport;
use App\Models\ReflectiveAnswer;
use App\Models\Mahasiswa;
use App\Models\Group;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class RefleksiController extends Controller
{
    public function getViewReflective()
    {
        return Inertia::render('Dosen/ReflectiveAssessment');
    }

    public function exportExcel(Request $request)
    {
        try {
            $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
                'type' => 'nullable|string',
            ]);

            $project = Project::where('batch_year', $request->batch_year)
                ->where('project_name', $request->project_name)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            $type = $request->type ?? 'template';
            $filename = "reflective-assessment-{$type}.xlsx";

            return Excel::download(
                new ReflectiveAssessmentExport(
                    $request->batch_year,
                    $request->project_name,
                    $project->id
                ),
                $filename
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
            $sheetRubric = $spreadsheet->getSheetByName('Assessment Rubric');
            $highestRowRubric = $sheetRubric->getHighestRow();

            // Import rubrik
            for ($row = 5; $row <= $highestRowRubric; $row++) {
                $criteria = trim($sheetRubric->getCellByColumnAndRow(2, $row)->getValue());

                if (!empty($criteria)) {
                    ReflectiveRubric::updateOrCreate(
                        ['criteria_reflective' => $criteria],
                        [
                            'bobot_1' => trim($sheetRubric->getCellByColumnAndRow(3, $row)->getValue() ?? ''),
                            'bobot_2' => trim($sheetRubric->getCellByColumnAndRow(4, $row)->getValue() ?? ''),
                            'bobot_3' => trim($sheetRubric->getCellByColumnAndRow(5, $row)->getValue() ?? ''),
                            'bobot_4' => trim($sheetRubric->getCellByColumnAndRow(6, $row)->getValue() ?? ''),
                            'bobot_5' => trim($sheetRubric->getCellByColumnAndRow(7, $row)->getValue() ?? '')
                        ]
                    );
                }
            }

            $sheetAssessment = $spreadsheet->getSheet(0);
            $highestRowAssessment = $sheetAssessment->getHighestRow();

            // Kelompokkan data berdasarkan project terlebih dahulu
            $groupedData = [];
            for ($row = 2; $row <= $highestRowAssessment; $row++) {
                $batchYear = trim($sheetAssessment->getCellByColumnAndRow(2, $row)->getValue());
                $projectName = trim($sheetAssessment->getCellByColumnAndRow(3, $row)->getValue());
                $question = trim($sheetAssessment->getCellByColumnAndRow(4, $row)->getValue());
                $criteria = trim($sheetAssessment->getCellByColumnAndRow(5, $row)->getValue());

                if (!empty($batchYear) && !empty($projectName)) {
                    $projectKey = $batchYear . '-' . $projectName;

                    if (!isset($groupedData[$projectKey])) {
                        $groupedData[$projectKey] = [
                            'batch_year' => $batchYear,
                            'project_name' => $projectName,
                            'items' => []
                        ];
                    }

                    $groupedData[$projectKey]['items'][] = [
                        'question' => $question,
                        'criteria' => $criteria
                    ];
                }
            }

            // Proses data yang sudah dikelompokkan
            foreach ($groupedData as $projectKey => $projectData) {
                $batchYear = $projectData['batch_year'];
                $projectName = $projectData['project_name'];

                // Cari atau buat project
                $project = Project::firstOrCreate([
                    'batch_year' => $batchYear,
                    'project_name' => $projectName
                ]);

                // Tentukan order baru untuk reflective assessment
                $newOrder = Reflective::where('project_id', $project->id)
                    ->max('reflective_assessment_order') ?? 0;
                $newOrder++;

                // Buat entry untuk setiap item dalam 1 order yang sama
                foreach ($projectData['items'] as $item) {
                    $typeCriteria = ReflectiveRubric::where('criteria_reflective', $item['criteria'])->first();

                    if ($typeCriteria) {
                        Reflective::create([
                            'batch_year' => $batchYear,
                            'project_id' => $project->id,
                            'question' => $item['question'],
                            'criteria_id' => $typeCriteria->id,
                            'end_date' => $request->end_date,
                            'reflective_assessment_order' => $newOrder // Gunakan order yang sama untuk semua item dalam satu import
                        ]);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Import berhasil'], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Import gagal',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getReflectiveAssessmentList()
    {
        try {
            $projectIds = Reflective::distinct('project_id')->pluck('project_id');

            $projects = Project::whereIn('id', $projectIds)
                ->where('status', 'Active')
                ->orderBy('created_at', 'desc')
                ->get();

            $result = [];

            foreach ($projects as $project) {
                $reflective = Reflective::where('project_id', $project->id)
                    ->select('reflective_assessment_order')
                    ->distinct()
                    ->orderBy('reflective_assessment_order')
                    ->pluck('reflective_assessment_order');

                foreach ($reflective as $order) {
                    $isPublished = Reflective::where('project_id', $project->id)
                        ->where('reflective_assessment_order', $order)
                        ->value('is_published');

                    $isPublished = $isPublished !== null ? $isPublished : 0;

                    $result[] = [
                        'id' => $project->id,
                        'batch_year' => $project->batch_year,
                        'project_name' => $project->project_name,
                        'reflective_assessment_order' => $order,
                        'status' => $project->status,
                        'is_published' => $isPublished,
                        'created_at' => $project->created_at,
                        'unique_key' => $project->id . '-' . $order,
                    ];
                }
            }
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error in getProyekSelfAssessment:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch self assessment projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function togglePublish(Request $request)
    {
        try {
            DB::enableQueryLog();

            $project = Project::where('batch_year', $request->batch_year)
                ->where('project_name', $request->project_name)
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => 'Project not found'
                ], 404);
            }

            $updated = Reflective::where([
                'project_id' => $project->id,
                'reflective_assessment_order' => $request->reflective_assessment_order
            ])->update([
                'is_published' => $request->is_published ? 1 : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Assessment publish status updated successfully',
                'data' => [
                    'is_published' => $request->is_published,
                    'updated_count' => $updated
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Toggle publish error:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to update publish status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDetailReflective(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');
        $assessmentOrder = $request->query('reflective_assessment_order', 1);

        // Load the relationship as "reflective_rubric" to match the Vue component
        $assessments = Reflective::with(['rubric' => function ($query) {
            $query->select('id', 'criteria_reflective as criteria', 'bobot_1', 'bobot_2', 'bobot_3', 'bobot_4', 'bobot_5');
        }])
            ->select(
                'reflective_assessment.id',
                'reflective_assessment.question',
                'reflective_assessment.criteria_id',
                'reflective_assessment.batch_year',
                'reflective_assessment.project_id',
                'reflective_assessment.reflective_assessment_order'
            )
            ->join('project', 'reflective_assessment.project_id', '=', 'project.id')
            ->when($batchYear, function ($query, $batchYear) {
                $query->where('reflective_assessment.batch_year', $batchYear);
            })
            ->when($projectName, function ($query, $projectName) {
                $query->where('project.project_name', $projectName);
            })
            ->where('reflective_assessment.reflective_assessment_order', $assessmentOrder)
            ->orderBy('reflective_assessment.id', 'asc')
            ->get()
            ->map(function ($assessment) {
                // Rename the "rubric" relationship to "reflective_rubric" to match Vue component
                $assessment->reflective_rubric = $assessment->rubric;
                unset($assessment->rubric);
                return $assessment;
            });

        $totalOrders = Reflective::join('project', 'reflective_assessment.project_id', '=', 'project.id')
            ->where('reflective_assessment.batch_year', $batchYear)
            ->where('project.project_name', $projectName)
            ->distinct('reflective_assessment_order')
            ->count('reflective_assessment_order');

        return Inertia::render('Dosen/detailReflectiveAssessment', [
            'assessments' => $assessments,
            'batchYear' => $batchYear,
            'projectName' => $projectName,
            'currentOrder' => (int)$assessmentOrder,
            'totalOrders' => $totalOrders
        ]);
    }

    public function showDetailAnswer(Request $request)
    {
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'reflective_assessment_order' => 'required|integer',
        ]);

        return Inertia::render('Dosen/ListReflectiveAnswer', [
            'batchYear' => $validated['batch_year'],
            'projectName' => $validated['project_name'],
            'reflective_assessment_order' => $validated['reflective_assessment_order'],
        ]);
    }

    public function getAnswerReflectiveAssessment(Request $request)
    {
        $validated = $request->validate([
            'batchYear' => 'required|string',
            'projectName' => 'required|string', // Using projectName instead of projectId
            'reflective_assessment_order' => 'required|integer',
        ]);

        $batchYear = $validated['batchYear'];
        $projectName = $validated['projectName'];
        $reflectiveAssessmentOrder = $validated['reflective_assessment_order'];

        // Find the project ID based on batch year and project name
        $project = Project::where('batch_year', $batchYear)
            ->where('project_name', $projectName)
            ->first();

        if (!$project) {
            return response()->json(['message' => 'Project not found with the specified batch year and name.'], 404);
        }

        $projectId = $project->id;

        // Get the specific reflective assessment by order
        $assessment = Reflective::where('batch_year', $batchYear)
            ->where('project_id', $projectId)
            ->where('reflective_assessment_order', $reflectiveAssessmentOrder)
            ->first();

        if (!$assessment) {
            return response()->json([
                'message' => 'No reflective assessment found with the specified order.',
                'project' => [
                    'name' => $projectName,
                    'id' => $projectId,
                    'batch_year' => $batchYear
                ]
            ], 404);
        }

        // Get all users in the specified project group
        $usersInGroup = Group::where('batch_year', $batchYear)
            ->where('project_id', $projectId)
            ->pluck('mahasiswa_id');

        if ($usersInGroup->isEmpty()) {
            return response()->json(['message' => 'No students found in this project group.'], 404);
        }

        // Get answers for the specified assessment
        $answers = ReflectiveAnswer::whereIn('mahasiswa_id', $usersInGroup)
            ->where('question_id', $assessment->id)
            ->with(['mahasiswa.user'])  // Include the user relationship to get the name
            ->get();

        $userAnswers = $answers->groupBy('mahasiswa_id');

        $result = [];

        foreach ($usersInGroup as $mahasiswaId) {
            $mahasiswa = Mahasiswa::with('user')->find($mahasiswaId);

            if (!$mahasiswa) {
                continue; // Skip if mahasiswa not found
            }

            $userAnswered = isset($userAnswers[$mahasiswaId]) ? $userAnswers[$mahasiswaId] : collect();

            // Since we're only looking at one specific reflective assessment question,
            // the status is simply whether they've answered it or not
            if ($userAnswered->count() === 0) {
                $status = 'unsubmitted';
            } else {
                $status = 'submitted';
            }

            $result[] = [
                'mahasiswa' => $mahasiswa,
                'status' => $status,
                'answers' => $userAnswered,
            ];
        }

        // Sort the result array by NIM (assuming NIM is in the user's nim field)
        usort($result, function ($a, $b) {
            return $a['mahasiswa']->nim <=> $b['mahasiswa']->nim;
        });

        return response()->json([
            'assessment' => $assessment,
            'project' => $project,
            'students' => $result
        ]);
    }

    public function getViewDetailsAnswer(Request $request)
    {
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'reflective_assessment_order' => 'required|integer',
            'mahasiswaId' => 'required|string',
        ]);

        $mahasiswa = Mahasiswa::findOrFail($validated['mahasiswaId']);

        // Jika menggunakan Inertia untuk SPA, gunakan:
        return Inertia::render('Dosen/AnswerDetailReflective', [
            'mahasiswaName' => $mahasiswa->user->name,
            'mahasiswaId' => $validated['mahasiswaId'],
            'batch_year' => $validated['batch_year'],
            'project_name' => $validated['project_name'],
            'assessment_order' => $validated['reflective_assessment_order'],
        ]);
    }

    public function getDetailsAnswerReflective(Request $request)
    {
        $validated = $request->validate([
            'mahasiswaId' => 'required|string',
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'assessment_order' => 'required|integer',
        ]);

        // Find mahasiswa directly by ID
        $mahasiswa = Mahasiswa::findOrFail($validated['mahasiswaId']);

        // Find project_id based on project_name and batch_year
        $project = Project::where('project_name', $validated['project_name'])
            ->where('batch_year', $validated['batch_year'])
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project tidak ditemukan'], 404);
        }

        $project_id = $project->id;

        // Find the reflective assessment first
        $reflective = Reflective::where('batch_year', $validated['batch_year'])
            ->where('project_id', $project_id)
            ->where('reflective_assessment_order', $validated['assessment_order'])
            ->get();

        if ($reflective->isEmpty()) {
            return response()->json(['message' => 'Penilaian reflektif tidak ditemukan'], 404);
        }

        // Get question IDs from the reflective assessments
        $questionIds = $reflective->pluck('id')->toArray();

        // Now get the answers using those question IDs
        $answers = ReflectiveAnswer::where('mahasiswa_id', $mahasiswa->id)
            ->whereIn('question_id', $questionIds)
            ->get();

        if ($answers->isEmpty()) {
            return response()->json(['message' => 'Jawaban tidak ditemukan untuk mahasiswa ini'], 404);
        }

        // Map the answers to include the questions and criteria
        $formattedAnswers = $answers->map(function ($answer) use ($reflective) {
            // Find the corresponding reflective assessment
            $assessment = $reflective->where('id', $answer->question_id)->first();

            // Default values if assessment is not found
            $questionText = 'Pertanyaan tidak ditemukan';
            $criteria = null;

            if ($assessment) {
                $questionText = $assessment->question;

                // Get the rubric criteria if criteria_id exists
                if ($assessment->criteria_id) {
                    $rubric = ReflectiveRubric::find($assessment->criteria_id);
                    if ($rubric) {
                        $criteria = $rubric->criteria_reflective;
                    }
                }
            }

            return [
                'pertanyaan' => $questionText,
                'jawaban' => $answer->answer,
                'kriteria' => $criteria,
            ];
        });

        return response()->json([
            'answers' => $formattedAnswers,
        ]);
    }
}
