<?php

namespace App\Services;

use App\Models\LeaveType;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LeaveTypeService
{
    use HasPagination;

    public function getPaginatedLeaveTypes(Request $request): LengthAwarePaginator
    {
        $query = LeaveType::query();

        // Apply filters using scope methods from Filterable trait
        $query->filterByDateRange(
            $request->start_date,
            $request->end_date,
            'created_at'
        );

        // Apply search using scope method from Filterable trait
        $query->search($request->search, ['name', 'type']);

        // Apply sorting using scope method from Sortable trait
        $query->sortBy(
            $request->sort_by ?? 'created_at',
            $request->sort_direction ?? 'desc'
        );

        $perPage = $this->getPerPageLimit('leave_types', $request->per_page);
        
        return $query->paginate($perPage);
    }

    public function createLeaveType(array $data): LeaveType
    {
        return LeaveType::create([
            'name' => $data['name'],
            'type' => $data['type'],
        ]);
    }

    public function updateLeaveType(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update([
            'name' => $data['name'],
            'type' => $data['type'],
        ]);

        return $leaveType->fresh();
    }
}