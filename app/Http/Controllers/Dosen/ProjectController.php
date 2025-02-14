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
        $projects = Project::select('batch_year', 'semester', 'project_name')
            ->where('status', 'active') // Menambahkan filter hanya untuk project yang aktif
            ->get();

        return response()->json($projects);
    }


    public function getProjectsWithAssessmentsSelf()
    {
        $projects = Project::whereHas('assessments', function ($query) {
            $query->where('type', 'selfAssessment');
        })
            ->select('id', 'batch_year', 'project_name', 'status', 'created_at')
            ->with(['assessments' => function ($query) {
                $query->where('type', 'selfAssessment')
                    ->with('typeCriteria'); // Load relasi typeCriteria jika diperlukan
            }])
            ->distinct()
            ->get();

        return Inertia::render('Dosen/DaftarProyekSelf', [
            'projects' => $projects
        ]);
    }

    public function getProjectsWithAssessmentsPeer()
    {
        $projects = Project::whereHas('assessments', function ($query) {
            $query->where('type', 'peerAssessment');
        })
            ->select('id', 'batch_year', 'project_name', 'status', 'created_at')
            ->with(['assessments' => function ($query) {
                $query->where('type', 'peerAssessment')
                    ->with('typeCriteria'); // Load relasi typeCriteria jika diperlukan
            }])
            ->distinct()
            ->get();

        return Inertia::render('Dosen/DaftarProyekPeer', [
            'projects' => $projects
        ]);
    }

    public function getDataSelf()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.id', 'assessment.project_id')
                ->where('assessment.type', 'selfAssessment');
        })
            ->select([
                'project.id',
                'project.batch_year',
                'project.project_name',
                'project.status',
                'project.created_at'
            ])
            ->selectRaw('(SELECT is_published FROM assessment WHERE project_id = project.id AND type = "selfAssessment" LIMIT 1) as is_published')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projects);
    }

    public function getDataPeer()
    {
        $projects = Project::whereExists(function ($query) {
            $query->from('assessment')
                ->whereColumn('project.id', 'assessment.project_id')
                ->where('assessment.type', 'peerAssessment');
        })
            ->select([
                'id',
                'batch_year',
                'project_name',
                'status',
                'created_at'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($projects);
    }

    public function togglePublishAssessment(Request $request)
{
    try {
        \DB::enableQueryLog(); // Untuk debug

        $project = Project::where('batch_year', $request->batch_year)
                         ->where('project_name', $request->project_name)
                         ->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project not found'
            ], 404);
        }

        $updated = Assessment::where([
            'project_id' => $project->id,
            'type' => 'selfAssessment'
        ])->update([
            'is_published' => $request->is_published ? 1 : 0
        ]);

        \Log::info('SQL Query:', \DB::getQueryLog()); // Untuk debug
        \Log::info('Update result:', ['updated' => $updated]);

        return response()->json([
            'success' => true,
            'message' => 'Assessment publish status updated successfully',
            'data' => [
                'is_published' => $request->is_published,
                'updated_count' => $updated
            ]
        ]);
    } catch (\Exception $e) {
        \Log::error('Toggle publish error:', ['error' => $e->getMessage()]);
        return response()->json([
            'success' => false,
            'message' => 'Failed to update publish status: ' . $e->getMessage()
        ], 500);
    }
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
