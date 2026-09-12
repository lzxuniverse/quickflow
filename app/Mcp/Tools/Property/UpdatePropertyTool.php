<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;

class UpdatePropertyTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_update';
    }

    public function getDescription(): string
    {
        return 'Update an existing property\'s fields (name, status, address, currency, timezone, contact info).';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'property_id' => [
                    'type' => 'string',
                    'description' => 'UUID of the property to update.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Updated name of the property.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['active', 'inactive'],
                    'description' => 'Updated status.',
                ],
                'currency' => [
                    'type' => 'string',
                    'description' => 'Updated currency code (3 chars, e.g. USD).',
                ],
                'timezone' => [
                    'type' => 'string',
                    'description' => 'Updated timezone.',
                ],
                'address_street' => [
                    'type' => 'string',
                    'description' => 'Updated street address.',
                ],
                'address_city' => [
                    'type' => 'string',
                    'description' => 'Updated city.',
                ],
                'address_state' => [
                    'type' => 'string',
                    'description' => 'Updated state.',
                ],
                'address_postal_code' => [
                    'type' => 'string',
                    'description' => 'Updated postal code.',
                ],
                'address_country' => [
                    'type' => 'string',
                    'description' => 'Updated country.',
                ],
                'lat' => [
                    'type' => 'number',
                    'description' => 'Updated latitude.',
                ],
                'lng' => [
                    'type' => 'number',
                    'description' => 'Updated longitude.',
                ],
                'contact_phone' => [
                    'type' => 'string',
                    'description' => 'Updated contact phone.',
                ],
                'contact_email' => [
                    'type' => 'string',
                    'description' => 'Updated contact email.',
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

        $property = Property::where('uuid', $propertyId)->first();
        if (!$property) {
            throw new \RuntimeException("Property with UUID '{$propertyId}' not found.");
        }

        $fields = [
            'name', 'status', 'currency', 'timezone',
            'address_street', 'address_city', 'address_state',
            'address_postal_code', 'address_country',
            'lat', 'lng', 'contact_phone', 'contact_email',
        ];

        $updates = [];
        foreach ($fields as $field) {
            if (array_key_exists($field, $arguments)) {
                $updates[$field] = $arguments[$field];
            }
        }

        if (isset($updates['currency'])) {
            $updates['currency'] = strtoupper(substr($updates['currency'], 0, 3));
        }

        if (empty($updates)) {
            return [
                'success' => false,
                'message' => 'No fields were provided to update.',
                'property' => $property->toArray(),
            ];
        }

        $property->update($updates);

        return [
            'success' => true,
            'message' => "Property '{$property->name}' updated successfully.",
            'updated_fields' => array_keys($updates),
            'property' => [
                'uuid' => $property->uuid,
                'name' => $property->name,
                'status' => $property->status,
                'currency' => $property->currency,
                'timezone' => $property->timezone,
                'updated_at' => $property->updated_at?->toIso8601String(),
            ],
        ];
    }
}
