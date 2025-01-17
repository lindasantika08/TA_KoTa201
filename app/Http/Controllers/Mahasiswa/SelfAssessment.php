<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\type_criteria;
use App\Models\Assessment;  
use App\Models\Answers;    
use App\Models\project;  
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelfAssessment extends Controller
{
    public function assessment() {
        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa');
    }

    public function getQuestionsByProject(Request $request) {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        $assessments = Assessment::join('type_criteria', function ($join) {
            $join->on('assessment.aspek', '=', 'type_criteria.aspek')
                ->on('assessment.kriteria', '=', 'type_criteria.kriteria');
        })
            ->select(
                'assessment.id',
                'assessment.type',
                'assessment.pertanyaan',
                'assessment.aspek',
                'assessment.kriteria',
                'type_criteria.bobot_1',
                'type_criteria.bobot_2',
                'type_criteria.bobot_3',
                'type_criteria.bobot_4',
                'type_criteria.bobot_5'
            )
            ->when($tahunAjaran, function ($query, $tahunAjaran) {
                $query->where('assessment.tahun_ajaran', $tahunAjaran);
            })
            ->when($namaProyek, function ($query, $namaProyek) {
                $query->where('assessment.nama_proyek', $namaProyek);
            })
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return response()->json($assessments);
    }
    

    public function getFilteredBobot(Request $request) {
        try {
            $bobot = type_criteria::where('aspek', $request->aspek)
                ->where('kriteria', $request->kriteria)
                ->get(['bobot_1', 'bobot_2', 'bobot_3', 'bobot_4', 'bobot_5'])
                ->first();
            return response()->json($bobot ?? []);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function saveAnswer(Request $request) {
        try {
            $answer = Answer::create([
                'question_id' => $request->question_id,
                'answer' => $request->answer,
                'user_id' => auth()->id(),
            ]);
            return response()->json(['message' => 'Answer saved successfully', 'data' => $answer]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserInfo(Request $request) {
        $user = Auth::user();

        $userInfo = [
            'nim' => $user->nim,
            'name' => $user->name,
            'class' => '1B',
            'group' => '1 (Satu)',
            'project' => 'Aplikasi Perkantoran',
            'date' => now()->format('d F Y')
        ];

        return response()->json($userInfo);
    }
}