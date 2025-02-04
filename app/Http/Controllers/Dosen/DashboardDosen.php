<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\project;
use App\Models\Dosen;
use App\Models\User;
use App\Models\Group;
use App\Models\Answers;

class DashboardDosen extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Dosen/Dashboard');
    }

    public function notifications()
    {
        return Inertia::render('Dosen/Notifications');
    }

    public function dashboardself()
    {
        return Inertia::render('Dosen/AnswerSelf');
    }

    public function dashboardpeer()
    {
        return Inertia::render('Dosen/AnswerPeer');
    }

    public function getActiveProjects(Request $request)
    {
        $projects = Project::with(['major'])
            ->where('status', 'Active')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'semester' => $project->semester,
                    'batch_year' => $project->batch_year,
                    'project_name' => $project->project_name,
                    'major' => $project->major ? $project->major->name : null,
                    'start_date' => $project->start_date ? $project->start_date->format('Y-m-d') : null,
                    'end_date' => $project->end_date ? $project->end_date->format('Y-m-d') : null,
                    'status' => $project->status,
                ];
            });

        return response()->json($projects);
    }

    public function getDosensInSameMajor()
    {
        $dosenData = auth()->user()->dosen;
        $sameMajorDosen = Dosen::where('major_id', $dosenData->major_id)->get();

        return response()->json([
            'data' => $sameMajorDosen
        ]);
    }

    public function getStatistics(Request $request)
    {
        try {
            $batchYear = $request->query('batch_year');
            $projectName = $request->query('project_name');

            // Find the project first
            $project = Project::where('batch_year', $batchYear)
                ->where('project_name', $projectName)
                ->first();

            if (!$project) {
                return response()->json([
                    'error' => 'Project not found',
                    'message' => 'No project found with the given batch year and project name'
                ], 404);
            }

            $projectId = $project->id;

            // Find groups for specific project with mahasiswa loaded
            $groups = Group::where('batch_year', $batchYear)
                ->where('project_id', $projectId)
                ->with('mahasiswa.user')
                ->get();

            $usersAlreadyFilled = Answers::whereHas('question', function ($query) use ($projectId) {
                $query->where('project_id', $projectId);
            })
                ->whereIn('mahasiswa_id', $groups->pluck('mahasiswa_id'))
                ->distinct('mahasiswa_id')
                ->count();

            // Prepare details of users who haven't submitted
            $submissionStatus = $groups->map(function ($item) use ($projectId) {
                $isSubmitted = Answers::whereHas('question', function ($query) use ($projectId) {
                    $query->where('project_id', $projectId);
                })
                    ->where('mahasiswa_id', $item->mahasiswa_id)
                    ->exists();

                return [
                    'index' => $item->id,
                    'mahasiswaName' => optional($item->mahasiswa->user)->name ?? 'Unknown',
                    'status' => $isSubmitted ? 'submitted' : 'unsubmitted'
                ];
            });

            return response()->json([
                'totalKeseluruhan' => $groups->count(),
                'totalSudahMengisi' => $usersAlreadyFilled,
                'submissionStatus' => $submissionStatus
            ]);
        } catch (\Exception $e) {
            Log::error('Statistics Error: ' . $e->getMessage() . ' Trace: ' . $e->getTraceAsString());

            return response()->json([
                'error' => 'Fatal Error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
