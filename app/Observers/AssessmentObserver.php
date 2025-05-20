<?php

namespace App\Observers;

use App\Models\Assessment;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\DB;
use App\Notifications\AssessmentNotifications;

class AssessmentObserver
{
    public function updated(Assessment $assessment)
    {
        if (
                $assessment->isDirty('is_published') &&
                $assessment->getOriginal('is_published') == false &&
                $assessment->is_published == true
            )
            {
            try {
                $mahasiswa = Mahasiswa::all();

                $assessments = Assessment::with(['project'])
                ->select(
                    'project_id',
                    'type',
                    DB::raw('MAX(id) as id'), 
                    DB::raw('MAX(end_date) as end_date'), 
                    DB::raw('MIN(created_at) as created_at')
                )
                ->where('is_published', true)
                ->groupBy('project_id', 'type')
                ->get();

                foreach ($assessments as $assessment) {
                    $notificationData = [
                        'assessment_id' => $assessment->id,
                        'project_name' => $assessment->project->project_name ?? 'Unknown Project',
                        'type' => $assessment->type,
                        'end_date' => $assessment->end_date,
                    ];

                    $mahasiswa = DB::table('groups')
                        ->where('project_id', $assessment->project_id)
                        ->pluck('mahasiswa_id');

                    foreach ($mahasiswa as $mhsId) {
                        $mhs = Mahasiswa::find($mhsId);
                        if ($mhs) {
                            $user = $mhs->user;
                            if ($user) {
                                $exists = $user->notifications()
                                    ->where('type', AssessmentNotifications::class)
                                    ->whereJsonContains('data->assessment_id', $assessment->id)
                                    ->exists();

                                if (!$exists) {
                                    $user->notify(new AssessmentNotifications($notificationData));
                                }
                            }
                        }
                    }
                }
                \Log::info('Assessment notifications sent successfully');
                
            } catch (\Exception $e) {
                \Log::error('Error sending assessment notifications: ' . $e->getMessage(), [
                    'assessment_id' => $assessment->id
                ]);
            }
        }
    }
}