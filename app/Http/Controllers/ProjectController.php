<?php

namespace App\Http\Controllers;

use App\Http\Requests\Projects\StoreProjectRequest;
use App\Http\Requests\Projects\UpdateProjectRequest;
use App\Http\Resources\Projects\ProjectResource;
use App\Models\Project;
use App\Services\ProjectService;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProjectController extends Controller
{
    use HasPagination;

    public function __construct(
        private ProjectService $projectService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $projects = $this->projectService->getPaginatedProjects($request);
        $paginationResponse = $this->buildPaginationResponse($projects);
        $paginationResponse['data'] = ProjectResource::collection($projects->items());

        return response()->json($paginationResponse);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject($request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Project created successfully',
            'data' => new ProjectResource($project)
        ], 201);
    }

    public function show(Project $project): JsonResponse
    {
        $project->load(['client', 'projectManager', 'projectType']);

        return response()->json([
            'status' => 'success',
            'data' => new ProjectResource($project)
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project): JsonResponse
    {
        $project = $this->projectService->updateProject($project, $request->validated());

        return response()->json([
            'status' => 'success',
            'message' => 'Project updated successfully',
            'data' => new ProjectResource($project)
        ]);
    }

    public function destroy(Project $project): JsonResponse
    {
        $project->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Project deleted successfully'
        ]);
    }

    public function getFormData(): JsonResponse
    {
        $formData = $this->projectService->getFormData();

        return response()->json([
            'status' => 'success',
            'data' => $formData
        ]);
    }
}