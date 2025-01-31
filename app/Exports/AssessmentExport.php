<?php

namespace App\Exports;

use App\Models\Assessment;
use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;

class AssessmentExport implements WithMultipleSheets
{
    use Exportable;

    protected $batchYear;
    protected $projectName;
    protected $projectId;

    public function __construct($batchYear, $projectName, $projectId = null)
    {
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
        $this->projectId = $projectId;
    }

    public function sheets(): array
    {
        // Jika projectId tidak ada, cari project
        if (!$this->projectId) {
            $project = Project::where('batch_year', $this->batchYear)
                ->where('project_name', $this->projectName)
                ->first();

            if ($project) {
                $this->projectId = $project->id;
            }
        }

        // Ambil assessment jika project ditemukan
        $assessments = null;
        if ($this->projectId) {
            $assessments = Assessment::with('typeCriteria')
                ->where('project_id', $this->projectId)
                ->get();
        }

        return [
            new AssessmentSheet(
                $this->batchYear,
                $this->projectName,
                $this->projectId,
                $assessments
            ),
            new TypeCriteriaSheet(
                $this->projectId,
                $this->batchYear,
                $this->projectName
            )
        ];
    }
}
