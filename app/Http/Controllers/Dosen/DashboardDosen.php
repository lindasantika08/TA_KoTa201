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
        $projects = Project::where('status', 'aktif')->get();

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
