<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;

class GetPropertyTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_get';
    }

    public function getDescription(): string
    {
        return 'Retrieve full details of a specific property by UUID (or property name if UUID is not known), including settings, room types, and ratings. '
            . 'Supports optional api_key or token for enterprise authenticated access. '
            . 'GUARDRAIL: If searching by name matches multiple properties in the system, this tool strictly rejects the lookup with an ambiguity exception. You must ask the user to clarify which exact location or UUID they want.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'property_id' => [
                    'type' => 'string',
                    'description' => 'The UUID of the property.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'The exact or partial name of the property to retrieve if UUID is not known.',
                ],
                'api_key' => [
                    'type' => 'string',
                    'description' => 'Optional API key for authenticated access or elevated permissions.',
                ],
                'token' => [
                    'type' => 'string',
                    'description' => 'Optional service auth token for enterprise gateway auditing.',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments): array
    {
        $propertyId = $arguments['property_id'] ?? null;
        $name = $arguments['name'] ?? null;

        if (!$propertyId && !$name) {
            throw new \InvalidArgumentException('Either "property_id" or "name" is required.');
        }

        $activeTenantId = \App\Mcp\Gate\McpTenantContext::hasActiveTenant()
            ? \App\Mcp\Gate\McpTenantContext::getActiveTenantId()
            : null;

        if ($propertyId) {
            $query = Property::query()
                ->with(['propertySetting', 'roomTypes'])
                ->where('uuid', $propertyId);

            if ($activeTenantId) {
                $query->where('tenant_id', $activeTenantId);
            }

            $property = $query->first();

            if (!$property) {
                throw new \RuntimeException("Property with UUID '{$propertyId}' not found.");
            }
        } else {
            $query = Property::query()
                ->with(['propertySetting', 'roomTypes'])
                ->where('name', 'like', "%{$name}%");

            if ($activeTenantId) {
                $query->where('tenant_id', $activeTenantId);
            }

            $matches = $query->get();

            if ($matches->isEmpty()) {
                throw new \RuntimeException("Property with name '{$name}' not found.");
            }

            if ($matches->count() > 1) {
                $candidateDescriptions = $matches->map(function ($p) {
                    return "{$p->name} in {$p->address_city}, {$p->address_country} (UUID: {$p->uuid})";
                })->implode('; ');

                throw new \RuntimeException(
                    "Ambiguous property name '{$name}'. Found {$matches->count()} candidate properties: [{$candidateDescriptions}]. "
                    . "Zero-guessing policy strictly rejects ambiguous single-entity lookups. Ask the user to clarify which location or UUID they want."
                );
            }

            $property = $matches->first();
        }

        $avgRating = $property->reviews()->avg('rating_overall');
        $reviewCount = $property->reviews()->count();

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
                'postal_code' => $property->address_postal_code,
                'country' => $property->address_country,
                'coordinates' => [
                    'lat' => $property->lat ? (float) $property->lat : null,
                    'lng' => $property->lng ? (float) $property->lng : null,
                ],
            ],
            'contact' => [
                'phone' => $property->contact_phone,
                'email' => $property->contact_email,
            ],
            'reviews_summary' => [
                'count' => $reviewCount,
                'average_rating' => $avgRating ? round((float) $avgRating, 2) : null,
            ],
            'room_types' => $property->roomTypes->map(function ($rt) {
                return [
                    'uuid' => $rt->uuid,
                    'name' => $rt->name,
                    'code' => $rt->code ?? null,
                    'base_occupancy' => $rt->base_occupancy ?? null,
                    'max_occupancy' => $rt->max_occupancy ?? null,
                ];
            })->toArray(),
            'setting' => $property->propertySetting ? [
                'check_in_time' => $property->propertySetting->check_in_time ?? null,
                'check_out_time' => $property->propertySetting->check_out_time ?? null,
            ] : null,
            'created_at' => $property->created_at?->toIso8601String(),
            'updated_at' => $property->updated_at?->toIso8601String(),
        ];
    }
}
