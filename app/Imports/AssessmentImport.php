<?php

namespace App\Imports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\ToModel;
use Illuminate\Support\Facades\Log;


class AssessmentImport implements ToModel
{
    // Fungsi untuk memetakan data dari Excel ke model Assessment
    public function model(array $row)
    {
        // Debug: melihat data yang diproses dari Excel
        Log::info('Importing row: ' . json_encode($row));

        return new Assessment([
            'type' => $row[0],  // Menyesuaikan dengan kolom yang ada pada Excel
        ]);
    }
}
