<?php

namespace App\Http\Controllers;

use App\Models\{User, AssociateLog};
use App\Http\Requests\AssociateLog\CreateAssociateLogRequest;
use App\Http\Resources\AssociateLog\AssociateLogResource;
use App\Services\AssociateLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AssociateLogController extends Controller
{
    public function __construct(
        private AssociateLogService $associateLogService
    ) {}

    public function index(Request $request)
    {
        try {
            $query = AssociateLog::with(['user', 'creator']);

            if ($request->filled('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->filled('search')) {
                $query->where('entry_details', 'like', '%' . $request->search . '%');
            }

            $logs = $query->orderBy('date', 'desc')
                         ->orderBy('created_at', 'desc')
                         ->paginate(15);

            return AssociateLogResource::collection($logs);

        } catch (\Exception $e) {
            Log::error('Failed to load associate logs: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load associate logs',
                'data' => []
            ], 500);
        }
    }

    public function getUsers()
    {
        try {
            $users = User::select('id', 'name', 'email')
                        ->orderBy('name')
                        ->get();

            return response()->json([
                'success' => true,
                'data' => $users
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to load users: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load users',
                'data' => []
            ], 500);
        }
    }

    public function store(CreateAssociateLogRequest $request)
    {
        try {
            $validated = $request->validated();

            if (is_string($request->input('user_ids'))) {
                $validated['user_ids'] = json_decode($request->input('user_ids'), true);
            }

            $createdLogs = $this->associateLogService->createLogs(
                $validated,
                $validated['user_ids']
            );

            $userCount = count($createdLogs);
            $message = $userCount === 1
                ? 'Associate log created successfully'
                : "Associate log created successfully for {$userCount} users";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => AssociateLogResource::collection($createdLogs),
                'count' => $userCount
            ], 201);

        } catch (\Exception $e) {
            Log::error('Associate log creation error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create associate log: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(AssociateLog $associateLog)
    {
        try {
            $this->associateLogService->deleteLog($associateLog);

            return response()->json([
                'success' => true,
                'message' => 'Associate log deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Associate log deletion error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete associate log: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadAttachment(AssociateLog $associateLog)
    {
        try {
            if (!$associateLog->hasAttachment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No attachment found'
                ], 404);
            }

            $filePath = $this->associateLogService->getAttachmentPath($associateLog);

            if (!$filePath) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->download($filePath, $associateLog->attachment_name);

        } catch (\Exception $e) {
            Log::error('File download error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to download file'
            ], 500);
        }
    }
}
