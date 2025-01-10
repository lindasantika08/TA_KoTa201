<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardDosen extends Controller
{
    public function dashboard() {
        return Inertia::render('Dosen/Dashboard');
    }
}
