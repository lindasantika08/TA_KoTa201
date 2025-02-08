<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\project;
use App\Models\Major;
use Inertia\Inertia;

class KelolaProyekController extends Controller
{
    public function KelolaProyekView()
    {
        return Inertia::render('Dosen/KelolaProyek');
    }

    public function AddProyek(Request $request)
    {
        $validatedData = $request->validate([
            'semester' => 'required|string|max:10',
            'batch_year' => 'required|string|max:10',
            'project_name' => 'required|string|max:255|unique:project,project_name',
            'major_id' => 'required|exists:major,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $project = Project::create([
                'semester' => $validatedData['semester'],
                'batch_year' => $validatedData['batch_year'],
                'project_name' => $validatedData['project_name'],
                'major_id' => $validatedData['major_id'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'status' => $request->input('status', 'Active'), // Default ke "aktif" jika tidak dikirim
            ]);

            return response()->json([
                'message' => 'Proyek berhasil ditambahkan!',
                'data' => $project
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat menambahkan proyek.',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function getProjects()
    {
        try {
            $projects = Project::with('major')->get(); // Eager load the 'major' relationship

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getMajors()
    {
        try {
            $majors = Major::all(); // Pastikan ini benar-benar mengambil data
            return response()->json($majors);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
