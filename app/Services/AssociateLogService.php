<?php

namespace App\Services;

use App\Models\AssociateLog;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{DB, Storage};

class AssociateLogService
{
    public function createLogs(array $data, array $userIds): array
    {
        DB::beginTransaction();

        try {
            $attachmentId = null;
            $attachmentName = null;

            if (isset($data['attachment']) && $data['attachment'] instanceof UploadedFile) {
                [$attachmentId, $attachmentName] = $this->storeAttachment($data['attachment']);
            }

            $createdLogs = [];

            foreach ($userIds as $userId) {
                $log = AssociateLog::create([
                    'user_id' => $userId,
                    'entry_details' => $data['entry_details'],
                    'date' => $data['date'],
                    'attachment_id' => $attachmentId,
                    'attachment_name' => $attachmentName,
                    'created_by' => auth()->id(),
                ]);

                $log->load(['user', 'creator']);
                $createdLogs[] = $log;
            }

            DB::commit();

            return $createdLogs;
        } catch (\Exception $e) {
            DB::rollBack();

            if ($attachmentId) {
                $this->deleteAttachment($attachmentId);
            }

            throw $e;
        }
    }

    public function deleteLog(AssociateLog $log): bool
    {
        DB::beginTransaction();

        try {
            if ($log->attachment_id) {
                $this->deleteAttachment($log->attachment_id);
            }

            $deleted = $log->delete();

            DB::commit();

            return $deleted;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function storeAttachment(UploadedFile $file): array
    {
        $attachmentId = time() . '_' . uniqid();
        $attachmentName = $file->getClientOriginalName();

        Storage::disk('public')->putFileAs('associate-logs', $file, $attachmentId);

        if (!Storage::disk('public')->exists('associate-logs/' . $attachmentId)) {
            throw new \Exception('File upload failed - file not found after storage');
        }

        return [$attachmentId, $attachmentName];
    }

    private function deleteAttachment(string $attachmentId): void
    {
        if (Storage::disk('public')->exists('associate-logs/' . $attachmentId)) {
            Storage::disk('public')->delete('associate-logs/' . $attachmentId);
        }
    }

    public function getAttachmentPath(AssociateLog $log): ?string
    {
        if (!$log->attachment_id) {
            return null;
        }

        $path = Storage::disk('public')->path('associate-logs/' . $log->attachment_id);

        return file_exists($path) ? $path : null;
    }
}
