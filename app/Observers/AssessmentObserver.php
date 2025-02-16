<?php

namespace App\Observers;

use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Notifications\AssessmentNotifications;

class AssessmentObserver
{
    public function updated(Assessment $assessment)
    {
        // Cek apakah is_published berubah dari false ke true
        if ($assessment->isDirty('is_published') && $assessment->is_published) {
            try {
                // Ambil semua mahasiswa
                $mahasiswa = Mahasiswa::all();
                
                // Load relasi project jika belum
                if (!$assessment->relationLoaded('project')) {
                    $assessment->load('project');
                }
                
                // Siapkan data notifikasi
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
                
                // Kirim notifikasi ke setiap mahasiswa
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