<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Notifications\AssessmentNotifications;
use App\Notifications\AssessmentReminderNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NotificationMahasiswa extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($notification) {
                return [
                    'id' => $notification->id,
                    'project_name' => $notification->data['project_name'],
                    'type' => $notification->data['type'],
                    'assessment_id' => $notification->data['assessment_id'],
                    'message' => $notification->data['message'],
                    'read_at' => $notification->read_at,
                    'created_at' => Carbon::parse($notification->created_at)->diffForHumans()
                ];
            });
        
        return Inertia::render('Mahasiswa/Notification', [
            'notifications' => $notifications,
            'unreadCount' => $user->unreadNotifications->count()
        ]);
    }

    // public function getNotifications()
    // {
    //     try {
    //         $user = Auth::user();
    //         $notifications = $user->notifications()
    //             ->orderBy('created_at', 'desc')
    //             ->get();

    //         return response()->json([
    //             'success' => true,
    //             'data' => [
    //                 'notifications' => $notifications,
    //                 'unread_count' => $user->unreadNotifications->count()
    //             ]
    //         ], 200);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error fetching notifications: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function getNotifications()
    {
        $user = Auth::user();

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($n) => [
                'id'            => $n->id,
                'type'          => $n->data['type'] ?? null,
                'project_name'  => $n->data['project_name'] ?? null,
                'assessment_id' => $n->data['assessment_id'] ?? null,
                'assessment_order' => $n->data['assessment_order'] ?? null,
                'batch_year' => $n->data['batch_year'] ?? null,
                'message'       => $n->data['message'],
                'url'           => $n->data['url'] ?? null,
                'read_at'       => $n->read_at,
                'created_at'    => $n->created_at->diffForHumans(),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count'  => $user->unreadNotifications->count(),
            ],
        ]);
    }


    public function markAsRead($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            
            $type = $notification->data['type'] ?? null;
            
            return response()->json([
                'success' => true,
                'type' => $type
            ]);
        }

        return response()->json(['success' => false], 404);
    }

    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    public function getCountNotif() {
        $user = Auth::user();
        $unreadCount = $user->notifications()->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'count' => $unreadCount
        ]);
    }

    public function testReminderNotification()
    {
        $user = Auth::user();
        $mahasiswa = Mahasiswa::where('user_id', $user->id)->first();

        $assessments = Assessment::with(['project.groups.mahasiswa'])
            ->where('is_published', true)
            ->whereDate('end_date', Carbon::today()->addDays(2))
            ->get();

        $notificationsSent = 0;

        foreach ($assessments as $assessment) {
            if (!$assessment->project) {
                continue;
            }
            
            $groups = $assessment->project->groups;

            foreach ($groups as $group) {
                $student = $group->mahasiswa;
                
                if (!$student) {
                    continue; 
                }
                
                $hasFilled = DB::table('answers')
                    ->where('question_id', $assessment->id)
                    ->where('mahasiswa_id', $student->id)
                    ->exists();

                if (!$hasFilled) {
                    $student->user->notify(new AssessmentReminderNotification($assessment));
                    $notificationsSent++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi reminder terkirim!',
            'count' => $notificationsSent
        ]);
    }
}