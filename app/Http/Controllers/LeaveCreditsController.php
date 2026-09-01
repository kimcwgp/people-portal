<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\LeaveCredit;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeaveCreditsController extends Controller
{
    use HasPagination;
    /**
     * Display a listing of all employees with their leave credits
     */
    public function index(Request $request): JsonResponse
    {
        $year = $request->get('year', Carbon::now()->year);
        $perPage = $this->getPerPageLimit(null, $request->get('per_page'));
        
        $query = User::with(['employee'])
            ->where('status', 1) // Only active/employed users
            ->where('email', '!=', 'superadmin@example.com'); // Exclude super admin

        // Apply search using Filterable trait
        if ($request->search) {
            $query->search($request->search, ['name', 'email']);
        }

        // Filter by regularization status
        if ($request->filter === 'regular') {
            $query->whereHas('employee', function($q) {
                $q->regular();
            });
        } elseif ($request->filter === 'probationary') {
            $query->where(function($q) {
                $q->whereDoesntHave('employee')
                  ->orWhereHas('employee', function($subQ) {
                      $subQ->probationary();
                  });
            });
        }

        // Apply sorting using Sortable trait
        $sortColumn = $request->get('sort_by', 'name');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->sortBy($sortColumn, $sortDirection);

        $users = $query->paginate($perPage);

        $data = $users->getCollection()->map(function($user) use ($year) {
            $credits = LeaveCredit::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'year' => $year
                ],
                [
                    'vl_credits' => 0,
                    'vl_used' => 0,
                    'vl_pending' => 0,
                    'sl_credits' => 0,
                    'sl_used' => 0,
                    'sl_pending' => 0,
                    'vl_carried_over' => 0,
                    'sl_carried_over' => 0,
                ]
            );

            $isRegular = $user->employee ? $user->employee->isRegular() : false;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee->employee_id ?? 'N/A',
                'hire_date' => $user->employee->hire_date ?? null,
                'regularization_date' => $user->employee->regularization_date ?? null,
                'is_regular' => $isRegular,
                'credits' => [
                    'id' => $credits->id,
                    'year' => $credits->year,
                    'vl_credits' => (float) $credits->vl_credits,
                    'vl_used' => (float) $credits->vl_used,
                    'vl_pending' => (float) $credits->vl_pending,
                    'vl_remaining' => (float) $credits->vl_remaining,
                    'vl_carried_over' => (float) $credits->vl_carried_over,
                    'vl_carried_over_used' => (float) $credits->vl_carried_over_used,
                    'vl_carried_over_remaining' => (float) $credits->vl_carried_over_remaining,
                    'vl_current_year_remaining' => (float) $credits->vl_current_year_remaining,
                    'sl_credits' => (float) $credits->sl_credits,
                    'sl_used' => (float) $credits->sl_used,
                    'sl_pending' => (float) $credits->sl_pending,
                    'sl_remaining' => (float) $credits->sl_remaining,
                    'sl_carried_over' => (float) $credits->sl_carried_over,
                ],
            ];
        });

        $users->setCollection($data);

        return response()->json([
            'success' => true,
            ...$this->buildPaginationResponse($users)
        ]);
    }

    /**
     * Update leave credits for a specific user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'year' => 'required|integer',
            'vl_credits' => 'required|numeric|min:0|max:15',
            'sl_credits' => 'required|numeric|min:0|max:15',
            'vl_used' => 'nullable|numeric|min:0',
            'sl_used' => 'nullable|numeric|min:0',
            'vl_carried_over' => 'nullable|numeric|min:0|max:5',
            'vl_carried_over_used' => 'nullable|numeric|min:0',
            'sl_carried_over' => 'nullable|numeric|min:0|max:5',
        ]);

        $credits = LeaveCredit::updateOrCreate(
            [
                'user_id' => $user->id,
                'year' => $request->year,
            ],
            [
                'vl_credits' => $request->vl_credits,
                'vl_used' => $request->vl_used ?? 0,
                'sl_credits' => $request->sl_credits,
                'sl_used' => $request->sl_used ?? 0,
                'vl_carried_over' => $request->vl_carried_over ?? 0,
                'vl_carried_over_used' => $request->vl_carried_over_used ?? 0,
                'sl_carried_over' => $request->sl_carried_over ?? 0,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Leave credits updated successfully',
            'data' => $credits,
        ]);
    }

    /**
     * Reset pending credits to 0 (useful when cleaning up)
     */
    public function resetPending(User $user): JsonResponse
    {
        $year = Carbon::now()->year;
        
        $credits = LeaveCredit::where('user_id', $user->id)
            ->where('year', $year)
            ->first();

        if (!$credits) {
            return response()->json([
                'success' => false,
                'message' => 'No credits found for this user',
            ], 404);
        }

        $credits->vl_pending = 0;
        $credits->sl_pending = 0;
        $credits->save();

        return response()->json([
            'success' => true,
            'message' => 'Pending credits reset successfully',
            'data' => $credits,
        ]);
    }

    public function export(Request $request)
    {
        $year = $request->get('year', Carbon::now()->year);
        $search = $request->get('search');
        $filter = $request->get('filter');
        
        $query = User::with(['employee'])
            ->where('status', 1)
            ->where('email', '!=', 'superadmin@example.com');

        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%");
            });
        }

        if ($filter === 'regular') {
            $query->whereHas('employee', function($q) {
                $q->regular();
            });
        } elseif ($filter === 'probationary') {
            $query->where(function($q) {
                $q->whereDoesntHave('employee')
                  ->orWhereHas('employee', function($subQ) {
                      $subQ->probationary();
                  });
            });
        }

        $users = $query->orderBy('name')->get();

        $csvData = [];
        $csvData[] = [
            'Name',
            'Email',
            'Employee ID',
            'Hire Date',
            'Status',
            'VL Credits',
            'VL Used',
            'VL Carried Over',
            'VL Carried Over Used',
            'SL Credits',
            'SL Used',
            'Birthday Leave',
        ];

        foreach ($users as $user) {
            $credits = LeaveCredit::where('user_id', $user->id)
                ->where('year', $year)
                ->first();

            $isRegular = $user->employee ? $user->employee->isRegular() : false;
            $status = $isRegular ? 'Regular' : 'Probationary';

            $csvData[] = [
                $user->name,
                $user->email,
                $user->employee->employee_id ?? 'N/A',
                $user->employee->hire_date ?? '',
                $status,
                $credits->vl_credits ?? 0,
                $credits->vl_used ?? 0,
                $credits->vl_carried_over ?? 0,
                $credits->vl_carried_over_used ?? 0,
                $credits->sl_credits ?? 0,
                $credits->sl_used ?? 0,
                $credits->birthday_leave_count ?? 1,
            ];
        }

        $filename = "leave_credits_{$year}_" . date('Y-m-d_His') . ".csv";
        
        $handle = fopen('php://temp', 'r+');
        foreach ($csvData as $row) {
            fputcsv($handle, $row);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return response($csv)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
            'year' => 'required|integer|min:2020|max:2100',
        ]);

        $year = $request->year;
        $file = $request->file('file');
        
        $csv = array_map('str_getcsv', file($file->getRealPath()));
        $header = array_shift($csv);

        $errors = [];
        $imported = 0;
        $skipped = 0;

        foreach ($csv as $index => $row) {
            $rowNumber = $index + 2;
            
            if (count($row) < 11) {
                $errors[] = "Row {$rowNumber}: Invalid format (missing columns)";
                $skipped++;
                continue;
            }

            $email = trim($row[1]);
            $user = User::where('email', $email)->first();

            if (!$user) {
                $errors[] = "Row {$rowNumber}: User not found with email '{$email}'";
                $skipped++;
                continue;
            }

            $validator = Validator::make([
                'vl_credits' => $row[4],
                'vl_used' => $row[5],
                'vl_carried_over' => $row[6],
                'vl_carried_over_used' => $row[7],
                'sl_credits' => $row[8],
                'sl_used' => $row[9],
                'birthday_leave_count' => $row[10],
            ], [
                'vl_credits' => 'numeric|min:0|max:15',
                'vl_used' => 'numeric|min:0',
                'vl_carried_over' => 'numeric|min:0|max:10',
                'vl_carried_over_used' => 'numeric|min:0',
                'sl_credits' => 'numeric|min:0|max:15',
                'sl_used' => 'numeric|min:0',
                'birthday_leave_count' => 'numeric|min:0|max:1',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNumber}: " . implode(', ', $validator->errors()->all());
                $skipped++;
                continue;
            }

            try {
                LeaveCredit::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'year' => $year,
                    ],
                    [
                        'vl_credits' => (float) $row[4],
                        'vl_used' => (float) $row[5],
                        'vl_carried_over' => (float) $row[6],
                        'vl_carried_over_used' => (float) $row[7],
                        'sl_credits' => (float) $row[8],
                        'sl_used' => (float) $row[9],
                        'birthday_leave_count' => (float) $row[10],
                    ]
                );
                $imported++;
            } catch (\Exception $e) {
                $errors[] = "Row {$rowNumber}: {$e->getMessage()}";
                $skipped++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Import completed: {$imported} imported, {$skipped} skipped",
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ]);
    }
}
