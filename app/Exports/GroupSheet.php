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
            ->join('type_criteria', 'assessment.criteria_id', '=', 'type_criteria.id')
            ->where('assessment.project_id', $this->project->id)
            ->select('assessment.*', 'type_criteria.aspect', 'type_criteria.criteria')
            ->orderBy('type_criteria.aspect')
            ->orderBy('type_criteria.criteria')
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

            // Group assessments by aspect and criteria
            $groupedAssessments = $allAssessments->groupBy(function ($assessment) {
                return $assessment->aspect . '|' . $assessment->criteria;
            });

            // Process each grouped assessment (aspect + criteria combination)
            foreach ($groupedAssessments as $groupKey => $assessments) {
                try {
                    [$aspect, $criteria] = explode('|', $groupKey);

                    // Collect all data for this aspect/criteria combination
                    $allSelfAnswers = [];
                    $allSelfScores = [];
                    $allSelfSLAScores = [];
                    $allPeerAnswers = [];
                    $allPeerScores = [];
                    $allPeerSLAScores = [];
                    $allAssessors = [];
                    $allQuestions = [];
                    $allFinalSelfScores = [];
                    $allFinalPeerScores = [];

                    foreach ($assessments as $assessment) {
                        // Get self assessment
                        $selfAnswer = Answers::where('mahasiswa_id', $student->id)
                            ->where('question_id', $assessment->id)
                            ->first();

                        // Get peer assessments
                        $peerAnswers = AnswersPeer::where('peer_id', $student->id)
                            ->where('question_id', $assessment->id)
                            ->with('mahasiswa.user')
                            ->get();

                        // Get final scores from reports
                        $report = Report::where('project_id', $this->project->id)
                            ->where('mahasiswa_id', $student->id)
                            ->where('question_id', $assessment->id)
                            ->first();

                        // Collect question
                        $allQuestions[] = $assessment->question;

                        // Collect self data
                        if ($selfAnswer) {
                            if (!empty($selfAnswer->answer)) {
                                $allSelfAnswers[] = $selfAnswer->answer;
                            }
                            if (!empty($selfAnswer->score)) {
                                $allSelfScores[] = $selfAnswer->score;
                            }
                            if (!empty($selfAnswer->score_SLA)) {
                                $allSelfSLAScores[] = $selfAnswer->score_SLA;
                            }
                        }

                        // Collect peer data
                        foreach ($peerAnswers as $peer) {
                            if (!empty($peer->answer)) {
                                $allPeerAnswers[] = $peer->answer;
                            }
                            if (!empty($peer->score)) {
                                $allPeerScores[] = $peer->score;
                            }
                            if (!empty($peer->score_SLA)) {
                                $allPeerSLAScores[] = $peer->score_SLA;
                            }
                            if ($peer->mahasiswa && $peer->mahasiswa->user) {
                                $allAssessors[] = $peer->mahasiswa->user->name;
                            }
                        }

                        // Collect final scores
                        if ($report) {
                            if ($report->final_score_self > 0) {
                                $allFinalSelfScores[] = $report->final_score_self;
                            }
                            if ($report->final_score_peer > 0) {
                                $allFinalPeerScores[] = $report->final_score_peer;
                            }
                        }
                    }

                    // Skip only if both self and peer answers are completely empty
                    if (empty($allSelfAnswers) && empty($allPeerAnswers)) {
                        continue;
                    }

                    // Combine and format all collected data
                    $questionsText = !empty($allQuestions) ? implode(' | ', array_unique($allQuestions)) : 'N/A';
                    $selfAnswersText = !empty($allSelfAnswers) ? implode(' | ', $allSelfAnswers) : 'No answer';
                    $selfScoresText = !empty($allSelfScores) ? implode(' | ', $allSelfScores) : '0';
                    $selfSLAScoresText = !empty($allSelfSLAScores) ? implode(' | ', $allSelfSLAScores) : '0';
                    $peerAnswersText = !empty($allPeerAnswers) ? implode(' | ', $allPeerAnswers) : 'No peer answers';
                    $peerScoresText = !empty($allPeerScores) ? implode(' | ', $allPeerScores) : 'No peer scores';
                    $peerSLAScoresText = !empty($allPeerSLAScores) ? implode(' | ', $allPeerSLAScores) : 'No peer SLA scores';
                    $assessorsText = !empty($allAssessors) ? implode(' | ', array_unique($allAssessors)) : 'No assessors';

                    // Calculate average final scores for this aspect/criteria
                    $avgFinalSelfScore = !empty($allFinalSelfScores) ? round(array_sum($allFinalSelfScores) / count($allFinalSelfScores), 2) : 0;
                    $avgFinalPeerScore = !empty($allFinalPeerScores) ? round(array_sum($allFinalPeerScores) / count($allFinalPeerScores), 2) : 0;

                    // Add single row for this aspect/criteria combination
                    $data->push([
                        '',                                         // A: Student Name (empty for detail rows)
                        '',                                         // B: NIM (empty for detail rows)
                        '',                                         // C: Class (empty for detail rows)
                        $aspect ?? 'N/A',                          // D: Aspect
                        $criteria ?? 'N/A',                        // E: Criteria
                        $questionsText,                             // F: Questions (combined)
                        $selfAnswersText,                           // G: Self Answers (combined)
                        $selfScoresText,                            // H: Self Scores (combined)
                        $selfSLAScoresText,                         // I: Self SLA Scores (combined)
                        $peerAnswersText,                           // J: Peer Answers (combined)
                        $peerScoresText,                            // K: Peer Scores (combined)
                        $peerSLAScoresText,                         // L: Peer SLA Scores (combined)
                        $assessorsText,                             // M: Assessors (combined)
                        $avgFinalSelfScore,                         // N: Average Final Self Score
                        $avgFinalPeerScore,                         // O: Average Final Peer Score
                    ]);
                } catch (\Exception $e) {
                    Log::error('Error processing grouped assessment ' . $groupKey . ' for student ' . $student->id . ': ' . $e->getMessage());
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
            'Questions',
            'Self Answers',
            'Self Scores',
            'Self SLA Scores',
            'Peer Answers',
            'Peer Scores',
            'Peer SLA Scores',
            'Assessors',
            'Avg Final Self Score',
            'Avg Final Peer Score'
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
