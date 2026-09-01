<?php

namespace App\Http\Controllers;

use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, Log};
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Models\AssociateLog;
use App\Http\Resources\Profile\ProfileResource;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(
        private ProfileService $profileService
    ) {}

    public function show(): JsonResponse
    {
        try {
            $user = Auth::user();
            $userWithProfile = $this->profileService->getUserProfile($user);
            
            return response()->json([
                'success' => true,
                'data' => new ProfileResource($userWithProfile)
            ]);

        } catch (\Exception $e) {
            Log::error('Profile fetch error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile data'
            ], 500);
        }
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();
            $validated = $request->validated();

            if (isset($validated['associate_log']) && !$this->profileService->canCreateAssociateLog($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to create associate logs'
                ], 403);
            }

            $updatedUser = $this->profileService->updateProfile($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => new ProfileResource($updatedUser)
            ]);

        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile'
            ], 500);
        }
    }

    public function downloadAttachment(AssociateLog $associateLog): JsonResponse|BinaryFileResponse
    {
        try {
            $user = Auth::user();

            if (!$this->profileService->canDownloadAttachment($user, $associateLog)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to download this attachment'
                ], 403);
            }

            $filePath = $this->profileService->getAttachmentPath($associateLog);

            if (!$filePath) {
                return response()->json([
                    'success' => false,
                    'message' => 'File not found'
                ], 404);
            }

            return response()->download($filePath, $associateLog->attachment_name);

        } catch (\Exception $e) {
            Log::error('File download error', [
                'user_id' => Auth::id(),
                'associate_log_id' => $associateLog->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to download file'
            ], 500);
        }
    }
}
