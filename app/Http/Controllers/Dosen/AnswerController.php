<?php

namespace App\Http\Controllers\Dosen;

use App\Models\Answers;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class AnswerController extends Controller
{
    public function showAnswersSelf(Request $request)
    {
        $tahunAjaran = $request->input('tahunAjaran');
        $namaProyek = $request->input('namaProyek');

        $answers = Answers::with('question') // Mengambil data terkait dengan pertanyaan
            ->whereHas('question', function ($query) use ($tahunAjaran, $namaProyek) {
                $query->where('tahun_ajaran', $tahunAjaran)
                    ->where('nama_proyek', $namaProyek);
            })
            ->get();

        return Inertia::render('Dosen/AnswersSelfAssessment', [
            'tahunAjaran' => $tahunAjaran,
            'namaProyek' => $namaProyek,
            'answers' => $answers,
        ]);
    }
}
