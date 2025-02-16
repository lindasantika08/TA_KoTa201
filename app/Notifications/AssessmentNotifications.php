<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Carbon\Carbon;

class AssessmentNotifications extends Notification
{
    protected $assessmentData;

    public function __construct($assessmentData)
    {
        $this->assessmentData = $assessmentData;
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $endDate = Carbon::parse($this->assessmentData['end_date'])->format('d F Y H:i');
        
        $route = match (strtolower($this->assessmentData['type'])) {
            'self assessment' => route('mahasiswa.assessment.self'),
            'peer assessment' => route('mahasiswa.assessment.peer'),
            default => route('mahasiswa.dashboard')
        };

        $assessmentType = match (strtolower($this->assessmentData['type'])) {
            'self assessment' => 'Self Assessment',
            'peer assessment' => 'Peer Assessment',
            default => $this->assessmentData['type']
        };

        return (new MailMessage)
            ->subject("New {$assessmentType} Available")
            ->greeting("Hello {$notifiable->name}!")
            ->line("You have a new {$assessmentType} to complete:")
            ->line("Project: {$this->assessmentData['project_name']}")
            ->line("Due date: {$endDate}")
            ->action('Go to Assessment', $route)
            ->line('Please complete the assessment before the due date.')
            ->line('Thank you for your participation!');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'message' => "New {$this->assessmentData['type']} assessment available",
            'assessment_id' => $this->assessmentData['assessment_id'],
            'project_name' => $this->assessmentData['project_name'],
            'type' => $this->assessmentData['type'],
            'end_date' => $this->assessmentData['end_date']
        ];
    }
}