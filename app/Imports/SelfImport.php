<?php

namespace App\Imports;

use App\Models\SelfAssessment;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class SelfImport implements ToModel, WithStartRow, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new SelfAssessment([
            'semester' => $row['semester'],
            'project' => $row['project'],
            'status' => $row['status'],
        ]);
    }

    /**
     * @return int
     */
    public function startRow(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function headingRow(): int
    {
        return 2;
    }
}