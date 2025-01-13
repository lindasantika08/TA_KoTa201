<?php

namespace App\Exports;

use App\Models\Assessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class AssessmentExport implements FromCollection, WithHeadings, WithStartRow
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Assessment::select('Type', 'pertanyaan', 'aspek', 'kriteria')
            ->get()
            ->map(function ($item, $key) {
                return [
                    'no' => $key + 1,
                    'pertanyaan' => $item->pertanyaan,
                    'aspek' => $item->aspek,
                    'kriteria' => $item->kriteria,
                    // 'date_created' => $item->created_at->format('Y-m-d'),
                ];
            });
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'No',
            'pertanyaan',
            'aspek',
            'kriteria',
            // 'Date Created'
        ];
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 3;
    }
}
