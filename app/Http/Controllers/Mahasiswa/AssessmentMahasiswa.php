<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Models\project;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AssessmentMahasiswa extends Controller
{
    public function selfAssessment() {
        return Inertia::render('Mahasiswa/SelfAssessment');
    }

    public function peerAssessment() {

    }

    public function getDataSelf() {
        $data = project::all();
        // dd($data);
        return response()->json($data);
    }
}
