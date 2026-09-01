<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class MyShiftController extends Controller
{
    public function history(Request $request): JsonResponse
    {
        $request->validate([
            'month' => 'sometimes|integer|between:1,12',
            'year' => 'sometimes|integer|between:2020,2100',
        ]);

        $userId = Auth::id();
        $month = $request->input('month');
        $year = $request->input('year');

        // For now, we'll create a mock response since there's no shift_history table yet
        // You'll need to create a shift_history migration and model later

        $user = User::with('shift')->find($userId);

        $history = [];

        if ($user->shift) {
            // Get the user's account creation date
            $effectiveDate = $user->created_at;
            $effectiveYear = $effectiveDate->year;
            $effectiveMonth = $effectiveDate->month;

            // Only show current shift if the selected month/year matches or is after the effective date
            // and the shift is still active (current)
            $currentYear = now()->year;
            $currentMonth = now()->month;

            // Check if the selected period falls within the range when this shift was active
            // The shift is active from effective_date to now
            $isInRange = false;

            if ($year && $month) {
                // Check if selected date is >= effective date AND <= current date
                $selectedDate = "{$year}-{$month}-01";
                $effectiveYearMonth = "{$effectiveYear}-{$effectiveMonth}-01";
                $currentYearMonth = "{$currentYear}-{$currentMonth}-01";

                if ($selectedDate >= $effectiveYearMonth && $selectedDate <= $currentYearMonth) {
                    $isInRange = true;
                }
            } else {
                // If no filters, show current shift
                $isInRange = true;
            }

            if ($isInRange) {
                $history[] = [
                    'id' => 1,
                    'user_id' => $userId,
                    'shift_id' => $user->shift_id,
                    'shift' => [
                        'id' => $user->shift->id,
                        'shift_type' => $user->shift->shift_type,
                        'shift_label' => $user->shift->shift_label,
                        'start_time' => $user->shift->start_time->format('g:i A'),
                        'end_time' => $user->shift->end_time->format('g:i A'),
                    ],
                    'effective_date' => $effectiveDate,
                    'end_date' => null, // null means current
                    'changed_by' => 'System',
                    'created_at' => $user->created_at,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => $history,
            'message' => 'Shift history retrieved successfully',
        ]);
    }
}
