<?php

namespace App\Observers;

use App\Models\Assessment;
use App\Models\Mahasiswa;
use Illuminate\Support\Facades\DB;
use App\Notifications\AssessmentNotifications;
use Illuminate\Support\Facades\Log;

class AssessmentObserver
{
    public function updated(Assessment $assessment)
    {
        // dd('Assessment updated: ' . $assessment->id);   
        if (
                $assessment->isDirty('is_published') &&
                $assessment->getOriginal('is_published') == false &&
                $assessment->is_published == true
            )
            {
            try {
                $mahasiswa = Mahasiswa::all();

                // dd($mahasiswa->count());

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
                        'assessment_order' => $assessment->project_id . '_' . strtolower($assessment->type),
                        'project_name' => $assessment->project->project_name ?? 'Unknown Project',
                        'type' => $assessment->type,
                        'end_date' => $assessment->end_date,
                    ];
                
                    // Get all mahasiswa in the group at once
                    $mahasiswaIds = DB::table('groups')
                        ->where('project_id', $assessment->project_id)
                        ->pluck('mahasiswa_id')
                        ->toArray();
                
                    // Eager load mahasiswa and their users in one query
                    $mahasiswas = Mahasiswa::with('user')
                        ->whereIn('id', $mahasiswaIds)
                        ->get();
                
                    foreach ($mahasiswas as $mhs) {
                        $user = $mhs->user;
                        Log::info('count sent mhs: ' . $mahasiswas->count());
                        if ($user) {
                            $exists = $user->notifications()
                                ->where('type', AssessmentNotifications::class)
                                ->whereJsonContains('data->assessment_order', $assessment->project_id . '_' . strtolower($assessment->type))
                                ->exists();
                
                            if (!$exists) {
                                $user->notify(new AssessmentNotifications($notificationData));
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