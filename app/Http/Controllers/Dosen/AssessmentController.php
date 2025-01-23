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
        // Ambil pilihan tahun_ajaran dan nama_proyek
        $tahunAjaran = $request->input('tahun_ajaran');
        $namaProyek = $request->input('nama_proyek');

        // Validasi input
        $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        // Cek apakah ada data di assessment dengan tahun_ajaran dan nama_proyek
        $assessments = Assessment::where('tahun_ajaran', $tahunAjaran)
            ->where('nama_proyek', $namaProyek)
            ->get();

        // Kirim data ke AssessmentExport
        if ($assessments->isEmpty()) {
            // Tidak ada data, kirim template kosong
            return Excel::download(new AssessmentExport($tahunAjaran, $namaProyek), 'template-assessment.xlsx');
        } else {
            // Ada data, kirim template dengan data
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

            // Process Sheet 1 (Assessment)
            $sheet1 = $spreadsheet->getSheet(0);
            $assessmentData = [];
            foreach ($sheet1->getRowIterator(2) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();  // Changed from getFormattedValue()
                }

                if (!empty($rowData[1])) {  // Check if row has data
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

            // Process Sheet 2 (Type Criteria)
            $sheet2 = $spreadsheet->getSheet(1);
            $typeCriteriaData = [];
            foreach ($sheet2->getRowIterator(3) as $row) {
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);
                $rowData = [];
                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getValue();  // Changed from getFormattedValue()
                }

                if (!empty($rowData[1])) {  // Check if row has data
                    $typeCriteriaData[] = [
                        'aspek' => trim($rowData[1]),
                        'kriteria' => trim($rowData[2]),
                        'bobot_1' => floatval($rowData[3]),
                        'bobot_2' => floatval($rowData[4]),
                        'bobot_3' => floatval($rowData[5]),
                        'bobot_4' => floatval($rowData[6]),
                        'bobot_5' => floatval($rowData[7]),
                    ];
                }
            }

            // Save Type Criteria
            foreach ($typeCriteriaData as $row) {
                \App\Models\type_criteria::updateOrCreate(
                    ['aspek' => $row['aspek'], 'kriteria' => $row['kriteria']],
                    $row
                );
            }

            // Save Assessment
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
            throw $e;  // Let Laravel handle the error response
        }
    }


    public function getData()
    {
        $assessments = Assessment::all(); // Ambil semua data assessment
        return response()->json($assessments); // Kembalikan sebagai response JSON
    }

    public function getAssessmentsWithBobotSelf(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        // Menggunakan join untuk mengambil data dari tabel assessment dan type_criteria
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

        // Menggunakan join untuk mengambil data dari tabel assessment dan type_criteria
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
        // Ambil data tahun ajaran dan nama proyek dari database
        $tahunAjaranList = Assessment::distinct()->pluck('tahun_ajaran');
        $namaProyekList = Assessment::distinct()->pluck('nama_proyek');

        return Inertia::render('Dosen/CreateAssessment', [
            'tahunAjaranList' => $tahunAjaranList,
            'namaProyekList' => $namaProyekList,
        ]);
    }
}
