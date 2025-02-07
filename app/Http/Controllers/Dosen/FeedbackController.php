<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\Project;
use App\Models\Feedback;
use App\Models\Mahasiswa;
use OpenAI;
use App\Services\GeminiService;


class FeedbackController extends Controller
{
    public function feedback()
    {
        return Inertia::render('Dosen/Feedback');
    }

    public function feedbackDetailView(Request $request)
    {
        // Ambil parameter dari query string
        $tahunAjaran = $request->query('batch_year');
        $namaProyek = $request->query('project_name');
        $kelompok = $request->query('kelompok');

        // Log parameter untuk debugging
        Log::info('Data diterima di controller:', [
            'tahun_ajaran' => $tahunAjaran,
            'nama_proyek' => $namaProyek,
            'kelompok' => $kelompok,
        ]);

        // Validasi input
        if (!$tahunAjaran || !$namaProyek || !$kelompok) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter tidak lengkap'
            ], 400);
        }

        // Mencari project berdasarkan nama proyek
        $project = Project::where('project_name', $namaProyek)->first();

        if (!$project) {
            return response()->json([
                'success' => false,
                'message' => 'Project tidak ditemukan'
            ], 404);
        }

        // Ambil data kelompok dengan relasi mahasiswa dan user
        $groupMembers = Group::with([
            'mahasiswa' => function ($query) {
                $query->with('user'); // Pastikan mengambil user (nama & email)
            },
            'project'
        ])
            ->where('batch_year', $tahunAjaran)
            ->where('project_id', $project->id)
            ->where('group', $kelompok)
            ->get();

        if ($groupMembers->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Data kelompok tidak ditemukan'
            ], 404);
        }

        // Log hasil query untuk debugging
        Log::info('Group Members Data:', $groupMembers->toArray());

        // Kirim data ke halaman Vue
        return Inertia::render('Dosen/FeedbackDetailDosen', [
            'batch_year' => $tahunAjaran,
            'project_name' => $namaProyek,
            'kelompok' => $kelompok,
            'initialData' => [
                'groupMembers' => $groupMembers,
                'project' => $project
            ]
        ]);
    }

    public function getFeedbackAnswer(Request $request)
    {
        try {
            Log::info('Received request parameters:', [
                'batch_year' => $request->batch_year,
                'project_name' => $request->project_name,
                'kelompok' => $request->kelompok
            ]);

            $validated = $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
                'kelompok' => 'required|string',
            ]);

            // First, get the project
            $project = Project::where('project_name', $validated['project_name'])->first();

            if (!$project) {
                return response()->json([
                    'message' => 'Project not found',
                    'data' => []
                ], 404);
            }

            // Get the groups for this project and batch year
            $groups = Group::where('batch_year', $validated['batch_year'])
                ->where('project_id', $project->id)
                ->where('group', $validated['kelompok'])
                ->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'message' => 'No groups found for this project and batch year',
                    'data' => []
                ], 404);
            }

            // Get all mahasiswa IDs from these groups
            $mahasiswaIds = $groups->pluck('mahasiswa_id')->unique();

            if ($mahasiswaIds->isEmpty()) {
                return response()->json([
                    'message' => 'No students found in groups',
                    'data' => []
                ], 404);
            }

            // Get all feedback for these students and groups
            $feedbacks = Feedback::with([
                'mahasiswa.user',
                'dosen.user',  // Add relation to get dosen information
            ])
                ->whereIn('mahasiswa_id', $mahasiswaIds)
                ->whereIn('group_id', $groups->pluck('id'))
                ->orderBy('created_at', 'desc')
                ->get();


            $formattedFeedbacks = $feedbacks->map(function ($feedback) {
                return [
                    // Receiver (Mahasiswa) information
                    'mahasiswa_id' => $feedback->mahasiswa_id,
                    'mahasiswa_name' => $feedback->mahasiswa->user->name ?? 'Unknown',
                    'mahasiswa_nim' => $feedback->mahasiswa->nim,

                    // Sender information
                    'dosen_id' => $feedback->dosen_id,
                    'dosen_name' => $feedback->dosen->user->name ?? null,
                    'peer_id' => $feedback->peer_id,
                    'peer_name' => $feedback->peer ? $feedback->peer->user->name : null,
                    'peer_nim' => $feedback->peer ? $feedback->peer->nim : null,
                    // Other feedback details
                    'group_id' => $feedback->group_id,
                    'feedback' => $feedback->feedback,
                    'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                ];
            });


            return response()->json([
                'message' => 'Feedbacks retrieved successfully',
                'data' => $formattedFeedbacks
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getFeedbackAnswer: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'message' => 'An error occurred while retrieving feedbacks',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    protected $geminiService;

    public function __construct(GeminiService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    public function getSummaryFeedback(Request $request)
    {
        // Validasi request
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
        ]);

        try {
            // Cek apakah proyek ada
            $project = Project::where('project_name', $validated['project_name'])->first();
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => "Proyek tidak ditemukan.",
                ], 404);
            }

            // Ambil semua grup berdasarkan batch_year dan project_id
            $groups = Group::where([
                'batch_year' => $validated['batch_year'],
                'project_id' => $project->id
            ])->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada grup ditemukan.",
                ], 404);
            }

            // Ambil semua mahasiswa yang ada di grup tersebut
            $mahasiswaIds = $groups->pluck('mahasiswa_id');
            $mahasiswaList = Mahasiswa::whereIn('id', $mahasiswaIds)->with('user')->get();

            // Ambil semua feedback yang diberikan kepada mahasiswa dalam grup tersebut
            $feedbacks = Feedback::whereIn('peer_id', $mahasiswaIds)
                ->with(['peer.user', 'mahasiswa.user'])
                ->get()
                ->groupBy('peer_id');

            if ($feedbacks->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'summaries' => [],
                ]);
            }

            $summaries = [];

            foreach ($feedbacks as $peer_id => $peerFeedbacks) {
                $peer = $peerFeedbacks->first()->peer;
                $feedbackTexts = $peerFeedbacks->map(function ($feedback) {
                    return "Feedback: {$feedback->feedback}";
                })->join("\n\n");

                try {
                    $response = $this->geminiService->generateText(
                        "Berikut adalah kumpulan feedback untuk {$peer->user->name} (NIM: {$peer->nim}):\n\n{$feedbackTexts}\n\nBuatkan ringkasan dari semua feedback ini dengan menyoroti poin-poin penting dan area yang perlu ditingkatkan tanpa menyebutkan siapa pemberi feedback."
                    );

                    $summaryText = $response['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal menghasilkan ringkasan.";

                    Log::info('Google Gemini Response:', ['response' => $summaryText]);

                    $summaries[] = [
                        'peer_id' => $peer_id,
                        'peer_name' => $peer->user->name,
                        'peer_nim' => $peer->nim,
                        'summary' => $summaryText,
                    ];
                } catch (\Exception $e) {
                    Log::error("Error Google Gemini: " . $e->getMessage());

                    $summaries[] = [
                        'peer_id' => $peer_id,
                        'peer_name' => $peer->user->name,
                        'peer_nim' => $peer->nim,
                        'summary' => "Gagal menghasilkan ringkasan: " . $e->getMessage(),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'summaries' => $summaries,
            ]);
        } catch (\Exception $e) {
            Log::error("Error di getSummaryFeedback: " . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => "Terjadi kesalahan pada server.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
