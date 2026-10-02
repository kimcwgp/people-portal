<?php

namespace App\Http\Resources\TimeEntries;

use App\Models\Timesheet;
use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'week_start' => $this->week_start?->toDateString(),
            'week_end' => $this->week_end?->toDateString(),
            'week_label' => $this->week_label,
            'status' => $this->status,
            'is_editable' => $this->is_editable,
            'total_hours' => $this->total_hours,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'approver' => $this->approver?->name,
            'rejection_note' => $this->rejection_note,
            // The seven day headers of the grid, with their dates and totals.
            'days' => $this->when($this->relationLoaded('entries'), fn () => $this->buildDays()),
            'entries' => TimesheetEntryResource::collection($this->whenLoaded('entries')),
        ];
    }

    private function buildDays(): array
    {
        $totals = $this->dailyTotals();

        return collect(Timesheet::DAYS)->map(function ($day, $index) use ($totals) {
            $date = $this->week_start->copy()->addDays($index);

            return [
                'key' => $day,
                'label' => $date->format('D'),
                'day_of_month' => $date->day,
                'date' => $date->toDateString(),
                'is_weekend' => $date->isWeekend(),
                'total' => $totals[$day],
            ];
        })->all();
    }
}
