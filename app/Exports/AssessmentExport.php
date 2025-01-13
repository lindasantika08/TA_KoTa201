<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\type_criteria;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AssessmentExport implements WithMultipleSheets
{
    /**
     * @return array
     */
    public function sheets(): array
    {
        return [
            new AssessmentSheet(),
            new TypeCriteriaSheet(),
        ];
    }
}
