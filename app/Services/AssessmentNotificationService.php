<?php

namespace App\Services;

use App\Models\Group;
use App\Models\project;
use App\Models\Assessment;
use App\Models\User;
use App\Models\Mahasiswa;
use App\Notifications\Assessment as AssessmentNotification;

class AssessmentNotificationService
{
    public function checkAndNotifyPendingAssessments(User $user)
    {
        // Get mahasiswa data from user
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();
        
        if (!$mahasiswa) {
            return;
        }

        // Get all groups the student is a member of
        $groups = Group::where('mahasiswa_id', $mahasiswa->id)
            ->with(['project' => function ($query) {
                $query->where('status', 'active')
                    ->where('end_date', '>=', now());
            }])
            ->with(['project.assessments' => function ($query) {
                $query->whereNull('deleted_at');
            }])
            ->get();

        foreach ($groups as $group) {
            if ($group->project && $group->project->assessments->isNotEmpty()) {
                foreach ($group->project->assessments as $assessment) {
                    // Check if we haven't already notified about this assessment
                    $alreadyNotified = $user->notifications()
                        ->where('type', AssessmentNotification::class)
                        ->where('data->assessment_id', $assessment->id)
                        ->exists();

                    if (!$alreadyNotified) {
                        $user->notify(new AssessmentNotification(
                            $assessment,
                            $group->project->project_name
                        ));
                    }
                }
            }
        }
    }
}