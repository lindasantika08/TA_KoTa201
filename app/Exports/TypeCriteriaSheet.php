<?php

namespace App\Exports;

use App\Models\type_criteria;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;

class TypeCriteriaSheet implements FromCollection, WithHeadings, WithStartRow
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return type_criteria::all()->map(function ($item, $key) {
            return [
                'no' => $key + 1,
                'aspek' => $item->aspek,
                'kriteria' => $item->kriteria,
                'bobot_1' => $item->bobot_1,
                'bobot_2' => $item->bobot_2,
                'bobot_3' => $item->bobot_3,
                'bobot_4' => $item->bobot_4,
                'bobot_5' => $item->bobot_5,
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
            'Aspek',
            'Kriteria',
            'Bobot 1',
            'Bobot 2',
            'Bobot 3',
            'Bobot 4',
            'Bobot 5',
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
