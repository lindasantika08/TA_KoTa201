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
            ->where('status', 'active')
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
                    ->with('typeCriteria');
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
                    ->with('typeCriteria');
            }])
            ->distinct()
            ->get();

        return Inertia::render('Dosen/DaftarProyekPeer', [
            'projects' => $projects
        ]);
    }

    public function getDataSelf()
    {
        try {
            $projects = Project::where('status', 'Active')
                ->orderBy('created_at', 'desc')
                ->get();
            
            $result = [];
            
            foreach ($projects as $project) {
                // Untuk setiap assessment order yang terkait dengan proyek ini
                $assessmentOrders = Assessment::where('project_id', $project->id)
                    ->select('assessment_order')
                    ->distinct()
                    ->orderBy('assessment_order')
                    ->pluck('assessment_order');
                
                foreach ($assessmentOrders as $order) {
                    $isPublished = Assessment::where('project_id', $project->id)
                        ->where('assessment_order', $order)
                        ->where('type', 'selfAssessment')
                        ->value('is_published');
                    
                    // Jika tidak ada record, anggap sebagai not published
                    $isPublished = $isPublished !== null ? $isPublished : 0;
                    
                    $result[] = [
                        'id' => $project->id,
                        'batch_year' => $project->batch_year,
                        'project_name' => $project->project_name,
                        'assessment_order' => $order,
                        'status' => $project->status,
                        'is_published' => $isPublished,
                        'created_at' => $project->created_at,
                        'unique_key' => $project->id . '-' . $order // tambhain ini buat publish di fe nya gengs
                    ];
                }
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error in getProyekSelfAssessment:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch self assessment projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDataPeer()
    {
        try {
            $projects = Project::where('status', 'Active')
                ->orderBy('created_at', 'desc')
                ->get();
            
            $result = [];
            
            foreach ($projects as $project) {
                $assessmentOrders = Assessment::where('project_id', $project->id)
                    ->where('type', 'peerAssessment')
                    ->select('assessment_order')
                    ->distinct()
                    ->orderBy('assessment_order')
                    ->pluck('assessment_order');
                
                foreach ($assessmentOrders as $order) {
                    $isPublished = Assessment::where('project_id', $project->id)
                        ->where('assessment_order', $order)
                        ->where('type', 'peerAssessment')
                        ->value('is_published');
                    
                    $isPublished = $isPublished !== null ? $isPublished : 0;
                    
                    $result[] = [
                        'id' => $project->id,
                        'batch_year' => $project->batch_year,
                        'project_name' => $project->project_name,
                        'assessment_order' => $order,
                        'status' => $project->status,
                        'is_published' => $isPublished,
                        'created_at' => $project->created_at,
                        'unique_key' => $project->id . '-' . $order
                    ];
                }
            }
            
            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Error in getProyekPeerAssessment:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch peer assessment projects',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function togglePublishAssessment(Request $request)
    {
        try {
            \DB::enableQueryLog();

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
                'type' => 'selfAssessment',
                'assessment_order' => $request->assessment_order
            ])->update([
                'is_published' => $request->is_published ? 1 : 0
            ]);

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

    public function togglePublishAssessmentPeer(Request $request)
    {
        try {
            \DB::enableQueryLog();

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
                'type' => 'peerAssessment',
                'assessment_order' => $request->assessment_order
            ])->update([
                'is_published' => $request->is_published ? 1 : 0
            ]);

            \Log::info('SQL Query:', \DB::getQueryLog());
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
        $tahun_ajaran = $request->tahun_ajaran;
        $nama_proyek = $request->nama_proyek;

        $project = Project::where('batch_year', $tahun_ajaran)
            ->where('project_name', $nama_proyek)
            ->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:Active,NonActive',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $project->status = $request->status;
        $project->save();

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
