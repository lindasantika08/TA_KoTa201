<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assessment;
use App\Models\Dosen;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use App\Notifications\AssessmentReminderLecturerNotification;

class SendAssessmentReminderLecturer extends Command
{
    protected $signature   = 'assessment:reminder-lecturer';
    protected $description = 'Kirim notifikasi ke dosen H‑2 sebelum assessment ditutup';

    public function handle(): int
    {
        Log::info('assessment:reminder-lecturer running', ['now' => now()]);
        $deadline = Carbon::today()->addDays(2);

        // ambil assessment publish yg berakhir H‑2
        $assessments = Assessment::with('project')
            ->where('is_published', true)
            ->whereDate('end_date', $deadline)
            ->get()
            ->groupBy('assessment_order');

        $sent = 0;

        foreach ($assessments as $assessmentGroup) {
            // Ambil assessment pertama dari group (semua assessment dalam group punya project sama)
            $assessment = $assessmentGroup->first();
            $dosenIds = DB::table('groups')
            ->where('project_id', $assessment->project_id)
            ->pluck('dosen_id')
            ->unique();

            foreach ($dosenIds as $dosenId) {
            $dosen = Dosen::find($dosenId);
            $user  = $dosen?->user;
            Log::info('Checking user for assessment reminder', [
                'dosen_id' => $dosenId,
                'user_id'  => $user?->id,
                'assessment_order' => $assessment->assessment_order,
            ]);
            if (!$user) continue;

            // Cek apakah sudah pernah kirim notif untuk assessment_order ini
            $exists = $user->notifications()
                ->where('type', AssessmentReminderLecturerNotification::class)
                ->whereJsonContains('data->assessment_order', $assessment->assessment_order)
                ->exists();
            if ($exists) continue;

            $payload = [
                'assessment_id' => $assessment->id,
                'assessment_order' => $assessment->assessment_order,
                'batch_year' => $assessment->batch_year,
                'project_name'  => $assessment->project->project_name ?? 'Unknown Project',
                'type'          => $assessment->type,
            ];

            $user->notify(new AssessmentReminderLecturerNotification($payload));
            $sent++;
            }
        }

        $this->info("Lecturer reminders sent: {$sent}");
        return self::SUCCESS;
    }
}
