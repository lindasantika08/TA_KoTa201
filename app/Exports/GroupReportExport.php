<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\Exportable;
use App\Models\Group;
use App\Models\Project;
use App\Models\Report;
use App\Models\Mahasiswa;
use App\Exports\GroupSheet;
use App\Exports\EmptySheet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GroupReportExport implements WithMultipleSheets
{
    use Exportable;

    protected $batchYear;
    protected $projectName;

    public function __construct($batchYear, $projectName)
    {
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
    }

    public function sheets(): array
    {
        $sheets = [];

        try {
            // Get project
            $project = Project::where('batch_year', $this->batchYear)
                ->where('project_name', $this->projectName)
                ->first();

            if (!$project) {
                // Create a fallback sheet with error message
                $sheets[] = new EmptySheet('No project found');
                return $sheets;
            }

            // Get all groups for this project
            $groups = Group::where('batch_year', $this->batchYear)
                ->where('project_id', $project->id)
                ->with(['mahasiswa.user', 'mahasiswa.classRoom'])
                ->get()
                ->groupBy('group');

            if ($groups->isEmpty()) {
                // Create a fallback sheet with no groups message
                $sheets[] = new EmptySheet('No groups found for this project');
                return $sheets;
            }

            foreach ($groups as $groupName => $groupMembers) {
                try {
                    $sheets[] = new GroupSheet($project, $groupName, $groupMembers, $this->batchYear, $this->projectName);
                } catch (\Exception $e) {
                    Log::error('Error creating sheet for group ' . $groupName . ': ' . $e->getMessage());
                    continue;
                }
            }

            // If no sheets were created successfully, add an error sheet
            if (empty($sheets)) {
                $sheets[] = new EmptySheet('No data available for export');
            }

            return $sheets;
        } catch (\Exception $e) {
            Log::error('Error in GroupReportExport sheets(): ' . $e->getMessage());
            // Return a sheet with error message
            $sheets[] = new EmptySheet('Error occurred during export: ' . $e->getMessage());
            return $sheets;
        }
    }
}
