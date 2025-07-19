<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use App\Models\Report;
use App\Models\TypeCriteria;
use App\Models\Assessment;
use App\Models\Answers;
use App\Models\AnswersPeer;
use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class GroupSheet implements FromCollection, WithHeadings, WithTitle, WithStyles, WithColumnWidths, WithCustomStartCell
{
    protected $project;
    protected $groupName;
    protected $groupMembers;
    protected $batchYear;
    protected $projectName;

    public function __construct($project, $groupName, $groupMembers, $batchYear, $projectName)
    {
        $this->project = $project;
        $this->groupName = $groupName;
        $this->groupMembers = $groupMembers;
        $this->batchYear = $batchYear;
        $this->projectName = $projectName;
    }

    public function collection()
    {
        $data = collect();

        // Get group member IDs
        $memberIds = $this->groupMembers->pluck('mahasiswa_id')->toArray();

        if (empty($memberIds)) {
            // Return empty data if no members found
            return $data;
        }

        // Get all assessments/questions used in this project
        $allAssessments = DB::table('assessment')
            ->join('type_criterias', 'assessment.criteria_id', '=', 'type_criterias.id')
            ->where('assessment.project_id', $this->project->id)
            ->select('assessment.*', 'type_criterias.aspect', 'type_criterias.criteria')
            ->orderBy('type_criterias.aspect')
            ->orderBy('type_criterias.criteria')
            ->orderBy('assessment.question')
            ->get();

        if ($allAssessments->isEmpty()) {
            // Return empty data if no assessments found
            return $data;
        }

        foreach ($this->groupMembers as $member) {
            $student = $member->mahasiswa;
            if (!$student || !$student->user) continue;

            // Calculate overall final scores for this student
            $overallSelfScore = Report::where('project_id', $this->project->id)
                ->where('mahasiswa_id', $student->id)
                ->where('final_score_self', '>', 0)
                ->avg('final_score_self') ?: 0;

            $overallPeerScore = Report::where('project_id', $this->project->id)
                ->where('mahasiswa_id', $student->id)
                ->where('final_score_peer', '>', 0)
                ->avg('final_score_peer') ?: 0;

            // Add student summary row (only name, NIM, class, and final scores)
            $data->push([
                $student->user->name,                           // A: Student Name
                $student->nim,                                  // B: NIM
                $student->classRoom ? $student->classRoom->class_name : 'N/A', // C: Class
                '',                                             // D: Aspect (empty for summary)
                '',                                             // E: Criteria (empty for summary)
                '',                                             // F: Question (empty for summary)
                '',                                             // G: Self Answer (empty for summary)
                '',                                             // H: Self Score (empty for summary)
                '',                                             // I: Self SLA Score (empty for summary)
                '',                                             // J: Peer Answers (empty for summary)
                '',                                             // K: Peer Scores (empty for summary)
                '',                                             // L: Peer SLA Scores (empty for summary)
                '',                                             // M: Assessors (empty for summary)
                round($overallSelfScore, 2),                    // N: Final Self Score
                round($overallPeerScore, 2),                    // O: Final Peer Score
            ]);

            // Process each assessment for this student
            foreach ($allAssessments as $assessment) {
                try {
                    // Get self assessment
                    $selfAnswer = Answers::where('mahasiswa_id', $student->id)
                        ->where('question_id', $assessment->id)
                        ->first();

                    // Skip if no self answer
                    if (!$selfAnswer || empty($selfAnswer->answer)) {
                        continue;
                    }

                    // Get peer assessments (peer_id should match mahasiswa in answers table)
                    $peerAnswers = AnswersPeer::where('peer_id', $student->id)
                        ->where('question_id', $assessment->id)
                        ->with('mahasiswa.user') // mahasiswa_id in answers_peer is the assessor
                        ->get();

                    // Skip if no peer answers
                    if ($peerAnswers->isEmpty()) {
                        continue;
                    }

                    // Get final scores from reports
                    $report = Report::where('project_id', $this->project->id)
                        ->where('mahasiswa_id', $student->id)
                        ->where('question_id', $assessment->id)
                        ->first();

                    // Prepare peer data
                    $peerAnswersText = $peerAnswers->map(function ($peer) {
                        return $peer->answer ?: '';
                    })->filter()->implode(' | ');

                    $peerScoresText = $peerAnswers->map(function ($peer) {
                        return $peer->score ?: '';
                    })->filter()->implode(' | ');

                    $peerSLAScoresText = $peerAnswers->map(function ($peer) {
                        return $peer->score_SLA ?: '';
                    })->filter()->implode(' | ');

                    $assessorsText = $peerAnswers->map(function ($peer) {
                        return $peer->mahasiswa && $peer->mahasiswa->user ? $peer->mahasiswa->user->name : '';
                    })->filter()->implode(' | ');

                    // Only add row if we have both self and peer data
                    if (!empty($peerAnswersText)) {
                        $data->push([
                            '',                                         // A: Student Name (empty for detail rows)
                            '',                                         // B: NIM (empty for detail rows)
                            '',                                         // C: Class (empty for detail rows)
                            $assessment->aspect ?? 'N/A',              // D: Aspect
                            $assessment->criteria ?? 'N/A',            // E: Criteria
                            $assessment->question ?? 'N/A',            // F: Question
                            $selfAnswer->answer,                        // G: Self Answer
                            $selfAnswer->score ?: 0,                   // H: Self Score
                            $selfAnswer->score_SLA ?: 0,               // I: Self SLA Score
                            $peerAnswersText,                           // J: Peer Answers
                            $peerScoresText,                            // K: Peer Scores
                            $peerSLAScoresText,                         // L: Peer SLA Scores
                            $assessorsText,                             // M: Assessors
                            $report ? round($report->final_score_self, 2) : 0,  // N: Final Self Score
                            $report ? round($report->final_score_peer, 2) : 0,  // O: Final Peer Score
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing assessment ' . $assessment->id . ' for student ' . $student->id . ': ' . $e->getMessage());
                    continue;
                }
            }

            // Add spacer row between students
            if ($member !== $this->groupMembers->last()) {
                $data->push([
                    '',                                         // A: Student Name
                    '',                                         // B: NIM
                    '',                                         // C: Class
                    '',                                         // D: Aspect
                    '',                                         // E: Criteria
                    '',                                         // F: Question
                    '',                                         // G: Self Answer
                    '',                                         // H: Self Score
                    '',                                         // I: Self SLA Score
                    '',                                         // J: Peer Answers
                    '',                                         // K: Peer Scores
                    '',                                         // L: Peer SLA Scores
                    '',                                         // M: Assessors
                    '',                                         // N: Final Self Score
                    '',                                         // O: Final Peer Score
                ]);
            }
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Student Name',
            'NIM',
            'Class',
            'Aspect',
            'Criteria',
            'Question',
            'Self Answer',
            'Self Score',
            'Self SLA Score',
            'Peer Answers',
            'Peer Scores',
            'Peer SLA Scores',
            'Assessors',
            'Final Self Score',
            'Final Peer Score'
        ];
    }

    public function title(): string
    {
        return 'Group ' . $this->groupName;
    }

    public function startCell(): string
    {
        return 'A3';
    }

    public function styles(Worksheet $sheet)
    {
        // Add title
        $sheet->setCellValue('A1', 'Group Report: ' . $this->groupName);
        $sheet->setCellValue('A2', 'Project: ' . $this->projectName . ' - ' . $this->batchYear);

        // Style title
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);

        // Style headers
        $sheet->getStyle('A3:O3')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => '4472C4']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ]);

        // Get data to apply conditional styling
        $data = $this->collection();
        $rowNum = 4; // Starting after headers

        foreach ($data as $row) {
            if (is_array($row) && count($row) > 0) {
                // Check if this is a student summary row (has student name)
                $hasStudentName = !empty($row[0]);

                if ($hasStudentName) {
                    // Style student summary row with highlighting
                    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => '2E5C8A']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'E7F3FF']],
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP]
                    ]);
                } elseif (!empty($row[3])) { // Has aspect data
                    // Regular data row for assessment details
                    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->applyFromArray([
                        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
                        'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP]
                    ]);
                } else {
                    // Empty spacer row
                    $sheet->getStyle("A{$rowNum}:O{$rowNum}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'color' => ['rgb' => 'FAFAFA']],
                    ]);
                }
            }
            $rowNum++;
        }

        // Set row height for better readability
        $sheet->getDefaultRowDimension()->setRowHeight(30);

        // Auto-fit columns but set minimum widths
        foreach (range('A', 'O') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return [];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20, // Student Name
            'B' => 15, // NIM  
            'C' => 15, // Class
            'D' => 20, // Aspect
            'E' => 25, // Criteria
            'F' => 50, // Question
            'G' => 40, // Self Answer
            'H' => 12, // Self Score
            'I' => 15, // Self SLA Score
            'J' => 60, // Peer Answers
            'K' => 20, // Peer Scores
            'L' => 20, // Peer SLA Scores
            'M' => 30, // Assessors
            'N' => 15, // Final Self Score
            'O' => 15, // Final Peer Score
        ];
    }
}
