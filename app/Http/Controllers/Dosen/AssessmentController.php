<?php

namespace App\Http\Controllers\Dosen;

use App\Exports\AssessmentExport;
use App\Exports\AssessmentSheet;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Assessment;
use App\Models\Project;
use App\Models\TypeCriteria;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


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
        ]);

        try {
            DB::beginTransaction();

            $spreadsheet = IOFactory::load($request->file('file'));

            // Import Type Criteria dari sheet kedua
            $sheet2 = $spreadsheet->getSheetByName('Type Criteria');
            $highestRow2 = $sheet2->getHighestRow();

            // Mulai dari baris 3 karena ada 2 baris header
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

            // Import Assessment dari sheet pertama
            $sheet1 = $spreadsheet->getSheet(0);
            $highestRow1 = $sheet1->getHighestRow();

            // Mulai dari baris 2 karena ada 1 baris header
            for ($row = 2; $row <= $highestRow1; $row++) {
                $batchYear = trim($sheet1->getCellByColumnAndRow(2, $row)->getValue());
                $projectName = trim($sheet1->getCellByColumnAndRow(3, $row)->getValue());
                $type = trim($sheet1->getCellByColumnAndRow(4, $row)->getValue());
                $question = trim($sheet1->getCellByColumnAndRow(5, $row)->getValue());
                $aspect = trim($sheet1->getCellByColumnAndRow(6, $row)->getValue());
                $criteria = trim($sheet1->getCellByColumnAndRow(7, $row)->getValue());

                if (!empty($batchYear) && !empty($projectName)) {
                    // Cari atau buat Project
                    $project = Project::firstOrCreate(
                        [
                            'batch_year' => $batchYear,
                            'project_name' => $projectName
                        ]
                    );

                    // Cari TypeCriteria berdasarkan aspect dan criteria
                    $typeCriteria = TypeCriteria::where('aspect', $aspect)
                        ->where('criteria', $criteria)
                        ->first();

                    if ($typeCriteria) {
                        // Check if the combination of batch_year and project_name exists in the Assessment table
                        $assessment = Assessment::where('batch_year', $batchYear)
                            ->where('project_id', $project->id)
                            ->where('question', $question)
                            ->where('criteria_id', $typeCriteria->id)
                            ->first();

                        if ($assessment) {
                            // If exists, update the record
                            $assessment->update([
                                'type' => $type
                            ]);
                        } else {
                            // If not exists, create a new record
                            Assessment::create([
                                'batch_year' => $batchYear,
                                'project_id' => $project->id,
                                'type' => $type,
                                'question' => $question,
                                'criteria_id' => $typeCriteria->id
                            ]);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => 'Data berhasil diimpor']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Error: ' . $e->getMessage());
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
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

        $assessments = Assessment::with('typeCriteria') // Simplified eager loading
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.question',
                'assessment.criteria_id',
                'assessment.batch_year',
                'assessment.project_id'
            )
            ->join('project', 'assessment.project_id', '=', 'project.id')
            ->when($batchYear, function ($query, $batchYear) {
                $query->where('assessment.batch_year', $batchYear);
            })
            ->when($projectName, function ($query, $projectName) {
                $query->where('project.project_name', $projectName);
            })
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return Inertia::render('Dosen/SelfAssessment', [
            'assessments' => $assessments,
            'batchYear' => $batchYear,
            'projectName' => $projectName
        ]);
    }

    public function getAssessmentsWithBobotPeer(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        $assessments = Assessment::join('type_criteria', function ($join) {
            $join->on('assessment.aspek', '=', 'type_criteria.aspek')
                ->on('assessment.kriteria', '=', 'type_criteria.kriteria');
        })
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.pertanyaan',
                'assessment.aspek',
                'assessment.kriteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('assessment.tahun_ajaran', $tahunAjaran);
            })
            ->when($namaProyek, function ($query, $namaProyek) {
                $query->where('assessment.nama_proyek', $namaProyek);
            })
            ->where('assessment.type', 'peerAssessment')
            ->get();

        return Inertia::render('Dosen/PeerAssessment', [
            'assessments' => $assessments,
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
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
}
