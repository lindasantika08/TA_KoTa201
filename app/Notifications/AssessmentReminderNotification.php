<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AssessmentReminderNotification extends Notification
{
    use Queueable;

    /** @var array */
    protected $assessmentData;

    /**
     * Constructor menerima satu array data
     */
    public function __construct(array $assessmentData)
    {
        $this->assessmentData = $assessmentData;
    }

    /**
     * Kita cuma simpan di database
     */
    public function via($notifiable): array
    {
        return ['database'];
    }

    /**
     * Bentuk payload yang disimpan di tabel notifications
     */
    public function toDatabase($notifiable): array
    {
        $project    = $this->assessmentData['project_name'] ?? 'Unknown Project';
        $rawType    = strtolower($this->assessmentData['type'] ?? 'selfAssessment');

        $routePart  = str_contains($rawType, 'peer') ? 'peer' : 'self';

        $prettyType = Str::title(
            preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->assessmentData['type'])
        );

        return [
            'project_name'  => $project,
            'type'          => 'reminder',
            'assessment_id' => $this->assessmentData['assessment_id'],
            'message'       => "⏳ The {$prettyType} for project '{$project}' will close in 2 days. Please make sure to complete it before it closes.",
            'url'           => "/sispa/mahasiswa/assessment/{$routePart}",  
        ];
    }

}
