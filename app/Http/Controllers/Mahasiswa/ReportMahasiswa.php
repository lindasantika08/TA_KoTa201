<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportMahasiswa extends Controller
{
    public function reportMahasiswa() {
        
        return Inertia::render('Mahasiswa/ReportMahasiswa');
    }
}
