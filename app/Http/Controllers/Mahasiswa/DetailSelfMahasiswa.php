<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Answers;
use App\Models\Mahasiswa;
use App\Models\Group;


class DetailSelfMahasiswa extends Controller
{
    public function showDetail(Request $request)
    {
        $batchYear = $request->input('batch_year');
        $projectName = $request->input('project_name');

        return Inertia::render('Mahasiswa/DetailSelfAssessment', [
            'batchYear' => $batchYear,
            'projectName' => $projectName
        ]);
    }
    public function getUserInfo(Request $request)
    {
        $batch_year = $request->query('batch_year');
        $project_name = $request->query('project_name');

        $user = Auth::user();
        $mahasiswa = Mahasiswa::with(['classRoom', 'group' => function ($query) use ($batch_year, $project_name) {
            $query->whereHas('project', function ($q) use ($project_name, $batch_year) {
                $q->where('project_name', $project_name)
                    ->where('batch_year', $batch_year);
            });
        }])->where('user_id', $user->id)->first();

        $group = $mahasiswa->group()
            ->whereHas('project', function ($query) use ($project_name, $batch_year) {
                $query->where('project_name', $project_name)
                    ->where('batch_year', $batch_year);
            })
            ->first();

        if (!$mahasiswa || !$group) {
            return response()->json([
                'error' => 'Data not found'
            ], 404);
        }

        $userInfo = [
            'nim' => $mahasiswa->nim,
            'name' => $user->name,
            'class' => $mahasiswa->classRoom ? $mahasiswa->classRoom->name : '',
            'group' => $group->group,
            'project' => $group->project->project_name,
            'date' => now()->format('d F Y')
        ];

        return response()->json($userInfo);
    }

    public function getAnswerSelf(Request $request)
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

        $answers = Answers::with(['question'])
            ->where('mahasiswa_id', $mahasiswa->id)
            ->whereHas('question', function ($query) use ($group) {
                $query->where('project_id', $group->project_id);
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
