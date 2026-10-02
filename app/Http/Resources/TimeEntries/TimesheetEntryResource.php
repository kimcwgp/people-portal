<?php

namespace App\Http\Resources\TimeEntries;

use Illuminate\Http\Resources\Json\JsonResource;

class TimesheetEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'project_id' => $this->project_id,
            'project_name' => $this->project?->project_name,
            'project_ticket' => $this->project_ticket,
            'time_type_id' => $this->time_type_id,
            'time_type' => $this->timeType ? [
                'id' => $this->timeType->id,
                'name' => $this->timeType->name,
                'tint' => $this->timeType->tint,
            ] : null,
            'memo' => $this->memo,
            'mon_hours' => (float) $this->mon_hours,
            'tue_hours' => (float) $this->tue_hours,
            'wed_hours' => (float) $this->wed_hours,
            'thu_hours' => (float) $this->thu_hours,
            'fri_hours' => (float) $this->fri_hours,
            'sat_hours' => (float) $this->sat_hours,
            'sun_hours' => (float) $this->sun_hours,
            'row_total' => $this->row_total,
            'sort_order' => $this->sort_order,
        ];
    }
}
