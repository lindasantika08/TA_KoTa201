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
        // Log request data
        \Log::info('=== Start AnswersPeer Request ===');
        \Log::info('Request Data:', $request->all());

        try {
            // Cek keberadaan data sebelum validasi
            \Log::info('Checking User:', ['user_id' => $request->user_id]);
            $userExists = \App\Models\User::find($request->user_id);
            \Log::info('User exists:', ['exists' => (bool)$userExists]);

            \Log::info('Checking Peer:', ['peer_id' => $request->peer_id]);
            $peerExists = \App\Models\User::find($request->peer_id);
            \Log::info('Peer exists:', ['exists' => (bool)$peerExists]);

            \Log::info('Checking Question:', ['question_id' => $request->question_id]);
            $questionExists = \App\Models\Assessment::find($request->question_id);
            \Log::info('Question exists:', ['exists' => (bool)$questionExists]);

            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'required|string|exists:users,id',
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            \Log::info('Validated Data:', $validated);

            // Coba buat record baru
            \Log::info('Attempting to create record');
            $answer = AnswersPeer::create($validated);
            \Log::info('Record created successfully:', $answer->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Jawaban peer berhasil disimpan.',
                'data' => $answer,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation Error:', [
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error in AnswersPeer:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan jawaban peer.',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }
}
