<?php

namespace App\Http\Controllers;

use App\Http\Resources\TimeEntries\TimesheetResource;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A supervisor's read-only view of their direct reports' weekly timesheets.
 *
 * The personal screen lists one row per week for one person. This inverts it:
 * the week is the filter and the people are the rows, so a supervisor can see
 * at a glance who has submitted and who has not yet started.
 */
class TeamTimeEntryController extends Controller
{
    /** One row per direct report for the selected week. */
    public function index(Request $request): JsonResponse
    {
        $weekStart = Timesheet::weekStartFor(
            $request->filled('date') ? $request->string('date') : now()
        );
        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $members = User::query()
            ->where('immediate_sup_id', Auth::id())
            ->when($request->filled('user_name'), fn ($q) => $q->where('name', 'LIKE', '%'.$request->string('user_name').'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $sheets = Timesheet::with('entries')
            ->whereIn('user_id', $members->pluck('id'))
            ->where('week_start', $weekStart->toDateString())
            ->get()
            ->keyBy('user_id');

        $rows = $members->map(function (User $member) use ($sheets) {
            $sheet = $sheets->get($member->id);

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                // A member with no row has not opened the week at all, which is
                // the thing a supervisor most needs to chase.
                'status' => $sheet?->status ?? 'not started',
                'timesheet_id' => $sheet?->id,
                'total_hours' => $sheet ? $sheet->total_hours : 0.0,
                'line_count' => $sheet ? $sheet->entries->count() : 0,
                'submitted_at' => $sheet?->submitted_at?->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekEnd->toDateString(),
                'week_label' => $this->weekLabel($weekStart, $weekEnd),
                'rows' => $rows,
                'totals' => [
                    'members' => $rows->count(),
                    'submitted' => $rows->whereIn('status', ['pending', 'approved'])->count(),
                    'hours' => round($rows->sum('total_hours'), 2),
                ],
            ],
        ]);
    }

    /** One member's week grid, read only. */
    public function week(Request $request, User $user): JsonResponse
    {
        if ($user->immediate_sup_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You can only view the time entries of your direct reports.',
            ], 403);
        }

        $date = $request->filled('date') ? $request->string('date') : now()->toDateString();
        $weekStart = Timesheet::weekStartFor($date);

        $timesheet = Timesheet::with(['entries.project', 'entries.timeType', 'approver'])
            ->where('user_id', $user->id)
            ->where('week_start', $weekStart->toDateString())
            ->first();

        // Nothing filed yet: hand back an empty shell so the grid still renders
        // its day headers instead of the screen erroring.
        if (! $timesheet) {
            $timesheet = new Timesheet([
                'user_id' => $user->id,
                'week_start' => $weekStart->toDateString(),
                'week_end' => $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY)->toDateString(),
                'status' => 'draft',
            ]);
            $timesheet->setRelation('entries', collect());
        }

        return response()->json([
            'success' => true,
            'data' => [
                'member' => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email],
                'timesheet' => new TimesheetResource($timesheet),
            ],
        ]);
    }

    /**
     * CSV of the selected week: one line per timesheet row, plus a row for any
     * member who has not filed, so the export matches what the screen shows.
     */
    public function export(Request $request): StreamedResponse
    {
        $weekStart = Timesheet::weekStartFor(
            $request->filled('date') ? $request->string('date') : now()
        );
        $weekEnd = $weekStart->copy()->endOfWeek(\Carbon\Carbon::SUNDAY);

        $members = User::query()
            ->where('immediate_sup_id', Auth::id())
            ->when($request->filled('user_name'), fn ($q) => $q->where('name', 'LIKE', '%'.$request->string('user_name').'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $sheets = Timesheet::with(['entries.project', 'entries.timeType'])
            ->whereIn('user_id', $members->pluck('id'))
            ->where('week_start', $weekStart->toDateString())
            ->get()
            ->keyBy('user_id');

        $days = collect(Timesheet::DAYS);
        $rows = collect();

        foreach ($members as $member) {
            $sheet = $sheets->get($member->id);

            if (! $sheet || $sheet->entries->isEmpty()) {
                $rows->push(array_merge([
                    'Employee Name' => $member->name,
                    'Email' => $member->email,
                    'Week' => $this->weekLabel($weekStart, $weekEnd),
                    'Status' => $sheet?->status ?? 'not started',
                    'Project/Task' => '',
                    'Project Ticket' => '',
                    'Time Type' => '',
                    'Memo' => '',
                ], $days->mapWithKeys(fn ($d) => [ucfirst($d) => ''])->all(), ['Row Total' => '0.00']));
                continue;
            }

            foreach ($sheet->entries as $entry) {
                $rows->push(array_merge([
                    'Employee Name' => $member->name,
                    'Email' => $member->email,
                    'Week' => $sheet->week_label,
                    'Status' => $sheet->status,
                    'Project/Task' => $entry->project?->project_name ?? '',
                    'Project Ticket' => $entry->project_ticket ?? '',
                    'Time Type' => $entry->timeType?->name ?? '',
                    'Memo' => $entry->memo ?? '',
                ], $days->mapWithKeys(fn ($d) => [
                    ucfirst($d) => number_format((float) $entry->{"{$d}_hours"}, 2),
                ])->all(), ['Row Total' => number_format((float) $entry->row_total, 2)]));
            }
        }

        $filename = 'team_time_entries_'.$weekStart->format('Y-m-d').'_to_'.$weekEnd->format('Y-m-d').'.csv';

        return response()->stream(function () use ($rows) {
            $file = fopen('php://output', 'w');

            if ($rows->isNotEmpty()) {
                fputcsv($file, array_keys($rows->first()));
            }

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function weekLabel(\Carbon\Carbon $start, \Carbon\Carbon $end): string
    {
        return $start->month === $end->month
            ? $start->format('F j').'-'.$end->format('j, Y')
            : $start->format('F j').' - '.$end->format('F j, Y');
    }
}
