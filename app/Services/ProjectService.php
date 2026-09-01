<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Client;
use App\Models\User;
use App\Models\ProjectType;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    use HasPagination;

    public function getPaginatedProjects(Request $request): LengthAwarePaginator
    {
        $query = Project::with(['client', 'projectManager', 'projectType']);

        $query->filterByDateRange(
            $request->start_date,
            $request->end_date,
            'created_at'
        );

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('project_type_id')) {
            $query->where('project_type_id', $request->project_type_id);
        }

        $query->search($request->search, ['project_name', 'description']);
        $query->sortBy(
            $request->sort_by ?? 'created_at',
            $request->sort_direction ?? 'desc'
        );

        $perPage = $this->getPerPageLimit('projects', $request->per_page);
        
        return $query->paginate($perPage);
    }

    public function createProject(array $data): Project
    {
        $project = Project::create($data);
        $project->load(['client', 'projectManager', 'projectType']);
        
        return $project;
    }

    public function updateProject(Project $project, array $data): Project
    {
        $project->update($data);
        $project->load(['client', 'projectManager', 'projectType']);
        
        return $project;
    }

    public function getFormData(): array
    {
        return [
            'clients' => Client::select('id', 'name')->orderBy('name')->get(),
            'project_managers' => User::select('id', 'name', 'email')
                ->where('team_id', 6)
                ->where('status', 1) 
                ->orderBy('name')
                ->get(),
            'project_types' => ProjectType::select('id', 'name', 'description')
                ->orderBy('name')
                ->get(),
        ];
    }
}