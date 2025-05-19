<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use Exception;
use Illuminate\Support\Facades\Log;

class ReflectiveAssessmentExport implements WithMultipleSheets
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
        // If no project ID is provided, try to find it (if Project model exists)
        if (!$this->projectId && class_exists('App\Models\Project')) {
            $project = Project::where('batch_year', $this->batchYear)
                ->where('project_name', $this->projectName)
                ->first();

            if ($project) {
                $this->projectId = $project->id;
            }
        }

        $sheets = [];

        try {
            // Add the question sheet
            $sheets[] = new ReflectiveAssessmentQuestionSheet(
                $this->batchYear,
                $this->projectName,
                $this->projectId
            );

            // Add the rubric sheet
            $sheets[] = new ReflectiveAssessmentRubricSheet(
                $this->projectId,
                $this->batchYear,
                $this->projectName
            );
        } catch (Exception $e) {
            // Log any errors for debugging
            Log::error('Error creating export sheets: ' . $e->getMessage());

            // Throw the exception to show the error to the user
            throw new Exception('Error creating export sheets: ' . $e->getMessage());
        }

        return $sheets;
    }
}
