<?php

namespace App\Domains\Property\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\Property\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('address_city', 'like', "%{$search}%")
                  ->orWhere('address_country', 'like', "%{$search}%");
            });
        }

        $properties = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('properties.index', compact('properties'));
    }

    public function show(Property $property)
    {
        $property->load(['propertySetting', 'roomTypes', 'reviews' => function($query) {
            $query->orderBy('uuid', 'desc')->limit(5);
        }]);

        $avgRating = $property->reviews()->avg('rating_overall');

        return view('properties.show', compact('property', 'avgRating'));
    }

    public function edit(Property $property)
    {
        return view('properties.edit', compact('property'));
    }

    public function update(Request $request, Property $property)
    {
        \Illuminate\Support\Facades\Log::info('Start of update: ' . json_encode($property->toArray()));
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'status' => 'required|string|in:active,inactive',
            'currency' => 'required|string|max:3',
            'timezone' => 'required|string|max:100',
            'address_street' => 'required|string|max:255',
            'address_city' => 'required|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'address_country' => 'required|string|max:100',
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'contact_phone' => 'nullable|string|max:50',
            'contact_email' => 'nullable|email|max:100',
        ]);

        $property->update($validated);
        \Illuminate\Support\Facades\Log::info('Property UUID: ' . $property->uuid);
        \Illuminate\Support\Facades\Log::info('Property Route Key: ' . $property->getRouteKey());

        return redirect()->route('properties.show', ['property' => $property->uuid])
            ->with('success', 'Property updated successfully.');
    }

    public function destroy(Property $property)
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($property) {
            $propertyId = $property->uuid;

            // 1. Get reservations
            $reservationIds = \Illuminate\Support\Facades\DB::table('reservations')->where('property_id', $propertyId)->pluck('uuid');

            // 2. Get folios
            $folioIds = $reservationIds->isNotEmpty() 
                ? \Illuminate\Support\Facades\DB::table('folios')->whereIn('reservation_id', $reservationIds)->pluck('uuid') 
                : collect();

            // 3. Get payments and charges
            $paymentIds = $folioIds->isNotEmpty() 
                ? \Illuminate\Support\Facades\DB::table('payments')->whereIn('folio_id', $folioIds)->pluck('uuid') 
                : collect();
            $chargeIds = $folioIds->isNotEmpty() 
                ? \Illuminate\Support\Facades\DB::table('charges')->whereIn('folio_id', $folioIds)->pluck('uuid') 
                : collect();

            // 4. Delete transaction allocations
            if ($paymentIds->isNotEmpty() || $chargeIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('transaction_allocations')
                    ->whereIn('payment_id', $paymentIds)
                    ->orWhereIn('charge_id', $chargeIds)
                    ->delete();
            }

            // 5. Delete payments and charges
            if ($paymentIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('payments')->whereIn('uuid', $paymentIds)->delete();
            }
            if ($chargeIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('charges')->whereIn('uuid', $chargeIds)->delete();
            }

            // 6. Delete folios
            if ($folioIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('folios')->whereIn('uuid', $folioIds)->delete();
            }

            // 7. Get room types
            $roomTypeIds = \Illuminate\Support\Facades\DB::table('room_types')->where('property_id', $propertyId)->pluck('uuid');
            // Get rate plans
            $ratePlanIds = \Illuminate\Support\Facades\DB::table('rate_plans')->where('property_id', $propertyId)->pluck('uuid');
            // Get rooms
            $roomIds = \Illuminate\Support\Facades\DB::table('rooms')->whereIn('room_type_id', $roomTypeIds)->pluck('uuid');

            // 8. Delete reservation rooms (references room_id and rate_plan_id and reservation_id)
            if ($reservationIds->isNotEmpty() || $roomTypeIds->isNotEmpty() || $ratePlanIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('reservation_rooms')
                    ->whereIn('reservation_id', $reservationIds)
                    ->orWhereIn('room_type_id', $roomTypeIds)
                    ->orWhereIn('rate_plan_id', $ratePlanIds)
                    ->delete();
            }

            // 9. Delete room beds
            if ($roomIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('room_beds')->whereIn('room_id', $roomIds)->delete();
                // 10. Delete rooms
                \Illuminate\Support\Facades\DB::table('rooms')->whereIn('uuid', $roomIds)->delete();
            }

            // 11. Delete other related tables
            \Illuminate\Support\Facades\DB::table('property_settings')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('reviews')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('revenue_metrics')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('booking_pace_snapshots')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('allotments')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('ical_connections')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('pms_connections')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('channel_credentials')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('services')->where('property_id', $propertyId)->delete();
            \Illuminate\Support\Facades\DB::table('tax_rates')->where('property_id', $propertyId)->delete();

            // 12. Delete AI conversations and reservation versions
            if ($reservationIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('ai_conversations')->whereIn('reservation_id', $reservationIds)->delete();
                \Illuminate\Support\Facades\DB::table('reservation_versions')->whereIn('reservation_id', $reservationIds)->delete();
                \Illuminate\Support\Facades\DB::table('reservation_guests')->whereIn('reservation_id', $reservationIds)->delete();
                \Illuminate\Support\Facades\DB::table('reservations')->whereIn('uuid', $reservationIds)->delete();
            }

            if ($ratePlanIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('rate_plans')->whereIn('uuid', $ratePlanIds)->delete();
            }

            if ($roomTypeIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('room_types')->whereIn('uuid', $roomTypeIds)->delete();
            }

            // Finally, delete the property itself
            $property->delete();
        });

        return redirect()->route('properties.index')
            ->with('success', 'Property deleted successfully.');
    }
}
