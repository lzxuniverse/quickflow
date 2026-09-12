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
        return 'Retrieve full details of a specific property by UUID, including settings, room types, and ratings.';
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
            ],
            'required' => ['property_id'],
        ];
    }

    public function execute(array $arguments): array
    {
        $propertyId = $arguments['property_id'] ?? null;

        if (!$propertyId) {
            throw new \InvalidArgumentException('Parameter "property_id" is required.');
        }

        $property = Property::query()
            ->with(['propertySetting', 'roomTypes'])
            ->where('uuid', $propertyId)
            ->first();

        if (!$property) {
            throw new \RuntimeException("Property with UUID '{$propertyId}' not found.");
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
