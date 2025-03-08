<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Major;
use App\Models\Prodi;

class DashboardAdminController extends Controller
{
    public function dashboard()
    {
        $majorsWithProjects = Major::with(['prodis.projects'])
            ->get()
            ->map(function ($major) {
                $activeProjects = 0;
                $inactiveProjects = 0;

                foreach ($major->prodis as $prodi) {
                    $activeProjects += $prodi->projects->where('status', 'active')->count();
                    $inactiveProjects += $prodi->projects->where('status', '!=', 'active')->count();
                }

                return [
                    'id' => $major->id,
                    'name' => $major->major_name,
                    'activeProjects' => $activeProjects,
                    'inactiveProjects' => $inactiveProjects,
                    'totalProjects' => $activeProjects + $inactiveProjects
                ];
            });

        return Inertia::render('Admin/Dashboard', [
            'majorsData' => $majorsWithProjects
        ]);
    }
}
