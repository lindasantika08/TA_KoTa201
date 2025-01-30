<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\project;
use App\Models\Assessment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Validator;

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
        })
            ->get();

        return response()->json($projects);
    }

    public function getDataPeer()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.tahun_ajaran', 'assessment.tahun_ajaran')
                ->whereColumn('project.nama_proyek', 'assessment.nama_proyek')
                ->where('assessment.type', 'peerAssessment');
        })

            ->get();


        return response()->json($projects);
    }

    public function changeStatus(Request $request)
    {
        // Ambil data dari request
        $tahun_ajaran = $request->tahun_ajaran;
        $nama_proyek = $request->nama_proyek;

        // Cari proyek berdasarkan tahun ajaran dan nama proyek
        $project = Project::where('batch_year', $tahun_ajaran)  // Pastikan nama kolom sesuai
            ->where('project_name', $nama_proyek)  // Pastikan nama kolom sesuai
            ->first();

        // Jika proyek tidak ditemukan
        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Validasi status
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,NonActive',  // Pastikan status yang diterima adalah 'aktif' atau 'nonaktif'
        ]);

        // Jika validasi gagal
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Update status proyek
        $project->status = $request->status; // Mengubah status proyek
        $project->save(); // Simpan perubahan ke database

        // Kembalikan respons sukses
        return response()->json([
            'message' => 'Project status updated successfully',
            'project' => $project,
        ]);
    }


    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'semester' => 'required|string',
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
            'jurusan' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'status' => 'required|in:aktif,nonaktif',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $project = Project::create($request->all());

        return response()->json([
            'message' => 'Project created successfully',
            'project' => $project,
        ], 201);
    }
}
