<?php

namespace App\Http\Controllers;

use App\Http\Requests\Shifts\{StoreShiftRequest, UpdateShiftRequest};
use App\Http\Resources\Shifts\ShiftResource;
use App\Models\Shift;
use App\Services\ShiftService;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};

class ShiftController extends Controller
{
    use HasPagination;

    public function __construct(
        private ShiftService $shiftService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $shifts = $this->shiftService->getPaginatedShifts($request);
        $stats = $this->shiftService->getShiftStats();

        return response()->json([
            'success' => true,
            'shifts' => [
                ...$this->buildPaginationResponse($shifts),
                'data' => ShiftResource::collection($shifts->items()),
            ],
            'stats' => $stats
        ]);
    }

    public function store(StoreShiftRequest $request): JsonResponse
    {
        $shift = $this->shiftService->createShift($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Shift created successfully',
            'shift' => new ShiftResource($shift)
        ], 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        $shift->loadCount('users');

        return response()->json([
            'success' => true,
            'shift' => new ShiftResource($shift)
        ]);
    }

    public function update(UpdateShiftRequest $request, Shift $shift): JsonResponse
    {
        $shift = $this->shiftService->updateShift($shift, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Shift updated successfully',
            'shift' => new ShiftResource($shift)
        ]);
    }

    public function destroy(Shift $shift): JsonResponse
    {
        try {
            $this->shiftService->deleteShift($shift);

            return response()->json([
                'success' => true,
                'message' => 'Shift deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
