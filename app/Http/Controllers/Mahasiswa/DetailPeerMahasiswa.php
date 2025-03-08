<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\models\Mahasiswa;
use App\models\AnswersPeer;
use App\models\Group;
use App\models\Project;
use App\models\Assessment;
use Illuminate\Support\Facades\Auth;

class DetailPeerMahasiswa extends Controller
{
    public function showDetail(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');
        return Inertia::render('Mahasiswa/DetailPeerAssessment', [
            'batchYear' => $batchYear,
            'projectName' => $projectName
        ]);
    }

    public function getAnswerPeer(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');
        $user = Auth::user();

        // Get the mahasiswa record
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        // Get the group for this specific project and batch year
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

        // Get peer assessments
        $answers = AnswersPeer::with(['mahasiswa', 'peer', 'question.typeCriteria'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('question', function ($query) use ($group) {
                $query->where('project_id', $group->project_id);
            })
            ->get()
            ->map(function ($answer) {
                return [
                    'aspect' => $answer->question->typeCriteria->aspect ?? 'Unknown Aspect',
                    'question' => $answer->question->question,
                    'scale' => $answer->score,
                    'reason' => $answer->answer,
                    'peer_name' => $answer->peer->user->name,
                    'peer_nim' => $answer->peer->nim
                ];
            });

        return response()->json([
            'answers' => $answers
        ]);
    }
}
