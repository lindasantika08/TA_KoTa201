<?php

namespace App\Http\Controllers\Dosen;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeedbackController extends Controller
{
    public function feedback()
    {
        return Inertia::render('Dosen/Feedback');
    }

    

   
}
