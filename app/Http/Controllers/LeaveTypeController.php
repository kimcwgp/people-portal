<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveTypes\StoreLeaveTypeRequest;
use App\Http\Requests\LeaveTypes\UpdateLeaveTypeRequest;
use App\Http\Resources\LeaveTypes\LeaveTypeResource;
use App\Models\LeaveType;
use App\Services\LeaveTypeService;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeaveTypeController extends Controller
{
    use HasPagination;

    public function __construct(
        private LeaveTypeService $leaveTypeService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $leaveTypes = $this->leaveTypeService->getPaginatedLeaveTypes($request);
        $paginationResponse = $this->buildPaginationResponse($leaveTypes);
        $paginationResponse['data'] = LeaveTypeResource::collection($leaveTypes->items());

        return response()->json($paginationResponse);
    }

    public function store(StoreLeaveTypeRequest $request): JsonResponse
    {
        $leaveType = $this->leaveTypeService->createLeaveType($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Leave type created successfully',
            'data' => new LeaveTypeResource($leaveType)
        ], 201);
    }

    public function show(LeaveType $leaveType): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => new LeaveTypeResource($leaveType)
        ]);
    }

    public function update(UpdateLeaveTypeRequest $request, LeaveType $leaveType): JsonResponse
    {
        $leaveType = $this->leaveTypeService->updateLeaveType($leaveType, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Leave type updated successfully',
            'data' => new LeaveTypeResource($leaveType)
        ]);
    }

    public function destroy(LeaveType $leaveType): JsonResponse
    {
        if ($leaveType->leaves()->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Cannot delete leave type that is being used'
            ], 422);
        }

        $leaveType->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Leave type deleted successfully'
        ]);
    }
}