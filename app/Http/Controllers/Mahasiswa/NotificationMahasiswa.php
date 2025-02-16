<?php

namespace App\Http\Controllers\Mahasiswa;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use App\Models\Assessment;
use App\Models\Mahasiswa;
use App\Notifications\AssessmentNotifications;
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

    public function getNotifications()
{
    try {
        $user = Auth::user();
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

            \Log::info('Project data:', [
                'project' => $assessment->project,
                'notification_data' => $notificationData
            ]);
            
            $exists = $user->notifications()
                ->where('type', AssessmentNotifications::class)
                ->whereJsonContains('data->assessment_id', $assessment->id)
                ->exists();
            
            if (!$exists) {
                $user->notify(new AssessmentNotifications($notificationData));
            }
        }

        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => $notifications,
                'unread_count' => $user->unreadNotifications->count()
            ]
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error fetching notifications: ' . $e->getMessage()
        ], 500);
    }
}

    public function markAsRead($id)
    {
        $notification = Auth::user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
            
            // Data sudah dalam bentuk array, tidak perlu json_decode
            $type = $notification->data['type'];
            
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
}