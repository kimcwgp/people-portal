<?php

namespace App\Services;

use App\Models\Client;
use App\Traits\HasPagination;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ClientService
{
    use HasPagination;

    public function getPaginatedClients(Request $request): LengthAwarePaginator
    {
        $query = Client::query()->withCount('projects');

        if ($request->filled('search')) {
            $query->search($request->search, ['name', 'parent_company', 'contact_name']);
        }

        $sortColumn = $request->sort_by ?? 'name';
        $sortDirection = $request->sort_direction ?? 'asc';
        $query->sortBy($sortColumn, $sortDirection);

        $perPage = $this->getPerPageLimit('clients', $request->per_page);

        return $query->paginate($perPage);
    }

    public function createClient(array $data): Client
    {
        $client = Client::create([
            'name' => $data['name'],
            'parent_company' => $data['parent_company'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $client->loadCount('projects');

        return $client;
    }

    public function updateClient(Client $client, array $data): Client
    {
        $client->update([
            'name' => $data['name'],
            'parent_company' => $data['parent_company'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_number' => $data['contact_number'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $client->loadCount('projects');

        return $client;
    }

    public function deleteClient(Client $client): string
    {
        if ($client->projects()->count() > 0) {
            throw new \Exception(
                'Cannot delete client with existing projects. Please remove or reassign projects first.'
            );
        }

        $clientName = $client->name;
        $client->delete();

        return $clientName;
    }

    public function canDeleteClient(Client $client): bool
    {
        return $client->projects()->count() === 0;
    }

    public function getProjectsCount(Client $client): int
    {
        return $client->projects()->count();
    }
}
