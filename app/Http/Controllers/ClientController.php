<?php

namespace App\Http\Controllers;

use App\Http\Requests\Clients\{StoreClientRequest, UpdateClientRequest};
use App\Http\Resources\Clients\ClientResource;
use App\Models\Client;
use App\Services\ClientService;
use App\Traits\HasPagination;
use Illuminate\Http\{JsonResponse, Request};

class ClientController extends Controller
{
    use HasPagination;

    public function __construct(
        private ClientService $clientService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $clients = $this->clientService->getPaginatedClients($request);

        return response()->json([
            'data' => ClientResource::collection($clients->items()),
            'meta' => $this->buildPaginationMeta($clients),
            'success' => true
        ]);
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = $this->clientService->createClient($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Client created successfully',
            'data' => new ClientResource($client)
        ], 201);
    }

    public function show(Client $client): JsonResponse
    {
        $client->loadCount('projects');

        return response()->json([
            'success' => true,
            'data' => new ClientResource($client)
        ]);
    }

    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $client = $this->clientService->updateClient($client, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Client updated successfully',
            'data' => new ClientResource($client)
        ]);
    }

    public function destroy(Client $client): JsonResponse
    {
        try {
            $clientName = $this->clientService->deleteClient($client);

            return response()->json([
                'success' => true,
                'message' => "Client '{$clientName}' deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
