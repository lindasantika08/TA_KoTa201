<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\type_criteria;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelfAssessment extends Controller
{
    public function bobot() {
        $data = type_criteria::all();
        return response()->json($data);
    }
    public function assessment() {
        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa');
    }
}
