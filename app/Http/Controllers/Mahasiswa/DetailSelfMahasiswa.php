<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Answers;


class DetailSelfMahasiswa extends Controller
{
    public function showDetail() {
        return Inertia::render('Mahasiswa/DetailSelfAssessment');
    }

    public function getUserInfo(Request $request)
    {
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

    public function getAnswerSelf() {

        $user = Auth::user();

        $answers = Answers::with(['question'])
        ->where('user_id', $user->id)
        ->get()
        ->groupBy('question.aspek')
        ->map(function ($aspectAnswers, $aspectName) {
            return [
                'aspect' => $aspectName,
                'answers' => $aspectAnswers->map(function ($answer) {
                    return [
                        'question' => $answer->question->pertanyaan,
                        'scale' => $answer->score,
                        'reason' => $answer->answer
                    ];
                })
            ];
        })
        ->values();

        return response()->json([
            'answers' => $answers
        ]);
    }

}