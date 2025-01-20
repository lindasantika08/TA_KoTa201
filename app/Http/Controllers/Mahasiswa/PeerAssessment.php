<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Assessment;
use App\Models\AnswersPeer;
use Illuminate\Support\Facades\Log;

class PeerAssessment extends Controller
{
    public function assessment()
    {
        return Inertia::render('Mahasiswa/PeerAssessmentMahasiswa');
    }

    public function getQuestionsByProject(Request $request)
    {
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
            ->where('assessment.type', 'peerAssessment')
            ->get();

        return response()->json($assessments);
    }

    public function AnswersPeer(Request $request)
    {
        try {
            $userExists = \App\Models\User::find($request->user_id);
            $peerExists = \App\Models\User::find($request->peer_id);
            $questionExists = \App\Models\Assessment::find($request->question_id);
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'required|string|exists:users,id',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            // Coba buat record baru
            $answer = AnswersPeer::create($validated);


            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function getSavedAnswer(Request $request)
    {
        $questionId = $request->query('question_id');
        $peerId = $request->query('peer_id');

        $answer = AnswersPeer::where('question_id', $questionId)
            ->where('peer_id', $peerId)
            ->first();

        if ($answer) {
            return response()->json($answer);
        }

        return response()->json(['message' => 'Answer not found'], 404);
    }
}
