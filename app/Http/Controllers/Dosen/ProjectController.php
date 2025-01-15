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
        $projects = Assessment::select('tahun_ajaran', 'nama_proyek')
            ->distinct()
            ->get()
            ->toArray();

        return Inertia::render('Dosen/DaftarProyek', [
            'projects' => $projects,
        ]);
    }
}
