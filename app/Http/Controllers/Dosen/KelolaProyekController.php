<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\project;
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
            'tahun_ajaran' => 'required|string|max:10',
            'nama_proyek' => 'required|string|max:255|unique:project,nama_proyek',
            'jurusan' => 'required|string|max:100',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        try {
            $project = Project::create($validatedData);
            return response()->json(['message' => 'Proyek berhasil ditambahkan!', 'data' => $project]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProjects()
    {
        try {
            $projects = Project::all();

            return response()->json($projects);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
