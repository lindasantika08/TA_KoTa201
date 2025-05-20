<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Mahasiswa;
use App\Models\AnswersPeer;
use App\Models\Group;
use App\Models\Project;
use App\Models\Assessment;
use Illuminate\Support\Facades\Auth;

class DetailPeerMahasiswa extends Controller
{
    public function showDetail(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');
        $assessmentOrder = $request->input('assessment_order');

        return Inertia::render('Mahasiswa/DetailPeerAssessment', [
            'batchYear' => $batchYear,
            'projectName' => $projectName,
            'assessmentOrder' => $assessmentOrder,
        ]);
    }

    public function getAnswerPeer(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');
        $assessment_order = $request->query('assessment_order');

        $user = Auth::user();

        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        $group = Group::whereHas('project', function ($query) use ($project_name, $batch_year) {
            $query->where('project_name', $project_name)
                ->where('batch_year', $batch_year);
        })
            ->where('mahasiswa_id', $mahasiswa->id)
            ->first();

        if (!$group) {
            return response()->json([
                'error' => 'Group not found'
            ], 404);
        }

        $answers = AnswersPeer::with(['mahasiswa', 'peer', 'question'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('question', function ($query) use ($group, $assessment_order) {
                $query->where('project_id', $group->project_id)
                    ->where('assessment_order', $assessment_order);
            })
            ->get()
            ->groupBy('question.aspek')
            ->map(function ($aspectAnswers, $aspectName) {
                return [
                    'aspect' => $aspectName,
                    'answers' => $aspectAnswers->map(function ($answer) {
                        return [
                            'question' => $answer->question->question,
                            'scale' => $answer->score,
                            'reason' => $answer->answer,
                            'peer_name' => $answer->peer->user->name,
                            'peer_nim' => $answer->peer->nim
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
