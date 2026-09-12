<?php

namespace App\Mcp\Tools\Property;

use App\Domains\Property\Models\Property;
use App\Mcp\Contracts\ToolInterface;
use Illuminate\Support\Facades\DB;

class DeletePropertyTool implements ToolInterface
{
    public function getName(): string
    {
        return 'properties_delete';
    }

    public function getDescription(): string
    {
        return 'Deactivate or permanently remove a property. Default is safe soft deactivation (status=inactive). Use force_delete=true for full cascade deletion.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'property_id' => [
                    'type' => 'string',
                    'description' => 'The UUID of the property to delete or deactivate.',
                ],
                'force_delete' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'If true, permanently cascades deletion of property and related records. If false, simply marks status as inactive.',
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

        $forceDelete = (bool) ($arguments['force_delete'] ?? false);

        if (!$forceDelete) {
            $property->update(['status' => 'inactive']);

            return [
                'success' => true,
                'action' => 'deactivated',
                'message' => "Property '{$property->name}' has been marked as inactive.",
                'property_id' => $property->uuid,
            ];
        }

        // Full cascade deletion within a database transaction
        DB::transaction(function () use ($property, $propertyId) {
            $reservationIds = DB::table('reservations')->where('property_id', $propertyId)->pluck('uuid');
            $folioIds = $reservationIds->isNotEmpty() 
                ? DB::table('folios')->whereIn('reservation_id', $reservationIds)->pluck('uuid') 
                : collect();

            $paymentIds = $folioIds->isNotEmpty() 
                ? DB::table('payments')->whereIn('folio_id', $folioIds)->pluck('uuid') 
                : collect();
            $chargeIds = $folioIds->isNotEmpty() 
                ? DB::table('charges')->whereIn('folio_id', $folioIds)->pluck('uuid') 
                : collect();

            if ($paymentIds->isNotEmpty() || $chargeIds->isNotEmpty()) {
                DB::table('transaction_allocations')
                    ->whereIn('payment_id', $paymentIds)
                    ->orWhereIn('charge_id', $chargeIds)
                    ->delete();
            }

            if ($paymentIds->isNotEmpty()) {
                DB::table('payments')->whereIn('uuid', $paymentIds)->delete();
            }
            if ($chargeIds->isNotEmpty()) {
                DB::table('charges')->whereIn('uuid', $chargeIds)->delete();
            }
            if ($folioIds->isNotEmpty()) {
                DB::table('folios')->whereIn('uuid', $folioIds)->delete();
            }

            $roomTypeIds = DB::table('room_types')->where('property_id', $propertyId)->pluck('uuid');
            $ratePlanIds = DB::table('rate_plans')->where('property_id', $propertyId)->pluck('uuid');
            $roomIds = DB::table('rooms')->whereIn('room_type_id', $roomTypeIds)->pluck('uuid');

            if ($reservationIds->isNotEmpty() || $roomTypeIds->isNotEmpty() || $ratePlanIds->isNotEmpty()) {
                DB::table('reservation_rooms')
                    ->whereIn('reservation_id', $reservationIds)
                    ->orWhereIn('room_type_id', $roomTypeIds)
                    ->orWhereIn('rate_plan_id', $ratePlanIds)
                    ->delete();
            }

            if ($roomIds->isNotEmpty()) {
                DB::table('room_beds')->whereIn('room_id', $roomIds)->delete();
                DB::table('rooms')->whereIn('uuid', $roomIds)->delete();
            }

            DB::table('property_settings')->where('property_id', $propertyId)->delete();
            DB::table('reviews')->where('property_id', $propertyId)->delete();
            DB::table('revenue_metrics')->where('property_id', $propertyId)->delete();
            DB::table('booking_pace_snapshots')->where('property_id', $propertyId)->delete();
            DB::table('allotments')->where('property_id', $propertyId)->delete();
            DB::table('ical_connections')->where('property_id', $propertyId)->delete();
            DB::table('pms_connections')->where('property_id', $propertyId)->delete();
            DB::table('channel_credentials')->where('property_id', $propertyId)->delete();
            DB::table('services')->where('property_id', $propertyId)->delete();
            DB::table('tax_rates')->where('property_id', $propertyId)->delete();

            if ($reservationIds->isNotEmpty()) {
                DB::table('ai_conversations')->whereIn('reservation_id', $reservationIds)->delete();
                DB::table('reservation_versions')->whereIn('reservation_id', $reservationIds)->delete();
                DB::table('reservation_guests')->whereIn('reservation_id', $reservationIds)->delete();
                DB::table('reservations')->whereIn('uuid', $reservationIds)->delete();
            }

            if ($ratePlanIds->isNotEmpty()) {
                DB::table('rate_plans')->whereIn('uuid', $ratePlanIds)->delete();
            }
            if ($roomTypeIds->isNotEmpty()) {
                DB::table('room_types')->whereIn('uuid', $roomTypeIds)->delete();
            }

            $property->delete();
        });

        return [
            'success' => true,
            'action' => 'permanently_deleted',
            'message' => "Property '{$property->name}' and all associated records have been permanently deleted.",
            'property_id' => $propertyId,
        ];
    }
}
