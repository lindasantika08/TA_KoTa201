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
        // Validasi input request
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        // Mendapatkan file yang diupload
        $file = $request->file('file');

        try {
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
                if (count($rowData) >= 6) {
                    $assessmentData[] = [
                        'tahun_ajaran' => $rowData[1],
                        'nama_proyek' => $rowData[2],
                        'type' => $rowData[3],
                        'pertanyaan' => $rowData[4],
                        'aspek' => $rowData[5],
                        'kriteria' => $rowData[6],

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
                    'tahun_ajaran' => $row['tahun_ajaran'],
                    'nama_proyek' => $row['nama_proyek'],
                    'type' => $row['type'],
                    'pertanyaan' => $row['pertanyaan'],
                    'aspek' => $row['aspek'],
                    'kriteria' => $row['kriteria'],

                ]);
            }

            return response()->json(['message' => 'Data berhasil diimpor'], 200);
        } catch (\Exception $e) {
            // Jika ada error, tangkap dan tampilkan pesan error
            return response()->json(['error' => 'Terjadi kesalahan saat mengimpor data', 'details' => $e->getMessage()], 500);
        }
    }

    public function getData()
    {
        $assessments = Assessment::all(); // Ambil semua data assessment
        return response()->json($assessments); // Kembalikan sebagai response JSON
    }

    public function getAssessmentsWithBobot(Request $request) {
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
            ->get();

        return Inertia::render('Dosen/SelfAssessment', [
            'assessments' => $assessments,
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek
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
