<?php

namespace App\Exports;

use App\Models\SelfAssessment;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class PeerExport implements FromCollection, WithHeadings, WithStartRow
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return SelfAssessment::select('semester', 'project', 'status', 'created_at')
            ->get()
            ->map(function($item, $key) {
                return [
                    'no' => $key + 1,
                    'semester' => $item->semester,
                    'project' => $item->project,
                    'status' => $item->status,
                    'date_created' => $item->created_at->format('Y-m-d'),
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
            'Semester',
            'Project',
            'Status',
            'Date Created'
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
