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
    public function PeerAssessment(Request $request)
    {
        return Inertia::render('Mahasiswa/PeerAssessmentMahasiswa', [
            'tahun_ajaran' => $request->tahun_ajaran,
            'proyek' => $request->proyek
        ]);
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
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'required|string|exists:users,id',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $validated['peer_id'],
                'question_id' => $validated['question_id'],
            ])->first();

            if ($existingAnswer) {
                $existingAnswer->update([
                    'answer' => $validated['answer'],
                    'score' => $validated['score'],
                    'status' => $validated['status']
                ]);
                $answer = $existingAnswer;
            } else {
                $answer = AnswersPeer::create($validated);
            }

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function answeredPeers(Request $request)
    {
        try {
            $user = $request->user();

            $answeredPeerIds = AnswersPeer::where('user_id', $user->id)
                ->select('peer_id')
                ->groupBy('peer_id')
                ->havingRaw('COUNT(DISTINCT question_id) = ?', [Assessment::count()])
                ->pluck('peer_id');

            return response()->json($answeredPeerIds);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data peer yang sudah dinilai'
            ], 500);
        }
    }

    public function getExistingPeerAnswers(Request $request) {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'peer_id' => 'required|exists:users,id',
            'question_id' => 'sometimes|exists:assessment,id'
        ]);

        $query = AnswersPeer::where('user_id', $validated['user_id'])
            ->where('peer_id', $validated['peer_id']);

        if (isset($validated['question_id'])) {
            $query->where('question_id', $validated['question_id']);
        }

        $existingAnswers = $query->get();

        return response()->json($existingAnswers);
    }
}
