<?php

namespace Tests\Feature;

use App\Domains\Property\Models\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PropertyControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test that the properties list page loads successfully.
     */
    public function test_properties_index_page_loads_successfully(): void
    {
        $property = Property::orderBy('name')->first();

        $response = $this->get(route('properties.index'));

        $response->assertStatus(200);
        $response->assertSee('Properties Directory');
        
        if ($property) {
            $response->assertSee($property->name);
        }
    }

    /**
     * Test searching properties on the index page.
     */
    public function test_properties_index_page_can_be_searched(): void
    {
        $property = Property::orderBy('name')->first();

        if ($property) {
            $response = $this->get(route('properties.index', ['search' => $property->name]));

            $response->assertStatus(200);
            $response->assertSee($property->name);
        } else {
            $this->markTestSkipped('No properties found in database to search.');
        }
    }

    /**
     * Test that the property details page loads successfully.
     */
    public function test_properties_show_page_loads_successfully(): void
    {
        $property = Property::first();

        if ($property) {
            $response = $this->get(route('properties.show', $property->uuid));

            $response->assertStatus(200);
            $response->assertSee($property->name);
            $response->assertSee('General Information');
            $response->assertSee('Property Settings');
            $response->assertSee('Configured Room Types');
        } else {
            $this->markTestSkipped('No properties found in database to view details.');
        }
    }

    /**
     * Test that showing a non-existent property returns 404.
     */
    public function test_properties_show_page_returns_404_for_invalid_uuid(): void
    {
        $response = $this->get(route('properties.show', '00000000-0000-0000-0000-000000000000'));

        $response->assertStatus(404);
    }

    /**
     * Test that the edit property page loads successfully.
     */
    public function test_properties_edit_page_loads_successfully(): void
    {
        $property = Property::first();

        if ($property) {
            $response = $this->get(route('properties.edit', $property->uuid));

            $response->assertStatus(200);
            $response->assertSee('Edit Property');
            $response->assertSee($property->name);
        } else {
            $this->markTestSkipped('No properties found in database to load edit page.');
        }
    }

    /**
     * Test that updating a property works and redirects to show page.
     */
    public function test_properties_update_saves_data_and_redirects(): void
    {
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $property = Property::first();

        if ($property) {
            $newName = 'Updated Property Name XYZ';
            $response = $this->put(route('properties.update', $property->uuid), [
                'name' => $newName,
                'status' => 'active',
                'currency' => $property->currency,
                'timezone' => $property->timezone,
                'address_street' => '123 New Street',
                'address_city' => 'New City',
                'address_country' => 'New Country',
                'lat' => 45.0,
                'lng' => 90.0,
                'contact_email' => 'newemail@example.com',
            ]);

            $response->assertRedirect(route('properties.show', $property->uuid));
            $response->assertSessionHas('success');

            $property->refresh();
            $this->assertEquals($newName, $property->name);
            $this->assertEquals('123 New Street', $property->address_street);
        } else {
            $this->markTestSkipped('No properties found in database to update.');
        }
    }

    /**
     * Test that updating a property with invalid data returns validation errors.
     */
    public function test_properties_update_validation_fails_for_invalid_input(): void
    {
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $property = Property::first();

        if ($property) {
            $response = $this->put(route('properties.update', $property->uuid), [
                'name' => '', // Name is required
                'status' => 'invalid-status', // Status must be active or inactive
                'currency' => $property->currency,
                'timezone' => $property->timezone,
                'address_street' => '123 New Street',
                'address_city' => 'New City',
                'address_country' => 'New Country',
            ]);

            $response->assertSessionHasErrors(['name', 'status']);
        } else {
            $this->markTestSkipped('No properties found in database to validate update.');
        }
    }
}
