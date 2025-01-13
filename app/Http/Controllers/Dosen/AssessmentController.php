<?php

namespace App\Http\Controllers\Dosen;

use App\Exports\AssessmentExport;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssessmentImport;
use App\Models\Assessment;
use App\Models\type_criteria;
use PhpOffice\PhpSpreadsheet\IOFactory;


class AssessmentController extends Controller
{
    public function self()
    {
        return Inertia::render('Dosen/SelfAssessment');
    }

    public function peer()
    {
        return Inertia::render('Dosen/PeerAssessment');
    }

    public function exportExcel()
    {
        return Excel::download(new AssessmentExport, 'self-assessment.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');

        // Membaca file Excel
        $spreadsheet = IOFactory::load($file);

        // Ambil data dari Sheet 1 (Assessment)
        $sheet1 = $spreadsheet->getSheet(0); // Sheet pertama
        $assessmentData = [];
        foreach ($sheet1->getRowIterator(2) as $row) { // Dimulai dari baris 2 untuk mengabaikan header
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getFormattedValue();
            }

            // Pastikan ada 5 kolom (id, type, pertanyaan, aspek, kriteria)
            if (count($rowData) >= 5) {
                $assessmentData[] = [
                    'pertanyaan' => $rowData[2],
                    'aspek' => $rowData[3],
                    'kriteria' => $rowData[4],
                    'type' => $rowData[1],
                ];
            }
        }

        // Ambil data dari Sheet 2 (Type Criteria)
        $sheet2 = $spreadsheet->getSheet(1); // Sheet kedua
        $typeCriteriaData = [];
        foreach ($sheet2->getRowIterator(3) as $row) { // Dimulai dari baris 3 untuk mengabaikan 2 baris header
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getFormattedValue();
            }

            // Pastikan ada 7 kolom (aspek, kriteria, bobot_1, bobot_2, bobot_3, bobot_4, bobot_5)
            if (count($rowData) >= 7) {
                $typeCriteriaData[] = [
                    'aspek' => $rowData[1],
                    'kriteria' => $rowData[2],
                    'bobot_1' => $rowData[3],
                    'bobot_2' => $rowData[4],
                    'bobot_3' => $rowData[5],
                    'bobot_4' => $rowData[6],
                    'bobot_5' => $rowData[7],
                ];
            }
        }

        // Simpan data untuk type_criteria terlebih dahulu
        try {
            // Simpan atau perbarui data ke tabel type_criteria
            foreach ($typeCriteriaData as $row) {
                type_criteria::updateOrCreate(
                    [
                        'aspek' => $row['aspek'],
                        'kriteria' => $row['kriteria']
                    ],
                    [
                        'bobot_1' => $row['bobot_1'],
                        'bobot_2' => $row['bobot_2'],
                        'bobot_3' => $row['bobot_3'],
                        'bobot_4' => $row['bobot_4'],
                        'bobot_5' => $row['bobot_5'],
                    ]
                );
            }

            // Hapus data assessment lama yang sudah ada berdasarkan kombinasi pertanyaan, aspek, dan kriteria
            foreach ($assessmentData as $row) {
                Assessment::where('pertanyaan', $row['pertanyaan'])
                    ->where('aspek', $row['aspek'])
                    ->where('kriteria', $row['kriteria'])
                    ->delete();
            }

            // Simpan data baru ke tabel assessment
            foreach ($assessmentData as $row) {
                Assessment::create([
                    'pertanyaan' => $row['pertanyaan'],
                    'aspek' => $row['aspek'],
                    'kriteria' => $row['kriteria'],
                    'type' => $row['type'],
                ]);
            }

            return response()->json(['message' => 'Data berhasil diimpor'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Terjadi kesalahan saat mengimpor data', 'details' => $e->getMessage()], 500);
        }
    }




    public function getData()
    {
        $assessments = Assessment::all(); // Ambil semua data assessment
        return response()->json($assessments); // Kembalikan sebagai response JSON
    }

    public function getAssessmentsWithBobot()
    {
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
            ->get();

        return response()->json($assessments);
    }
}
