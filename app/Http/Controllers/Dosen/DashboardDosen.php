<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function profile()
    {
        return Inertia::render('Dosen/Profile');
    }
    

   
}
