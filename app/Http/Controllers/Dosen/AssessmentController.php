<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AssessmentImport;
use App\Models\Assessment;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AssessmentController extends Controller
{
    public function selfAssessment()
    {
        // Pastikan nama komponen dan folder sesuai dengan struktur di resources/js/Pages/Dosen
        return Inertia::render('Dosen/SelfAssessment');
    }

    public function peerAssessment()
    {
        return Inertia::render('Dosen/PeerAssessment');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        $file = $request->file('file');

        // Membaca file Excel
        $spreadsheet = IOFactory::load($file);

        // Mengambil sheet pertama
        $sheet = $spreadsheet->getActiveSheet();

        // Mendapatkan semua data mulai dari baris kedua
        $data = [];
        foreach ($sheet->getRowIterator(2) as $row) { // Dimulai dari baris 2 untuk mengabaikan header
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            $rowData = [];
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getFormattedValue(); // Ambil nilai sel yang sudah diformat
            }

            // Pastikan ada dua kolom (UUID dan Type)
            if (count($rowData) >= 2) {
                $data[] = [
                    'id' => $rowData[0], // UUID di kolom pertama
                    'type' => $rowData[1], // Type di kolom kedua
                ];
            }
        }

        // Simpan data ke database
        try {
            foreach ($data as $row) {
                Assessment::create([
                    'id' => $row['id'],
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
}
