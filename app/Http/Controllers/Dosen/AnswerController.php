<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Kelompok;
use App\Models\Answers;
use App\Models\User;
use App\Models\AnswersPeer;
use App\Models\Assessment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

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

    public function answerPeer(Request $request)
    {
        $validated = $request->validate([
            // 'QuestionId' => 'required|uuid',
            'tahunAjaran' => 'required|string',
            'namaProyek' => 'required|string',
        ]);

        // Log tahun ajaran dan nama proyek
        Log::info('AnswerPeer method called', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);

        return Inertia::render('Dosen/AnswerPeer', [
            'tahunAjaran' => $validated['tahunAjaran'],
            'namaProyek' => $validated['namaProyek'],
        ]);
    }

    public function saveAllAnswersPeerDosen(Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = $request->validate([
                'answers' => 'required|array',
                'answers.*.question_id' => 'required|uuid',
                'answers.*.answer' => 'required|string',
                'answers.*.score' => 'required|integer|between:1,5',
                'answers.*.status' => 'required|string'
            ]);

            foreach ($validated['answers'] as $answerData) {
                AnswersPeer::updateOrCreate(
                    [
                        'question_id' => $answerData['question_id'],
                        'user_id' => auth()->id()
                    ],
                    [
                        'answer' => $answerData['answer'],
                        'score' => $answerData['score'],
                        'status' => $answerData['status']
                    ]
                );
            }

            DB::commit();
            Log::info('Transaction committed');
            return response()->json([
                'success' => true,
                'message' => 'Semua jawaban berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Transaction rolled back', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
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
            'id' => $user->id,
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
    public function getListAnswersKelompokPeer(Request $request)
    {
        // Menangkap query params
        $tahunAjaran = $request->query('tahun_ajaran');
        $namaProyek = $request->query('nama_proyek');
    
        // Validasi input
        if (!$tahunAjaran || !$namaProyek) {
            return response()->json([
                'success' => false,
                'message' => 'Tahun Ajaran atau Nama Proyek tidak ditemukan.'
            ], 400);
        }
    
        try {
            // Mengambil data jawaban yang terisi dan jumlah mahasiswa beserta user_id
            $answers = DB::table('kelompok')
                ->select(
                    'kelompok.kelompok as nama_kelompok',
                    'kelompok.tahun_ajaran',
                    'kelompok.nama_proyek',
                    DB::raw('COUNT(DISTINCT answerspeer.user_id) as total_filled'), // Unik
                    DB::raw('COUNT(DISTINCT kelompok.user_id) as total_mahasiswa'), // Unik
                    DB::raw('GROUP_CONCAT(DISTINCT kelompok.user_id) as user_ids') // Unik
                )
                ->leftJoin('answerspeer', function ($join) {
                    $join->on('answerspeer.user_id', '=', 'kelompok.user_id')
                        ->whereNotNull('answerspeer.status'); // Menghitung yang sudah mengisi
                })
                ->where('kelompok.tahun_ajaran', $tahunAjaran)
                ->where('kelompok.nama_proyek', $namaProyek)
                ->groupBy('kelompok.kelompok', 'kelompok.tahun_ajaran', 'kelompok.nama_proyek')
                ->get();
    
            // Jika data kosong, kembalikan response 404
            if ($answers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada data ditemukan.'
                ], 404);
            }
    
            // Menambahkan user_id dalam bentuk array (menghapus koma)
            $answers = $answers->map(function ($item) {
                $item->user_ids = explode(',', $item->user_ids); // Mengubah string menjadi array
                return $item;
            });
    
            return response()->json([
                'success' => true,
                'data' => $answers
            ]);
    
        } catch (\Exception $e) {
            // Log error dan beri respons error 500
            Log::error('Error fetching answers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    




    public function getListAnswersPeer(Request $request)
{
    $tahunAjaran = $request->query('tahun_ajaran');
    $namaProyek = $request->query('nama_proyek');
    $kelompok = $request->query('kelompok'); // Parameter kelompok

    Log::info('Parameter diterima:', [
        'tahun_ajaran' => $tahunAjaran,
        'nama_proyek' => $namaProyek,
        'kelompok' => $kelompok,
    ]);

    // Validasi input
    if (!$tahunAjaran || !$namaProyek || !$kelompok) {
        return response()->json([
            'success' => false,
            'message' => 'Parameter tidak lengkap.',
        ], 400);
    }

    try {
        // Ambil data dengan join ke tabel kelompok dan filter sesuai parameter
        $answers = AnswersPeer::select('answerspeer.*', 'assessment.pertanyaan')
            ->join('assessment', 'answerspeer.question_id', '=', 'assessment.id') // Join dengan tabel assessment
            ->join('kelompok', 'answerspeer.user_id', '=', 'kelompok.user_id') // Join dengan tabel kelompok
            ->where('assessment.tahun_ajaran', $tahunAjaran) // Filter berdasarkan tahun ajaran
            ->where('assessment.nama_proyek', $namaProyek)   // Filter berdasarkan nama proyek
            ->where('kelompok.kelompok', $kelompok)     // Filter berdasarkan nama kelompok
            ->with(['user', 'peer']) // Relasi ke tabel user dan peer
            ->get();

             Log::info('Hasil query:', ['answers' => $answers->toArray()]);

        if ($answers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $answers,
        ]);
    } catch (\Exception $e) {
        Log::error('Error fetching answers: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan saat mengambil data.',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    public function getListAnswerPeer()
    {
        return Inertia::render('Dosen/ListAnswerPeer');
    }

    public function getListAnswersView()
    {
        return Inertia::render('Dosen/AnswersSelfAssessment');
    }

    public function getListAnswersPeerView()
    {
        return Inertia::render('Dosen/AnswersPeerAssessment');
    }

    public function searchByNip(Request $request)
    {
        $nim = $request->query('nip');
        $user = User::where('nip', $nim)->first(); // Cari berdasarkan nim

        if ($user) {
            return response()->json(['user_id' => $user->id]);
        } else {
            return response()->json(['error' => 'User not found'], 404);
        }
    }

    public function getAnswerPeerDosen($questionId)
    {
        try {
            $answer = AnswersPeer::where('question_id', $questionId)
                ->where('user_id', auth()->id())
                ->first();

            return response()->json($answer);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function answeredPeersDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => 'nullable|string',  // Menambahkan validasi nullable
                'question_id' => 'required|string|exists:assessment,id',
                'answer' => 'required|string',
                'score' => 'required|integer|min:1|max:5',
                'status' => 'required|string',
            ]);

            // Pastikan jika peer_id tidak ada, set null
            $peer_id = $validated['peer_id'] ?? null;

            $existingAnswer = AnswersPeer::where([
                'user_id' => $validated['user_id'],
                'peer_id' => $peer_id,
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

    public function getQuestionsByProjectPeer(Request $request)
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

    public function AnswersPeerDosen(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|string|exists:users,id',
                'peer_id' => '-',
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
}
