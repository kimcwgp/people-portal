<?php

namespace App\Http\Controllers;

use App\Models\{User, Attendance};
use App\Http\Resources\ActiveAssociate\ActiveAssociateResource;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ActiveAssociatesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $today = Carbon::today();

            $activeAssociates = User::with([
                'team',
                'todayAttendance' => function ($query) use ($today) {
                    $query->whereDate('attendance_date', $today)
                          ->whereNotNull('time_in')
                          ->whereNull('time_out');
                },
                'todayAttendance.activeBreak' => function ($query) {
                    $query->whereNull('ended_at');
                }
            ])
            ->whereHas('todayAttendance', function ($query) use ($today) {
                $query->whereDate('attendance_date', $today)
                      ->whereNotNull('time_in')
                      ->whereNull('time_out');
            })
            ->orderBy('name')
            ->get();

            if ($request->filled('search')) {
                $search = $request->search;
                $activeAssociates = $activeAssociates->filter(function ($user) use ($search) {
                    return stripos($user->name, $search) !== false ||
                           stripos($user->email, $search) !== false ||
                           ($user->team && stripos($user->team->name, $search) !== false);
                });
            }

            return response()->json([
                'success' => true,
                'data' => ActiveAssociateResource::collection($activeAssociates),
                'summary' => [
                    'total_active' => $activeAssociates->count(),
                    'on_break' => $activeAssociates->filter(function ($user) {
                        return $user->todayAttendance && $user->todayAttendance->activeBreak;
                    })->count(),
                    'working' => $activeAssociates->filter(function ($user) {
                        return $user->todayAttendance && !$user->todayAttendance->activeBreak;
                    })->count(),
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to load active associates: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to load active associates',
                'data' => []
            ], 500);
        }
    }
}