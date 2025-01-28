<?php

namespace App\Http\Controllers\Dosen;

use App\Exports\AssessmentExport;
use App\Exports\AssessmentSheet;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Assessment;
use App\Models\type_criteria;
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
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');

        $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        $assessments = Assessment::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->get();

        if ($assessments->isEmpty()) {
            return Excel::download(new AssessmentExport($tahunAjaran, $namaProyek), 'template-assessment.xlsx');
        } else {
            return Excel::download(new AssessmentExport($tahunAjaran, $namaProyek), 'template-assessment.xlsx');
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

            $sheet1 = $spreadsheet->getSheet(0);
            $assessmentData = [];
            foreach ($sheet1->getRowIterator(2) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if (!empty($rowData[1])) {
                    $assessmentData[] = [
                        'tahun_ajaran' => trim($rowData[1]),
                        'nama_proyek' => trim($rowData[2]),
                        'type' => trim($rowData[3]),
                        'pertanyaan' => trim($rowData[4]),
                        'aspek' => trim($rowData[5]),
                        'kriteria' => trim($rowData[6]),
                    ];
                }
            }

            $sheet2 = $spreadsheet->getSheet(1);
            $typeCriteriaData = [];
            foreach ($sheet2->getRowIterator(3) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();
                }

                if (!empty($rowData[1])) {
                    $typeCriteriaData[] = [
                        'aspek' => trim($rowData[1]),
                        'kriteria' => trim($rowData[2]),
                        'bobot_1' => trim($rowData[3]),
                        'bobot_2' => trim($rowData[4]),
                        'bobot_3' => trim($rowData[5]),
                        'bobot_4' => trim($rowData[6]),
                        'bobot_5' => trim($rowData[7]),
                    ];
                }
            }

            foreach ($typeCriteriaData as $row) {
                \App\Models\type_criteria::updateOrCreate(
                    ['aspek' => $row['aspek'], 'kriteria' => $row['kriteria']],
                    $row
                );
            }

            foreach ($assessmentData as $row) {
                \App\Models\Assessment::updateOrCreate(
                    [
                        'pertanyaan' => $row['pertanyaan'],
                        'aspek' => $row['aspek'],
                        'kriteria' => $row['kriteria']
                    ],
                    $row
                );
            }

            DB::commit();
            return response()->json(['message' => 'Data berhasil diimpor']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import Error: ' . $e->getMessage());
            throw $e;
        }
    }


    public function getData()
    {
        $assessments = Assessment::all(); 
        return response()->json($assessments); 
    }

    public function getAssessmentsWithBobotSelf(Request $request)
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
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return Inertia::render('Dosen/SelfAssessment', [
            'assessments' => $assessments,
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
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
