<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\project;
use App\Models\Prodi;
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
            'prodi_id' => 'required|exists:prodi,id',  // Changed from major_id
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $project = Project::create([
                'semester' => $validatedData['semester'],
                'batch_year' => $validatedData['batch_year'],
                'project_name' => $validatedData['project_name'],
                'prodi_id' => $validatedData['prodi_id'],  // Changed from major_id
                'start_date' => $validatedData['start_date'],
                'end_date' => $validatedData['end_date'],
                'status' => $request->input('status', 'Active'),
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
            $projects = Project::with('prodi')->get(); // Eager load the 'major' relationship

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

    public function getProdisByMajor(Request $request)
    {
        try {
            $user = $request->user();

            // Check if user is a dosen
            if (!$user->isDosen()) {
                return response()->json([
                    'error' => 'Unauthorized. User is not a dosen.',
                ], 403);
            }

            // Get major_id through dosen relationship
            $majorId = $user->dosen->major_id;

            if (!$majorId) {
                return response()->json([
                    'error' => 'Major ID not found for this dosen.',
                ], 404);
            }

            $prodis = Prodi::where('major_id', $majorId)
                ->select('id', 'prodi_name')
                ->get();

            return response()->json($prodis);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Terjadi kesalahan saat mengambil data prodi.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
