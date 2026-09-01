<?php
namespace App\Http\Controllers;

use App\Http\Requests\Hr\StoreHrAnnouncementRequest;
use App\Http\Requests\Hr\UpdateHrAnnouncementRequest;
use App\Http\Resources\Hr\HrAnnouncementResource;
use App\Http\Resources\Hr\HrAnnouncementCollection;
use App\Models\HrAnnouncement;
use App\Services\HrAnnouncementService;
use Illuminate\Http\Request;

class HrAnnouncementController extends Controller
{
    public function __construct(
        protected HrAnnouncementService $announcementService
    ) {}

    public function index()
    {
        $announcements = HrAnnouncement::active()
            ->latest()
            ->with('user:id,name')
            ->get();

        return new HrAnnouncementCollection($announcements);
    }

    public function store(StoreHrAnnouncementRequest $request)
    {
        $announcement = $this->announcementService->create($request->validated());

        return new HrAnnouncementResource($announcement->load('user:id,name'));
    }

    public function update(UpdateHrAnnouncementRequest $request, $announcement)
    {
        if (is_numeric($announcement)) {
            $announcement = HrAnnouncement::findOrFail($announcement);
        }

        $announcement = $this->announcementService->update($announcement, $request->validated());

        return new HrAnnouncementResource($announcement);
    }

    public function destroy($announcement)
    {
        if (is_numeric($announcement)) {
            $announcement = HrAnnouncement::findOrFail($announcement);
        }

        $this->announcementService->delete($announcement);

        return response()->json(['message' => 'Announcement deleted successfully']);
    }

}