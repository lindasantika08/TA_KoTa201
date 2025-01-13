<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class KelolaKelompokController extends Controller
{
    public function KelolaKelompok()
    {
        return Inertia::render('Dosen/KelolaKelompok');
    }
   
}
