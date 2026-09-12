<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;

class ListPropertiesTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_list';
    }

    public function getDescription(): string
    {
        return 'Search and list properties/hotels with optional filters for name, city, country, status, and tenant.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'search' => [
                    'type' => 'string',
                    'description' => 'Optional search query matching name, city, or country.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                    'description' => 'Filter by property status.',
                ],
                'tenant_id' => [
                    'type' => 'string',
                    'description' => 'Filter properties by specific tenant UUID.',
                ],
                'page' => [
                    'type' => 'integer',
                    'default' => 1,
                    'description' => 'Page number for pagination.',
                ],
                'per_page' => [
                    'type' => 'integer',
                    'default' => 10,
                    'maximum' => 50,
                    'description' => 'Number of properties per page (max 50).',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments): array
    {
        $query = Property::query();

        if (!empty($arguments['tenant_id'])) {
            $query->where('tenant_id', $arguments['tenant_id']);
        }

        if (!empty($arguments['status'])) {
            $query->where('status', $arguments['status']);
        }

        if (!empty($arguments['search'])) {
            $search = $arguments['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address_city', 'like', "%{$search}%")
                  ->orWhere('address_country', 'like', "%{$search}%");
            });
        }

        $page = max(1, (int) ($arguments['page'] ?? 1));
        $perPage = min(50, max(1, (int) ($arguments['per_page'] ?? 10)));

        $paginator = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        $properties = collect($paginator->items())->map(function (Property $property) {
            return [
                'uuid' => $property->uuid,
                'tenant_id' => $property->tenant_id,
                'name' => $property->name,
                'status' => $property->status,
                'currency' => $property->currency,
                'timezone' => $property->timezone,
                'address' => [
                    'street' => $property->address_street,
                    'city' => $property->address_city,
                    'state' => $property->address_state,
                    'country' => $property->address_country,
                ],
                'contact' => [
                    'phone' => $property->contact_phone,
                    'email' => $property->contact_email,
                ],
                'created_at' => $property->created_at?->toIso8601String(),
            ];
        });

        return [
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'last_page' => $paginator->lastPage(),
            'properties' => $properties->toArray(),
        ];
    }
}
