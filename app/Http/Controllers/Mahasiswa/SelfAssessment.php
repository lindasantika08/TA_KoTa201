<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use App\Models\type_criteria;
use App\Models\Assessment;  
use App\Models\Answers;      
use Illuminate\Http\Request;
use Inertia\Inertia;

class SelfAssessment extends Controller
{
    public function assessment() {
        return Inertia::render('Mahasiswa/SelfAssessmentMahasiswa');
    }

    public function getQuestionsByProject(Request $request) {
        try {
            $questions = Assessment::where('nama_proyek', $request->nama_proyek)
                ->get(['id', 'pertanyaan', 'aspek', 'kriteria']);
            return response()->json($questions);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
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
}