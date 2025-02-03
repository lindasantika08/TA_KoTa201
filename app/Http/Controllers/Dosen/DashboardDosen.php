<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\project;
use App\Models\Dosen;
use App\Models\User;

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
}
