<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Notifications\AssessmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class SendAssessmentReminder extends Command
{
    protected $signature   = 'assessment:reminder';
    protected $description = 'Kirim notifikasi reminder H‑2 sebelum assessment ditutup';

    public function handle(): int
    {
        Log::info('assessment:reminder running', ['now' => now()]);
        $todayPlus2 = Carbon::today()->addDays(2);

        // ambil assessment yg publish & berakhir H‑2
        $assessments = Assessment::with('project')
            ->where('is_published', true)
            ->whereDate('end_date', $todayPlus2)
            ->get()
            ->groupBy('assessment_order');

        $sent = 0;

        foreach ($assessments as $assessment) {
            // semua mahasiswa di project tsb
            $assessment = $assessment->first();

            $mahasiswaIds = DB::table('groups')
                ->where('project_id', $assessment->project_id)
                ->pluck('mahasiswa_id')
                ->unique();

            foreach ($mahasiswaIds as $mhsId) {
                $mhs  = Mahasiswa::find($mhsId);
                $user = $mhs?->user;
                if (!$user) continue;

                // skip yg sudah mengisi
                $filled = DB::table('answers')
                    ->where('question_id',  $assessment->id)
                    ->where('mahasiswa_id', $mhsId)
                    ->exists();
                if ($filled) continue;

                // hindari duplikat
                $exists = $user->notifications()
                    ->where('type', AssessmentReminderNotification::class)
                    ->whereJsonContains('data->assessment_id', $assessment->id)
                    ->exists();
                if ($exists) continue;

                $reminderData = [
                    'assessment_id' => $assessment->id,
                    'project_name'  => $assessment->project->project_name ?? 'Unknown Project',
                    'type'          => $assessment->type,
                ];

                $user->notify(new AssessmentReminderNotification($reminderData));
                $sent++;
            }
        }

        $this->info("Reminder sent: {$sent}");
        return self::SUCCESS;
    }
}
