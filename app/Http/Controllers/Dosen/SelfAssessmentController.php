<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Exports\SelfExport;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class SelfAssessmentController extends Controller
{

    public function index()
    {
        return Inertia::render('Dosen/SelfAssessment');
    }

    public function exportExcel() {
        return Excel::download(new SelfExport, 'self-assessment.xlsx');
    }

    public function import(Request $request) 
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new SelfImport, $request->file('file'));
            
            return redirect()->back()->with('success', 'Data imported successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    
}
