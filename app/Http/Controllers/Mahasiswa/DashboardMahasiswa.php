<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardMahasiswa extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Mahasiswa/Dashboard');
    }

    public function profile()
    {
        return Inertia::render('Mahasiswa/Profile');
    }
}
