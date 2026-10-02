<?php

namespace App\Http\Controllers;

use App\Http\Requests\TimeEntries\StoreTimesheetEntryRequest;
use App\Http\Requests\TimeEntries\UpdateTimesheetEntryRequest;
use App\Http\Resources\TimeEntries\TimesheetEntryResource;
use App\Http\Resources\TimeEntries\TimesheetResource;
use App\Http\Resources\TimeEntries\TimeTypeResource;
use App\Models\Project;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\TimeType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TimeEntryController extends Controller
{
    /**
     * Weekly list: one row per week with its total and approval status.
     * Weeks with no timesheet yet are synthesised so the range is continuous.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $end = $request->filled('endDate')
            ? Timesheet::weekStartFor($request->string('endDate'))
            : Timesheet::weekStartFor(now());
        $start = $request->filled('startDate')
            ? Timesheet::weekStartFor($request->string('startDate'))
            : $end->copy()->subWeeks(7);

        if ($start->greaterThan($end)) {
            [$start, $end] = [$end, $start];
        }

        $saved = Timesheet::with('entries')
            ->forUser($user->id)
            ->whereBetween('week_start', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($sheet) => $sheet->week_start->toDateString());

        $weeks = [];
        for ($cursor = $end->copy(); $cursor->greaterThanOrEqualTo($start); $cursor->subWeek()) {
            $key = $cursor->toDateString();

            if ($sheet = $saved->get($key)) {
                $weeks[] = (new TimesheetResource($sheet))->resolve($request);
                continue;
            }

            // A week nobody has touched yet -- shown as an empty draft.
            $weeks[] = [
                'id' => null,
                'week_start' => $key,
                'week_end' => $cursor->copy()->endOfWeek(Carbon::SUNDAY)->toDateString(),
                'week_label' => (new Timesheet([
                    'week_start' => $cursor->copy(),
                    'week_end' => $cursor->copy()->endOfWeek(Carbon::SUNDAY),
                ]))->week_label,
                'status' => 'draft',
                'is_editable' => true,
                'total_hours' => 0,
                'submitted_at' => null,
                'approved_at' => null,
                'approver' => null,
                'rejection_note' => null,
            ];
        }

        return response()->json(['success' => true, 'data' => $weeks]);
    }

    /**
     * One week's grid. Creates the draft timesheet on first visit so the user
     * can start typing straight away.
     */
    public function week(Request $request): JsonResponse
    {
        $user = Auth::user();
        $date = $request->filled('date') ? $request->string('date') : now()->toDateString();

        $timesheet = Timesheet::forUserAndWeek($user->id, $date);
        $timesheet->load(['entries.project', 'entries.timeType', 'approver']);

        return response()->json([
            'success' => true,
            'data' => new TimesheetResource($timesheet),
        ]);
    }

    public function storeEntry(StoreTimesheetEntryRequest $request, Timesheet $timesheet): JsonResponse
    {
        if ($denied = $this->guard($timesheet)) {
            return $denied;
        }

        $entry = $timesheet->entries()->create($request->validated() + [
            'sort_order' => (int) $timesheet->entries()->max('sort_order') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Line added.',
            'data' => new TimesheetEntryResource($entry->load(['project', 'timeType'])),
        ], 201);
    }

    public function updateEntry(UpdateTimesheetEntryRequest $request, Timesheet $timesheet, TimesheetEntry $entry): JsonResponse
    {
        if ($denied = $this->guard($timesheet, $entry)) {
            return $denied;
        }

        $entry->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Line saved.',
            'data' => new TimesheetEntryResource($entry->fresh()->load(['project', 'timeType'])),
        ]);
    }

    public function destroyEntry(Timesheet $timesheet, TimesheetEntry $entry): JsonResponse
    {
        if ($denied = $this->guard($timesheet, $entry)) {
            return $denied;
        }

        $entry->delete();

        return response()->json(['success' => true, 'message' => 'Line removed.']);
    }

    /** Draft -> pending. The whole week goes to the supervisor as a unit. */
    public function submit(Timesheet $timesheet): JsonResponse
    {
        if ($denied = $this->guard($timesheet)) {
            return $denied;
        }

        if ($timesheet->entries()->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Add at least one line before submitting.',
            ], 422);
        }

        $timesheet->update([
            'status' => 'pending',
            'submitted_at' => now(),
            'approver_id' => Auth::user()->immediate_sup_id,
            'rejection_note' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Timesheet submitted for approval.',
            'data' => new TimesheetResource($timesheet->fresh()->load(['entries.project', 'entries.timeType'])),
        ]);
    }

    public function timeTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => TimeTypeResource::collection(TimeType::active()->ordered()->get()),
        ]);
    }

    /** Projects the user can log against. */
    public function projects(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => Project::orderBy('project_name')->get(['id', 'project_name']),
        ]);
    }

    /**
     * Every write goes through here: the sheet must belong to the caller and
     * still be editable, and a line must belong to that sheet.
     */
    private function guard(Timesheet $timesheet, ?TimesheetEntry $entry = null): ?JsonResponse
    {
        if ($timesheet->user_id !== Auth::id()) {
            return response()->json(['success' => false, 'message' => 'That timesheet is not yours.'], 403);
        }

        if (! $timesheet->is_editable) {
            return response()->json([
                'success' => false,
                'message' => ($timesheet->status === 'approved' ? 'An' : 'A')
                    . " {$timesheet->status} timesheet cannot be edited.",
            ], 422);
        }

        if ($entry && $entry->timesheet_id !== $timesheet->id) {
            return response()->json(['success' => false, 'message' => 'That line belongs to another week.'], 404);
        }

        return null;
    }
}
