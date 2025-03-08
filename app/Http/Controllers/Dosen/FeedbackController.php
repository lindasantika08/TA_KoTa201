<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\Group;
use App\Models\Project;
use App\Models\Feedback;
use App\Models\Mahasiswa;
use App\Models\feedback_ai;
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

            // Get the project
            $project = Project::where('project_name', $validated['project_name'])->first();
            if (!$project) {
                return response()->json([
                    'message' => 'Project not found',
                    'data' => []
                ], 404);
            }

            // Get the groups with dosen relationship
            $groups = Group::with('dosen')
                ->where('batch_year', $validated['batch_year'])
                ->where('project_id', $project->id)
                ->where('group', $validated['kelompok'])
                ->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'message' => 'No groups found',
                    'data' => []
                ], 404);
            }

            // Get group IDs and related IDs
            $groupIds = $groups->pluck('id');
            $mahasiswaIds = $groups->pluck('mahasiswa_id')->unique();
            $dosenIds = $groups->pluck('dosen_id')->unique();

            // Get all feedbacks with eager loading
            $feedbacks = Feedback::with([
                'mahasiswa.user',
                'dosen.user',
                'peer.user'
            ])
                ->where(function ($query) use ($groupIds, $mahasiswaIds, $dosenIds) {
                    $query->whereIn('group_id', $groupIds)
                        ->where(function ($q) use ($mahasiswaIds, $dosenIds) {
                            $q->whereIn('mahasiswa_id', $mahasiswaIds)
                                ->orWhereIn('dosen_id', $dosenIds);
                        });
                })
                ->orderBy('created_at', 'desc')
                ->get();

            // Debug log untuk melihat feedbacks yang diambil
            Log::info('Retrieved feedbacks:', [
                'count' => $feedbacks->count(),
                'feedbacks' => $feedbacks->map(function ($f) {
                    return [
                        'id' => $f->id,
                        'dosen_id' => $f->dosen_id,
                        'mahasiswa_id' => $f->mahasiswa_id,
                        'peer_id' => $f->peer_id,
                        'group_id' => $f->group_id
                    ];
                })
            ]);

            $formattedFeedbacks = $feedbacks->map(function ($feedback) {
                // Log untuk setiap feedback yang diproses
                Log::info('Processing feedback:', [
                    'id' => $feedback->id,
                    'dosen_id' => $feedback->dosen_id,
                    'dosen' => $feedback->dosen ? [
                        'id' => $feedback->dosen->id,
                        'user' => $feedback->dosen->user ? [
                            'id' => $feedback->dosen->user->id,
                            'name' => $feedback->dosen->user->name
                        ] : null
                    ] : null
                ]);

                return [
                    // Mahasiswa (receiver) information - only include if mahasiswa exists
                    'mahasiswa_id' => $feedback->mahasiswa_id,
                    'mahasiswa_name' => $feedback->mahasiswa ? ($feedback->mahasiswa->user->name ?? 'Unknown') : null,
                    'mahasiswa_nim' => $feedback->mahasiswa ? $feedback->mahasiswa->nim : null,

                    // Dosen information
                    'dosen_id' => $feedback->dosen_id,
                    'dosen_name' => $feedback->dosen ? ($feedback->dosen->user->name ?? null) : null,
                    'dosen_nip' => $feedback->dosen ? $feedback->dosen->nip : null,

                    // Peer information
                    'peer_id' => $feedback->peer_id,
                    'peer_name' => $feedback->peer ? ($feedback->peer->user->name ?? null) : null,
                    'peer_nim' => $feedback->peer ? $feedback->peer->nim : null,

                    // Other details
                    'group_id' => $feedback->group_id,
                    'feedback' => $feedback->feedback,
                    'created_at' => $feedback->created_at->format('Y-m-d H:i:s'),
                    'feedback_type' => $feedback->dosen_id ? 'dosen' : ($feedback->peer_id ? 'peer' : 'unknown')
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
        $validated = $request->validate([
            'batch_year' => 'required|string',
            'project_name' => 'required|string',
            'kelompok' => 'required|string',
            'force_regenerate' => 'sometimes|boolean'
        ]);

        try {
            $project = Project::where('project_name', $validated['project_name'])->first();
            if (!$project) {
                return response()->json([
                    'success' => false,
                    'message' => "Proyek tidak ditemukan.",
                ], 404);
            }

            $groups = Group::where([
                'batch_year' => $validated['batch_year'],
                'project_id' => $project->id,
                'group' => $validated['kelompok']
            ])->get();

            if ($groups->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "Tidak ada grup ditemukan.",
                ], 404);
            }

            $summaries = [];

            foreach ($groups as $group) {
                $existingFeedback = feedback_ai::where([
                    'mahasiswa_id' => $group->mahasiswa_id,
                    'group_id' => $group->id
                ])->first();

                if (!$existingFeedback || ($validated['force_regenerate'] ?? false)) {
                    $feedbacks = Feedback::where('peer_id', $group->mahasiswa_id)
                        ->with(['peer.user', 'mahasiswa.user'])
                        ->get();

                    if ($feedbacks->isEmpty()) {
                        continue;
                    }

                    $feedbackTexts = $feedbacks->map(function ($feedback) {
                        return "Feedback: {$feedback->feedback}";
                    })->join("\n\n");

                    try {
                        $response = $this->callGeminiWithErrorHandling(
                            "Analisis Komprehensif Feedback Mahasiswa: {$group->mahasiswa->user->name} (NIM: {$group->mahasiswa->nim})

Kumpulan Feedback:
{$feedbackTexts}

Instruksi untuk Pembuatan Ringkasan:
1. Buat ringkasan deskriptif yang menjelaskan:
   - Kekuatan utama mahasiswa
   - Area pengembangan dan perbaikan
   - Pola umum yang terlihat dari berbagai feedback
   - buat jangan point per point tapi dalam bentuk deskriptif saja

2. Fokus pada:
   - Objektifitas
   - Kejelasan
   - Konstruktif

3. Hindari:
   - Menyebutkan nama pemberi feedback
   - Kalimat yang bersifat personal atau menyinggung
   - Detail spesifik yang dapat mengidentifikasi pemberi feedback

Hasilkan ringkasan professional, mendalam, dan bermakna yang dapat membantu mahasiswa dalam pengembangan diri."
                        );

                        if ($existingFeedback) {
                            $existingFeedback->delete();
                        }

                        $newFeedbackAi = feedback_ai::create([
                            'mahasiswa_id' => $group->mahasiswa_id,
                            'group_id' => $group->id,
                            'summary' => Str::limit($response, 65535, '...')
                        ]);

                        $summaries[] = [
                            'peer_id' => $group->mahasiswa_id,
                            'peer_name' => $group->mahasiswa->user->name,
                            'peer_nim' => $group->mahasiswa->nim,
                            'summary' => $response,
                            'source' => 'gemini'
                        ];
                    } catch (\Exception $e) {
                        Log::error("Gemini API Error", [
                            'message' => $e->getMessage(),
                            'mahasiswa_name' => $group->mahasiswa->user->name,
                            'mahasiswa_nim' => $group->mahasiswa->nim,
                        ]);

                        $summaries[] = [
                            'peer_id' => $group->mahasiswa_id,
                            'peer_name' => $group->mahasiswa->user->name,
                            'peer_nim' => $group->mahasiswa->nim,
                            'summary' => "Gagal menghasilkan ringkasan: " . $e->getMessage(),
                            'source' => 'error'
                        ];
                    }
                } else {
                    $summaries[] = [
                        'peer_id' => $group->mahasiswa_id,
                        'peer_name' => $group->mahasiswa->user->name,
                        'peer_nim' => $group->mahasiswa->nim,
                        'summary' => $existingFeedback->summary,
                        'source' => 'database'
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'summaries' => $summaries,
            ]);
        } catch (\Exception $e) {
            Log::error("Kesalahan Umum di getSummaryFeedback", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => "Terjadi kesalahan pada server.",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Metode tambahan untuk error handling Gemini
    private function callGeminiWithErrorHandling($prompt)
    {
        $apiKey = config('services.gemini.api_key');

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$apiKey}", [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ]
        ]);

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->body());
        }

        $data = $response->json();
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? "Gagal menghasilkan ringkasan.";
    }

    public function storeFeedback(Request $request)
    {
        DB::beginTransaction(); // Start transaction at the beginning

        try {
            // Log untuk debugging
            Log::info('Request received:', $request->all());

            // Validasi
            $validated = $request->validate([
                'batch_year' => 'required|string',
                'project_name' => 'required|string',
                'kelompok' => 'required|string',
                'student_id' => 'required|string',
                'feedback' => 'required|string',
            ]);

            // Cek autentikasi
            if (!Auth::check()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized'
                ], 401);
            }

            // Get dosen ID from authenticated user
            $dosenId = Auth::user()->dosen->id;
            Log::info('Dosen ID:', ['id' => $dosenId]);

            // Cek query group
            $group = Group::whereHas('project', function ($query) use ($validated) {
                $query->where('batch_year', $validated['batch_year'])
                    ->where('project_name', $validated['project_name']);
            })
                ->where('mahasiswa_id', $validated['student_id'])
                ->first();

            // Log group query result
            Log::info('Found group:', ['group' => $group]);

            if (!$group) {
                throw new \Exception('Group not found for the given criteria');
            }

            // Create the feedback
            $feedback = Feedback::create([
                'dosen_id' => $dosenId,
                'peer_id' => $validated['student_id'],
                'group_id' => $group->id,
                'feedback' => $validated['feedback'],
            ]);

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Feedback berhasil disimpan',
                'data' => $feedback
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Feedback storage error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to store feedback: ' . $e->getMessage()
            ], 500);
        }
    }
}
