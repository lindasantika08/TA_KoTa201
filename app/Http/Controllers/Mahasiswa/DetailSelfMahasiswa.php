<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DetailSelfMahasiswa extends Controller
{
    public function showDetail() {
        return Inertia::render('Mahasiswa/DetailSelfAssessment');
    }
}
