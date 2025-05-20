<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Reflective;
use App\Models\ReflectiveRubric;
use App\Exports\ReflectiveAssessmentExport;
use App\Exports\ReflectiveWritingExport;
use App\Models\ReflectiveAnswer;
use App\Models\reflective_writing;
use App\Models\reflective_ai;
use App\Models\Mahasiswa;
use App\Models\Group;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
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
                'reflective_type' => 'nullable|string',
            ]);

            $project = Project::where('batch_year', $request->batch_year)
                ->where('project_name', $request->project_name)
                ->first();

            if (!$project) {
                return response()->json(['error' => 'Project not found'], 404);
            }

            $type = $request->type ?? 'template';
            $reflectiveType = $request->reflective_type ?? 'Reflective Assessment';

            // Format the filename to include the reflective type
            $reflectiveTypeSlug = strtolower(str_replace(' ', '-', $reflectiveType));
            $filename = "{$reflectiveTypeSlug}-{$type}.xlsx";

            // Use different export class based on reflective type
            if ($reflectiveType === 'Reflective Writing') {
                // Use the Reflective Writing export class (to be created later)
                return Excel::download(
                    new ReflectiveWritingExport(
                        $request->batch_year,
                        $request->project_name,
                        $project->id,
                        $reflectiveType
                    ),
                    $filename
                );
            } else {
                // Default to Reflective Assessment export
                return Excel::download(
                    new ReflectiveAssessmentExport(
                        $request->batch_year,
                        $request->project_name,
                        $project->id,
                        $reflectiveType
                    ),
                    $filename
                );
            }
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

            // Determine reflective type from Excel file
            $firstSheet = $spreadsheet->getSheet(0);
            $reflectiveType = 'Reflective Assessment'; // Default type

            // Check first sheet title/name
            $sheetTitle = $spreadsheet->getSheet(0)->getTitle();
            if (stripos($sheetTitle, 'writing') !== false) {
                $reflectiveType = 'Reflective Writing';
            }

            // If that doesn't work, check cell D2 which should contain the reflective type based on export template
            // Based on your export class, this should be in the "Reflective Type" column
            if ($firstSheet->getHighestRow() >= 2) {
                $typeCell = $firstSheet->getCellByColumnAndRow(4, 2)->getValue(); // Column D (4th column)
                if (!empty($typeCell)) {
                    if (stripos($typeCell, 'writing') !== false) {
                        $reflectiveType = 'Reflective Writing';
                    } elseif (stripos($typeCell, 'assessment') !== false) {
                        $reflectiveType = 'Reflective Assessment';
                    }
                }
            }

            // Branch based on reflective type
            if ($reflectiveType === 'Reflective Assessment') {
                return $this->importReflectiveAssessment($spreadsheet, $request->end_date);
            } else {
                return $this->importReflectiveWriting($spreadsheet, $request->end_date);
            }
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Import gagal',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Import Reflective Assessment data from Excel
     */
    private function importReflectiveAssessment($spreadsheet, $endDate)
    {
        // Get the rubric sheet
        $sheetRubric = $spreadsheet->getSheetByName('Reflective Rubric');
        if (!$sheetRubric) {
            // Try alternative names
            foreach (['Rubric', 'Rubric Reflective', 'Reflective'] as $sheetName) {
                try {
                    $sheetRubric = $spreadsheet->getSheetByName($sheetName);
                    if ($sheetRubric) break;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // Import rubric if found
        if ($sheetRubric) {
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
        }

        $sheetAssessment = $spreadsheet->getSheet(0);
        $highestRowAssessment = $sheetAssessment->getHighestRow();

        // Kelompokkan data berdasarkan project terlebih dahulu
        $groupedData = [];
        for ($row = 2; $row <= $highestRowAssessment; $row++) {
            // Based on your export sheet columns:
            // A: No
            // B: Batch Year (column 2)
            // C: Project Name (column 3)
            // D: Reflective Type (column 4)
            // E: Question (column 5)
            // F: Criteria ID (column 6)

            $batchYear = trim($sheetAssessment->getCellByColumnAndRow(2, $row)->getValue());
            $projectName = trim($sheetAssessment->getCellByColumnAndRow(3, $row)->getValue());
            $reflectiveType = trim($sheetAssessment->getCellByColumnAndRow(4, $row)->getValue());
            $question = trim($sheetAssessment->getCellByColumnAndRow(5, $row)->getValue());
            $criteriaId = trim($sheetAssessment->getCellByColumnAndRow(6, $row)->getValue());

            // Get criteria from criteria_id if available, otherwise look up by text
            $criteria = '';
            if (!empty($criteriaId) && is_numeric($criteriaId)) {
                // Use directly if ID is provided
                $criteria = $criteriaId;
            } else {
                // Get the text from the cell and look up ID later
                $criteria = $criteriaId;
            }

            if (!empty($batchYear) && !empty($projectName) && !empty($question)) {
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
                    'criteria' => $criteria,
                    'reflective_type' => $reflectiveType  // Store reflective type with each item
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

            // Group items by reflective type to create proper orders
            $typeGroups = [];
            foreach ($projectData['items'] as $item) {
                $type = $item['reflective_type'];
                if (!isset($typeGroups[$type])) {
                    $typeGroups[$type] = [];
                }
                $typeGroups[$type][] = $item;
            }

            // Process each reflective type group
            foreach ($typeGroups as $reflectiveType => $items) {
                // Tentukan order baru untuk reflective assessment
                $newOrder = Reflective::where('project_id', $project->id)
                    ->where('type', $reflectiveType)
                    ->max('reflective_assessment_order') ?? 0;
                $newOrder++;

                // Buat entry untuk setiap item dalam 1 order yang sama
                foreach ($items as $item) {
                    $criteriaId = null;

                    // Handle criteria based on whether it's an ID or text
                    if (is_numeric($item['criteria'])) {
                        // Direct ID was provided
                        $criteriaId = $item['criteria'];
                    } else {
                        // Need to find ID by text
                        $typeCriteria = ReflectiveRubric::where('criteria_reflective', $item['criteria'])->first();
                        if ($typeCriteria) {
                            $criteriaId = $typeCriteria->id;
                        }
                    }

                    if ($criteriaId) {
                        Reflective::create([
                            'batch_year' => $batchYear,
                            'project_id' => $project->id,
                            'question' => $item['question'],
                            'type' => $reflectiveType,  // Now properly using the type for each group
                            'criteria_id' => $criteriaId,
                            'end_date' => $endDate,
                            'reflective_assessment_order' => $newOrder
                        ]);
                    }
                }
            }
        }

        DB::commit();

        return response()->json(['message' => 'Import Reflective Assessment berhasil'], 200);
    }

    private function importReflectiveWriting($spreadsheet, $endDate)
    {
        $reflectiveWriting = $spreadsheet->getSheetByName('Reflective Writing');
        if (!$reflectiveWriting) {
            // Try alternative names
            foreach (['Reflective', 'Reflective Writing', 'Writing'] as $sheetName) {
                try {
                    $reflectiveWriting = $spreadsheet->getSheetByName($sheetName);
                    if ($reflectiveWriting) break;
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        // If no reflective writing sheet found, return error
        if (!$reflectiveWriting) {
            return response()->json(['message' => 'Reflective Writing sheet not found in uploaded file'], 422);
        }

        // Get highest row
        $highestRow = $reflectiveWriting->getHighestRow();

        // Group data by project and reflective type to assign proper order
        $groupedData = [];

        // First pass - gather all data
        for ($row = 2; $row <= $highestRow; $row++) {
            // Read cell values based on the ReflectiveWritingExport format
            $batchYear = trim($reflectiveWriting->getCellByColumnAndRow(2, $row)->getValue());
            $projectName = trim($reflectiveWriting->getCellByColumnAndRow(3, $row)->getValue());
            $reflectiveType = trim($reflectiveWriting->getCellByColumnAndRow(4, $row)->getValue());
            $point1 = trim($reflectiveWriting->getCellByColumnAndRow(5, $row)->getValue() ?? '');
            $point2 = trim($reflectiveWriting->getCellByColumnAndRow(6, $row)->getValue() ?? '');
            $point3 = trim($reflectiveWriting->getCellByColumnAndRow(7, $row)->getValue() ?? '');
            $point4 = trim($reflectiveWriting->getCellByColumnAndRow(8, $row)->getValue() ?? '');
            $point5 = trim($reflectiveWriting->getCellByColumnAndRow(9, $row)->getValue() ?? '');

            // Skip empty rows
            if (empty($batchYear) || empty($projectName) || empty($reflectiveType)) {
                continue;
            }

            // Skip if all points are empty
            if (empty($point1) && empty($point2) && empty($point3) && empty($point4) && empty($point5)) {
                continue;
            }

            $projectKey = $batchYear . '-' . $projectName;

            if (!isset($groupedData[$projectKey])) {
                $groupedData[$projectKey] = [
                    'batch_year' => $batchYear,
                    'project_name' => $projectName,
                    'types' => []
                ];
            }

            if (!isset($groupedData[$projectKey]['types'][$reflectiveType])) {
                $groupedData[$projectKey]['types'][$reflectiveType] = [];
            }

            $groupedData[$projectKey]['types'][$reflectiveType][] = [
                'point_1' => $point1,
                'point_2' => $point2,
                'point_3' => $point3,
                'point_4' => $point4,
                'point_5' => $point5
            ];
        }

        // Second pass - create records with proper ordering
        foreach ($groupedData as $projectKey => $projectData) {
            $batchYear = $projectData['batch_year'];
            $projectName = $projectData['project_name'];

            // Find or create project
            $project = Project::firstOrCreate([
                'batch_year' => $batchYear,
                'project_name' => $projectName
            ]);

            // Untuk setiap tipe reflektif, kita akan mengambil nomor urut sekarang di database
            foreach ($projectData['types'] as $reflectiveType => $items) {
                // Get the current maximum order for this project and type directly from the database
                $maxOrder = reflective_writing::where('project_id', $project->id)
                    ->where('type', $reflectiveType)
                    ->max('reflective_writing_order') ?? 0;

                // Increment order just once for this import batch
                $newOrder = $maxOrder + 1;

                // Create entries for each item with the same order number
                foreach ($items as $item) {
                    reflective_writing::create([
                        'batch_year' => $batchYear,
                        'project_id' => $project->id,
                        'type' => $reflectiveType,
                        'point_1' => $item['point_1'],
                        'point_2' => $item['point_2'],
                        'point_3' => $item['point_3'],
                        'point_4' => $item['point_4'],
                        'point_5' => $item['point_5'],
                        'end_date' => $endDate,
                        'reflective_writing_order' => $newOrder,  // Same order for all items in this import
                        'is_published' => true  // Using is_published instead of is_active to match model definition
                    ]);
                }
            }
        }

        DB::commit();

        return response()->json(['message' => 'Import Reflective Writing berhasil'], 200);
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

    public function getReflectiveWritingList()
    {
        try {
            $projectIds = reflective_writing::distinct('project_id')->pluck('project_id');

            $projects = Project::whereIn('id', $projectIds)
                ->where('status', 'Active')
                ->orderBy('created_at', 'desc')
                ->get();

            $result = [];

            foreach ($projects as $project) {
                $reflective = reflective_writing::where('project_id', $project->id)
                    ->select('reflective_writing_order')
                    ->distinct()
                    ->orderBy('reflective_writing_order')
                    ->pluck('reflective_writing_order');

                foreach ($reflective as $order) {
                    $isPublished = reflective_writing::where('project_id', $project->id)
                        ->where('reflective_writing_order', $order)
                        ->value('is_published');

                    $isPublished = $isPublished !== null ? $isPublished : 0;

                    $result[] = [
                        'id' => $project->id,
                        'batch_year' => $project->batch_year,
                        'project_name' => $project->project_name,
                        'reflective_writing_order' => $order,
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

    public function togglePublishWriting(Request $request)
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

            $updated = reflective_writing::where([
                'project_id' => $project->id,
                'reflective_writing_order' => $request->reflective_writing_order
            ])->update([
                'is_published' => $request->is_published ? 1 : 0
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Reflective Writing publish status updated successfully',
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

    public function getDetailReflectiveWriting(Request $request)
    {
        $batchYear = $request->query('batch_year');
        $projectName = $request->query('project_name');
        $assessmentOrder = $request->query('reflective_writing_order', 1);

        $assessments = \App\Models\reflective_writing::select(
            'reflective_writing.id',
            'reflective_writing.batch_year',
            'reflective_writing.project_id',
            'reflective_writing.reflective_writing_order',
            'reflective_writing.type',
            'reflective_writing.point_1',
            'reflective_writing.point_2',
            'reflective_writing.point_3',
            'reflective_writing.point_4',
            'reflective_writing.point_5',
            'reflective_writing.end_date',
            'reflective_writing.is_published'
        )
            ->join('project', 'reflective_writing.project_id', '=', 'project.id')
            ->when($batchYear, function ($query, $batchYear) {
                $query->where('reflective_writing.batch_year', $batchYear);
            })
            ->when($projectName, function ($query, $projectName) {
                $query->where('project.project_name', $projectName);
            })
            ->where('reflective_writing.reflective_writing_order', $assessmentOrder)
            ->orderBy('reflective_writing.id', 'asc')
            ->get();

        $totalOrders = \App\Models\reflective_writing::join('project', 'reflective_writing.project_id', '=', 'project.id')
            ->where('reflective_writing.batch_year', $batchYear)
            ->where('project.project_name', $projectName)
            ->distinct('reflective_writing_order')
            ->count('reflective_writing_order');

        return Inertia::render('Dosen/DetailReflectiveWriting', [
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

    protected $geminiService;

    /**
     * Constructor
     */
    public function __construct()
    {
        // You can inject GeminiService here if needed
    }

    /**
     * Get reflective summary for a student and project
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getReflectiveSummary(Request $request)
    {
        try {
            $validated = $request->validate([
                'mahasiswa_id' => 'required|string',
                'project_name' => 'required|string',
                'batch_year' => 'required|string',
                'force_regenerate' => 'sometimes|boolean'
            ]);

            $mahasiswa = Mahasiswa::with('user')->find($validated['mahasiswa_id']);
            if (!$mahasiswa) {
                return response()->json([
                    'success' => false,
                    'message' => "Mahasiswa tidak ditemukan.",
                ], 404);
            }

            // Find project by project_name and batch_year
            $project = Project::where('project_name', $validated['project_name'])
                ->where('batch_year', $validated['batch_year'])
                ->first();

            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => "Proyek tidak ditemukan dengan nama dan tahun yang diberikan.",
                ], 404);
            }

            // Check if summary already exists
            $existingSummary = reflective_ai::where([
                'mahasiswa_id' => $validated['mahasiswa_id'],
                'project_id' => $project->id
            ])->first();

            // Return existing summary if available and not forced to regenerate
            if ($existingSummary && !($validated['force_regenerate'] ?? false)) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'mahasiswa_id' => $mahasiswa->id,
                        'mahasiswa_name' => $mahasiswa->user->name,
                        'mahasiswa_nim' => $mahasiswa->nim,
                        'project_id' => $project->id,
                        'project_name' => $project->project_name,
                        'batch_year' => $project->batch_year,
                        'summary' => $existingSummary->summary,
                        'source' => 'database'
                    ]
                ]);
            }

            // Get reflective questions for this project
            $questions = Reflective::where('project_id', $project->id)->get();
            if ($questions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada pertanyaan reflektif untuk proyek ini.",
                ], 404);
            }

            // Get answers for these questions
            $allAnswers = [];
            foreach ($questions as $question) {
                $answer = ReflectiveAnswer::where([
                    'mahasiswa_id' => $validated['mahasiswa_id'],
                    'question_id' => $question->id
                ])->first();

                if ($answer) {
                    $allAnswers[] = [
                        'question' => $question->question,
                        'answer' => $answer->answer
                    ];
                }
            }

            if (empty($allAnswers)) {
                return response()->json([
                    'success' => false,
                    'message' => "Mahasiswa belum menjawab pertanyaan reflektif untuk proyek ini.",
                ], 404);
            }

            // Generate summary
            try {
                // Format questions and answers for the prompt
                $qaText = '';
                foreach ($allAnswers as $qa) {
                    $qaText .= "Pertanyaan: {$qa['question']}\n";
                    $qaText .= "Jawaban: {$qa['answer']}\n\n";
                }

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

                // Delete existing summary if forced to regenerate
                if ($existingSummary) {
                    $existingSummary->delete();
                }

                // Save new summary
                $newSummary = reflective_ai::create([
                    'mahasiswa_id' => $validated['mahasiswa_id'],
                    'project_id' => $project->id,
                    'summary' => Str::limit($summary, 65535, '...')
                ]);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'mahasiswa_id' => $mahasiswa->id,
                        'mahasiswa_name' => $mahasiswa->user->name,
                        'mahasiswa_nim' => $mahasiswa->nim,
                        'project_id' => $project->id,
                        'project_name' => $project->project_name,
                        'batch_year' => $project->batch_year,
                        'summary' => $summary,
                        'source' => 'gemini'
                    ]
                ]);
            } catch (\Exception $e) {
                Log::error("Gemini API Error", [
                    'message' => $e->getMessage(),
                    'mahasiswa_name' => $mahasiswa->user->name,
                    'mahasiswa_nim' => $mahasiswa->nim,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => "Gagal menghasilkan ringkasan.",
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error("Kesalahan Umum di getReflectiveSummary", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => "Terjadi kesalahan pada server.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Call Gemini API with error handling
     *
     * @param string $prompt
     * @return string
     * @throws \Exception
     */
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
}
