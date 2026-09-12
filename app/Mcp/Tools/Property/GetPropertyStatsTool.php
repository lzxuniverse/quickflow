<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;
use Illuminate\Support\Facades\DB;

class GetPropertyStatsTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_stats';
    }

    public function getDescription(): string
    {
        return 'Get statistical overview across properties (total count, active/inactive distribution, room counts, top countries, and average guest rating).';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'tenant_id' => [
                    'type' => 'string',
                    'description' => 'Optional tenant UUID to scope the statistics.',
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

        $propertyUuids = (clone $query)->pluck('uuid');
        $totalProperties = $propertyUuids->count();

        $activeProperties = (clone $query)->where('status', 'active')->count();
        $inactiveProperties = (clone $query)->where('status', 'inactive')->count();

        // Top countries
        $topCountries = (clone $query)
            ->whereNotNull('address_country')
            ->select('address_country', DB::raw('count(*) as count'))
            ->groupBy('address_country')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'address_country')
            ->toArray();

        // Room count
        $totalRooms = 0;
        $totalRoomTypes = 0;
        $avgRating = null;

        if ($propertyUuids->isNotEmpty()) {
            $roomTypeUuids = DB::table('room_types')
                ->whereIn('property_id', $propertyUuids)
                ->pluck('uuid');

            $totalRoomTypes = $roomTypeUuids->count();

            if ($roomTypeUuids->isNotEmpty()) {
                $totalRooms = DB::table('rooms')
                    ->whereIn('room_type_id', $roomTypeUuids)
                    ->count();
            }

            $rating = DB::table('reviews')
                ->whereIn('property_id', $propertyUuids)
                ->avg('rating_overall');

            $avgRating = $rating !== null ? round((float) $rating, 2) : null;
        }

        return [
            'tenant_id' => $arguments['tenant_id'] ?? 'all',
            'total_properties' => $totalProperties,
            'active_properties' => $activeProperties,
            'inactive_properties' => $inactiveProperties,
            'total_room_types' => $totalRoomTypes,
            'total_rooms' => $totalRooms,
            'average_rating' => $avgRating,
            'top_countries' => $topCountries,
        ];
    }
}
