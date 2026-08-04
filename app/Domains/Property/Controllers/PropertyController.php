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
}
