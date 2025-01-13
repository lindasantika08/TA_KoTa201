<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\SelfExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class PeerAssessmentController extends Controller
{
    public function index() {
        return Inertia::render('Dosen/PeerAssessment');
    }

    public function exportExcel() {
        return Excel::download('new PeerExport', 'peer-assessment.xlsx');
    }
}
