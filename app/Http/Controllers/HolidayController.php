<?php

namespace App\Http\Controllers;

use App\Http\Requests\Holidays\StoreHolidayRequest;
use App\Http\Requests\Holidays\UpdateHolidayRequest;
use App\Http\Resources\Holidays\HolidayResource;
use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HolidayController extends Controller
{
    /**
     * Full list for the HR maintenance screen, filterable by year, calendar
     * and free-text search.
     */
    public function index(Request $request): JsonResponse
    {
        $holidays = Holiday::with('creator')
            ->when($request->filled('year'), fn ($q) => $q->whereYear('date', $request->integer('year')))
            ->when($request->filled('month'), fn ($q) => $q->whereMonth('date', $request->integer('month')))
            ->when($request->filled('calendar'), fn ($q) => $q->where('calendar', $request->string('calendar')))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%' . $request->string('search') . '%'))
            ->orderBy('date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => HolidayResource::collection($holidays),
        ]);
    }

    /**
     * The dashboard panel: one month, grouped into the two calendars.
     * Defaults to the current month so the panel rolls over on its own.
     */
    public function forMonth(Request $request): JsonResponse
    {
        $reference = Carbon::today();
        $year = $request->filled('year') ? $request->integer('year') : $reference->year;
        $month = $request->filled('month') ? $request->integer('month') : $reference->month;

        if ($month < 1 || $month > 12) {
            return response()->json([
                'success' => false,
                'message' => 'Month must be between 1 and 12.',
            ], 422);
        }

        $holidays = Holiday::active()->forMonth($year, $month)->orderBy('date')->get();
        $monthStart = Carbon::create($year, $month, 1);

        return response()->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'month_name' => $monthStart->format('F'),
                'month_label' => $monthStart->format('F Y'),
                'partners' => HolidayResource::collection(
                    $holidays->where('calendar', Holiday::CALENDAR_PARTNERS)->values()
                ),
                'people' => HolidayResource::collection(
                    $holidays->where('calendar', Holiday::CALENDAR_PEOPLE)->values()
                ),
            ],
        ]);
    }

    public function store(StoreHolidayRequest $request): JsonResponse
    {
        try {
            $holiday = Holiday::create($request->validated() + ['created_by' => Auth::id()]);

            return response()->json([
                'success' => true,
                'message' => 'Holiday created successfully.',
                'data' => new HolidayResource($holiday->load('creator')),
            ], 201);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That holiday already exists on that date for this calendar.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Create holiday failed: ' . $e->getMessage(), ['user_id' => Auth::id()]);

            return response()->json(['success' => false, 'message' => 'Failed to create holiday.'], 500);
        }
    }

    public function update(UpdateHolidayRequest $request, Holiday $holiday): JsonResponse
    {
        try {
            $holiday->update($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Holiday updated successfully.',
                'data' => new HolidayResource($holiday->fresh()->load('creator')),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'That holiday already exists on that date for this calendar.',
            ], 422);
        } catch (\Exception $e) {
            Log::error('Update holiday failed: ' . $e->getMessage(), ['holiday_id' => $holiday->id]);

            return response()->json(['success' => false, 'message' => 'Failed to update holiday.'], 500);
        }
    }

    public function destroy(Holiday $holiday): JsonResponse
    {
        try {
            $holiday->delete();

            return response()->json(['success' => true, 'message' => 'Holiday deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Delete holiday failed: ' . $e->getMessage(), ['holiday_id' => $holiday->id]);

            return response()->json(['success' => false, 'message' => 'Failed to delete holiday.'], 500);
        }
    }
}
