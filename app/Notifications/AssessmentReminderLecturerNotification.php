<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class AssessmentReminderLecturerNotification extends Notification
{
    use Queueable;

    public function __construct(protected array $payload ) {
        // Constructor menerima satu array data
        // $this->payload = $payload;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toDatabase($notifiable): array
{
    $prettyType = Str::title(
        preg_replace('/([a-z])([A-Z])/', '$1 $2', $this->payload['type'])
    );

    $routeName = str_contains(strtolower($this->payload['type']), 'peer') 
        ? 'dosen.answers-peer-assessment' 
        : 'dosen.answers-self-assessment';

    return [
        'assessment_id'    => $this->payload['assessment_id'],
        'assessment_order' => $this->payload['assessment_order'],
        'batch_year'       => $this->payload['batch_year'],
        'project_name'     => $this->payload['project_name'],
        'type'             => $this->payload['type'],
        'message'          => "⏳ {$prettyType} for '{$this->payload['project_name']}' will close in 2 days.",
        'url'              => route($routeName, [
            'assessment_order' => $this->payload['assessment_order'],
            'batch_year'       => $this->payload['batch_year'],
            'project_name'     => $this->payload['project_name'],
        ]),
    ];
}



}
