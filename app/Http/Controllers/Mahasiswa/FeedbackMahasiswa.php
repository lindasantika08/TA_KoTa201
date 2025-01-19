<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackMahasiswa extends Controller
{
    public function feedbackMahasiswa() {
        return Inertia::render('Mahasiswa/FeedbackMahasiswa');
    }
}
