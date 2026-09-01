<?php

namespace App\Services;

use App\Models\Shift;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\{DB, Cache};

class ShiftService
{
    use HasPagination;

    public function getPaginatedShifts(Request $request): LengthAwarePaginator
    {
        $query = Shift::query()->withCount('users');

        $this->applyFilters($query, $request);

        $query->sortBy(
            $request->input('sort_by', 'shift_type'),
            $request->input('sort_direction', 'asc')
        );

        $perPage = $this->getPerPageLimit('shifts', $request->input('per_page', 10));

        return $query->paginate($perPage);
    }

    private function applyFilters($query, Request $request)
    {
        if ($request->filled('shift_type')) {
            $query->filterByShiftType($request->input('shift_type'));
        }

        if ($request->filled('search')) {
            $query->where('shift_type', 'LIKE', "%{$request->input('search')}%");
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            $query->filterByDateRange(
                $request->input('start_date'),
                $request->input('end_date'),
                'created_at'
            );
        }

        return $query;
    }

    public function getShiftStats(): array
    {
        $stats = Shift::select('shift_type', DB::raw('COUNT(*) as count'))
            ->groupBy('shift_type')
            ->pluck('count', 'shift_type')
            ->toArray();

        return [
            'total' => Shift::count(),
            'day_shifts' => $stats['day'] ?? 0,
            'night_shifts' => $stats['night'] ?? 0,
        ];
    }

    public function createShift(array $data): Shift
    {
        $shift = Shift::create([
            'shift_type' => $data['shift_type'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        Cache::forget('user_filter_options');

        return $shift;
    }

    public function updateShift(Shift $shift, array $data): Shift
    {
        $shift->update([
            'shift_type' => $data['shift_type'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ]);

        $shift->loadCount('users');

        Cache::forget('user_filter_options');

        return $shift;
    }

    public function deleteShift(Shift $shift): void
    {
        $usersCount = $shift->users()->count();

        if ($usersCount > 0) {
            throw new \Exception(
                "Cannot delete shift that has {$usersCount} user(s) assigned. Please reassign them first."
            );
        }

        $shift->delete();

        Cache::forget('user_filter_options');
    }

    public function canDeleteShift(Shift $shift): bool
    {
        return $shift->users()->count() === 0;
    }

    public function getUsersCount(Shift $shift): int
    {
        return $shift->users()->count();
    }
}
