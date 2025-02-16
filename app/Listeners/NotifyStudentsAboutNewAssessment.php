<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Events\AssessmentCreated;
use App\Models\Group;
use App\Notifications\Assessment as AssessmentNotification;

class NotifyStudentsAboutNewAssessment
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AssessmentCreated $event): void
    {
        $assessment = $event->assessment;
        $projectName = $event->projectName;

        // Get all groups in this project
        $groups = Group::whereHas('project', function ($query) use ($assessment) {
            $query->where('id', $assessment->project_id);
        })->with('mahasiswa.user')->get();

        foreach ($groups as $group) {
            if ($group->mahasiswa && $group->mahasiswa->user) {
                $user = $group->mahasiswa->user;
                
                // Check if notification already exists
                $alreadyNotified = $user->notifications()
                    ->where('type', AssessmentNotification::class)
                    ->where('data->assessment_id', $assessment->id)
                    ->exists();

                if (!$alreadyNotified) {
                    $user->notify(new AssessmentNotification(
                        $assessment,
                        $projectName
                    ));
                }
            }
        }
    }
}
