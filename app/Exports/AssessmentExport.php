<?php

namespace App\Exports;


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
