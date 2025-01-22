<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Kelompok;
use App\Models\Answers;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AnswerController extends Controller
{
    public function answerSelf(Request $request)
    {
        $validated = $request->validate([
            // 'QuestionId' => 'required|uuid',
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        // Log tahun ajaran dan nama proyek
        Log::info('AnswerSelf method called', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);

        return Inertia::render('Dosen/AnswerSelf', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
    }

    public function getQuestionId(Request $request)
    {
        $validated = $request->validate([
            'tahun_ajaran' => 'required|string',
            'nama_proyek' => 'required|string',
        ]);

        // Cari assessment berdasarkan tahun ajaran dan nama proyek
        $assessment = Assessment::where('tahun_ajaran', $validated['tahun_ajaran'])
            ->where('nama_proyek', $validated['nama_proyek'])
            ->first();

        if (!$assessment) {
            return response()->json(['message' => 'Assessment not found'], 404);
        }

        return response()->json([
            'questionId' => $assessment->id,
        ]);
    }

    public function getUserInfoDosen(Request $request)
    {
        // Mendapatkan data user yang sedang login
        $user = Auth::user();

        // Mendapatkan kelompok berdasarkan dosen_id yang sedang login
        $kelompok = Kelompok::where('dosen_id', $user->id)
            ->first();  // Mengambil kelompok pertama yang ditemukan

        // Menyusun informasi user yang ingin dikirimkan
        $userInfo = [
            'nip' => $user->nip,
            'name' => $user->name,
            'class' => '1B',  // Bisa disesuaikan sesuai data yang ada
            'group' => $kelompok ? $kelompok->kelompok : 'Tidak Ditemukan',  // Menampilkan kelompok
            'project' => $kelompok ? $kelompok->nama_proyek : 'Tidak Ditemukan',  // Menampilkan nama proyek dari kelompok
            'date' => now()->format('d F Y')  // Tanggal saat ini
        ];

        return response()->json($userInfo);
    }

    public function getQuestionsByProject(Request $request)
    {
        // Ambil tahun_ajaran dan nama_proyek dari query parameter
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        // Pastikan filter dengan tepat
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
            ->where('assessment.tahun_ajaran', $tahunAjaran)
            ->where('assessment.nama_proyek', $namaProyek)
            ->where('assessment.type', 'selfAssessment')
            ->get();

        return response()->json($assessments);
    }

    public function getListAnswers(Request $request)
    {
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');

        // Mengambil data jawaban dengan join ke tabel assessment untuk mendapatkan pertanyaan
        $answers = Answers::select('answers.*', 'assessment.pertanyaan')  // Memilih semua kolom dari answers dan kolom pertanyaan dari assessment
            ->join('assessment', 'answers.question_id', '=', 'assessment.id')  // Melakukan join dengan tabel assessment berdasarkan question_id
            ->where('assessment.tahun_ajaran', $tahunAjaran)  // Menyaring berdasarkan tahun_ajaran
            ->where('assessment.nama_proyek', $namaProyek)  // Menyaring berdasarkan nama_proyek
            ->with('user')  // Memuat relasi user
            ->get();

        // Mengembalikan data dalam format JSON
        return response()->json($answers);
    }

    public function getListAnswersView()
    {
        return Inertia::render('Dosen/AnswersSelfAssessment');
    }
}
