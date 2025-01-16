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

    public function getProjectsWithAssessmentsSelf()
    {
        $projects = Assessment::select('tahun_ajaran', 'nama_proyek')
            ->distinct()
            ->get()
            ->toArray();

        return Inertia::render('Dosen/DaftarProyekSelf', [
            'projects' => $projects,
        ]);
    }

    public function getProjectsWithAssessmentsPeer()
    {
        $projects = Assessment::select('tahun_ajaran', 'nama_proyek')
            ->distinct()
            ->get()
            ->toArray();

        return Inertia::render('Dosen/DaftarProyekPeer', [
            'projects' => $projects,
        ]);
    }

    public function getDataSelf()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'selfAssessment');
        })->get();

        return response()->json($projects);
    }

    public function getDataPeer()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'peerAssessment');
        })->get();

        return response()->json($projects);
    }
}
