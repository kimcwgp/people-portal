<?php

namespace App\Services;

use App\Models\HrAnnouncement;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class HrAnnouncementService
{
    public function create(array $data): HrAnnouncement
    {
        $userId = auth()->id() ?? $this->getDefaultUserId();
        
        $announcementData = [
            'title' => $data['title'] ?? 'Company Update',
            'user_id' => $userId,
            'is_active' => true
        ];

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            $announcementData['image'] = $this->storeImage($data['image']);
        }

        return HrAnnouncement::create($announcementData);
    }

    public function update(HrAnnouncement $announcement, array $data): HrAnnouncement
    {
        $updateData = [
            'title' => $data['title'] ?? 'Company Update',
        ];

        if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
            // Delete old image if exists
            if ($announcement->image) {
                $this->deleteImage($announcement->image);
            }
            $updateData['image'] = $this->storeImage($data['image']);
        }

        $announcement->update($updateData);
        return $announcement->fresh('user');
    }

    public function delete(HrAnnouncement $announcement): bool
    {
        // Delete associated image
        if ($announcement->image) {
            $this->deleteImage($announcement->image);
        }
        
        return $announcement->delete();
    }

    private function storeImage(UploadedFile $image): string
    {
        $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
        return $image->storeAs('hr-announcements', $imageName, 'public');
    }

    private function deleteImage(string $imagePath): void
    {
        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    private function getDefaultUserId(): int
    {
        $user = User::firstOrCreate(
            ['email' => 'hr@company.com'],
            [
                'name' => 'HR Department',
                'password' => bcrypt('password'),
                'email_verified_at' => now()
            ]
        );
        
        return $user->id;
    }
}