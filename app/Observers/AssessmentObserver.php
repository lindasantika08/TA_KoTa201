<?php

namespace App\Observers;

use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Notifications\AssessmentNotifications;

class AssessmentObserver
{
    public function updated(Assessment $assessment)
    {
        if ($assessment->isDirty('is_published') && $assessment->is_published) {
            try {
                $mahasiswa = Mahasiswa::all();
                
                if (!$assessment->relationLoaded('project')) {
                    $assessment->load('project');
                }
                
                $notificationData = [
                    'assessment_id' => $assessment->id,
                    'project_name' => $assessment->project->project_name,
                    'type' => $assessment->type,
                    'end_date' => $assessment->end_date
                ];
                
                \Log::info('Sending assessment notifications', [
                    'assessment_id' => $assessment->id,
                    'project' => $assessment->project->project_name,
                    'mahasiswa_count' => $mahasiswa->count()
                ]);
                
                foreach ($mahasiswa as $mhs) {
                    $mhs->notify(new AssessmentNotifications($notificationData));
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