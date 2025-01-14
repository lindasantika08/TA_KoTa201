<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\project;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        return response()->json(Project::select('tahun_ajaran', 'nama_proyek')->get());
    }

    public function getProjectsWithAssessments()
    {
        // Mengambil data proyek dengan assessment yang terkait
        $projects = Assessment::select('tahun_ajaran', 'nama_proyek')
            ->distinct()
            ->get()
            ->toArray(); // Pastikan mengirim data dalam bentuk array

        // Mengirim data ke Vue menggunakan Inertia
        return Inertia::render('Dosen/DaftarProyek', [
            'projects' => $projects, // Pastikan data terkirim
        ]);
    }
}
