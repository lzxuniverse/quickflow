<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;
use App\Models\Tenant;

class CreatePropertyTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_create';
    }

    public function getDescription(): string
    {
        return 'Create a new property/hotel in QuickFlow.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tenant_id' => [
                    'type' => 'string',
                    'description' => 'The tenant UUID owning this property.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name of the property.',
                ],
                'currency' => [
                    'type' => 'string',
                    'description' => 'Currency code (e.g. USD, EUR, VND). Default USD.',
                    'default' => 'USD',
                ],
                'timezone' => [
                    'type' => 'string',
                    'description' => 'Timezone string (e.g. UTC, Asia/Ho_Chi_Minh). Default UTC.',
                    'default' => 'UTC',
                ],
                'address_street' => [
                    'type' => 'string',
                    'description' => 'Street address.',
                ],
                'address_city' => [
                    'type' => 'string',
                    'description' => 'City name.',
                ],
                'address_state' => [
                    'type' => 'string',
                    'description' => 'State or province.',
                ],
                'address_postal_code' => [
                    'type' => 'string',
                    'description' => 'Postal / ZIP code.',
                ],
                'address_country' => [
                    'type' => 'string',
                    'description' => 'Country name.',
                ],
                'lat' => [
                    'type' => 'number',
                    'description' => 'Latitude (-90 to 90).',
                ],
                'lng' => [
                    'type' => 'number',
                    'description' => 'Longitude (-180 to 180).',
                ],
                'contact_phone' => [
                    'type' => 'string',
                    'description' => 'Contact phone number.',
                ],
                'contact_email' => [
                    'type' => 'string',
                    'description' => 'Contact email address.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                    'default' => 'active',
                    'description' => 'Initial property status.',
                ],
            ],
            'required' => ['tenant_id', 'name'],
        ];
    }

    public function execute(array $arguments): array
    {
        if (empty($arguments['name'])) {
            throw new \InvalidArgumentException('Parameter "name" is required.');
        }

        $tenantId = $arguments['tenant_id'] ?? null;
        if (!$tenantId) {
            throw new \InvalidArgumentException('Parameter "tenant_id" is required.');
        }

        $tenantExists = Tenant::where('uuid', $tenantId)->exists();
        if (!$tenantExists) {
            throw new \RuntimeException("Tenant with UUID '{$tenantId}' does not exist.");
        }

        $property = Property::create([
            'tenant_id' => $tenantId,
            'name' => trim($arguments['name']),
            'currency' => strtoupper(substr($arguments['currency'] ?? 'USD', 0, 3)),
            'timezone' => $arguments['timezone'] ?? 'UTC',
            'address_street' => $arguments['address_street'] ?? null,
            'address_city' => $arguments['address_city'] ?? null,
            'address_state' => $arguments['address_state'] ?? null,
            'address_postal_code' => $arguments['address_postal_code'] ?? null,
            'address_country' => $arguments['address_country'] ?? null,
            'lat' => isset($arguments['lat']) ? (float) $arguments['lat'] : null,
            'lng' => isset($arguments['lng']) ? (float) $arguments['lng'] : null,
            'contact_phone' => $arguments['contact_phone'] ?? null,
            'contact_email' => $arguments['contact_email'] ?? null,
            'status' => $arguments['status'] ?? 'active',
        ]);

        return [
            'success' => true,
            'message' => "Property '{$property->name}' created successfully.",
            'property' => [
                'uuid' => $property->uuid,
                'tenant_id' => $property->tenant_id,
                'name' => $property->name,
                'status' => $property->status,
                'currency' => $property->currency,
                'timezone' => $property->timezone,
                'created_at' => $property->created_at?->toIso8601String(),
            ],
        ];
    }
}
