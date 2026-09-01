<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class EmployeeManagementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $perPage = $request->input('per_page', 25);
            $search = $request->input('search', '');
            $statusFilter = $request->input('status', '');

            $query = User::with(['employee'])->whereHas('employee');

            if ($search) {
                $query->where('name', 'like', "%{$search}%");
            }

            if ($statusFilter) {
                $query->whereHas('employee', function ($q) use ($statusFilter) {
                    $q->where('employment_status', ucfirst($statusFilter));
                });
            }

            $employees = $query->paginate($perPage);

            $employees->getCollection()->transform(function ($user) {
                return $this->transformEmployeeData($user);
            });

            return response()->json($employees);
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to fetch employees', $e);
        }
    }

    public function updateRegularization(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'hire_date' => 'required|date',
            'employment_status' => 'required|in:Probationary,Regular,Resigned',
            'regularization_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $employee = $this->getEmployeeOrFail($user);

            $employee->hire_date = $request->hire_date;
            $employee->employment_status = $request->employment_status;

            $regularizationDate = $request->regularization_date 
                ?? $this->calculateRegularizationDate($request->employment_status, $request->hire_date);

            if ($regularizationDate) {
                $employee->regularization_date = $regularizationDate;
            }

            $employee->save();
            DB::commit();

            return $this->successResponse(
                'Employee status updated successfully',
                $this->transformEmployeeData($user)
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Failed to update employee status', $e);
        }
    }

    public function removeRegularization(User $user)
    {
        try {
            $employee = $this->getEmployeeOrFail($user);
            $employee->employment_status = 'Probationary';
            $employee->save();

            return $this->successResponse(
                'Employee status changed to Probationary',
                [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'employment_status' => 'Probationary',
                ]
            );
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update employment status', $e);
        }
    }

    private function transformEmployeeData(User $user): array
    {
        $employee = $user->employee;

        return [
            'id' => $user->id,
            'name' => $user->name,
            'employee_id' => $user->employee_id,
            'employment_status' => $employee->employment_status ?? 'N/A',
            'date_hired' => $employee->hire_date?->format('Y-m-d'),
            'regularization_date' => $employee->regularization_date?->format('Y-m-d'),
        ];
    }

    private function getEmployeeOrFail(User $user): Employee
    {
        if (!$user->employee) {
            throw new \Exception('Employee record not found');
        }
        return $user->employee;
    }

    private function calculateRegularizationDate(?string $status, $hireDate): ?string
    {
        if ($status === 'Regular' && $hireDate) {
            return Carbon::parse($hireDate)->addMonths(6)->format('Y-m-d');
        }
        return null;
    }

    private function successResponse(string $message, array $data): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function errorResponse(string $message, \Exception $e): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $e->getMessage(),
        ], 500);
    }
}
