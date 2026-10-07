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
        return 'Deactivate or permanently remove a property from the system. Default is safe soft deactivation (status=inactive). 
        Use force_delete=true for full cascade deletion. '
            . 'CRITICAL SAFETY GUARDRAILS: '
            . '1. AMBIGUOUS TARGET: If the user provides a vague name or location matching multiple properties 
            (e.g. multiple hotels in the same city or sharing a keyword), you must first use properties_list to find the candidates, 
            then present them and ask the user to clarify which exact property they want to delete. NEVER guess or delete arbitrarily. '
            . '2. DESTRUCTIVE ACTION WARNING & CONFIRMATION: Even after identifying the exact property, 
            do NOT call this tool immediately upon the initial request. 
            You MUST first present the property name and UUID, warn the user that this action will deactivate/remove the property, 
            and ask for explicit confirmation (e.g., "Are you sure you want to delete [Property Name] (ID: [UUID])? Please reply to confirm."). '
            . '3. STRICT CONFIRMATION ENFORCEMENT: 
            Only invoke this tool with confirmed=true AFTER the user explicitly confirms the deletion in their subsequent reply. 
            If the user cancels or says no, do NOT call this tool. 
            Even if the user requests an immediate or emergency deletion without asking, safety policy requires you to still ask for confirmation first.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'property_id' => [
                    'type' => 'string',
                    'description' => 'The UUID of the property to delete or deactivate (can also be passed as "uuid").',
                ],
                'uuid' => [
                    'type' => 'string',
                    'description' => 'The UUID of the property to delete or deactivate.',
                ],
                'confirmed' => [
                    'type' => 'boolean',
                    'description' => 'Optional confirmation flag indicating user has confirmed.',
                ],
                'force_delete' => [
                    'type' => 'boolean',
                    'default' => false,
                    'description' => 'If true, permanently cascades deletion of property and related records. If false, simply marks status as inactive.',
                ],
            ],
            'required' => [],
        ];
    }

    public function execute(array $arguments): array
    {
        $propertyId = $arguments['property_id'] ?? $arguments['uuid'] ?? null;
        if (!$propertyId) {
            throw new \InvalidArgumentException('Parameter "property_id" or "uuid" is required.');
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
