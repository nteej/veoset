<?php

namespace Tests\Unit;

use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_can_be_created_with_required_fields()
    {
        $site = Site::create([
            'name' => 'Test Energy Site',
            'location' => 'Test City',
        ]);

        $this->assertInstanceOf(Site::class, $site);
        $this->assertEquals('Test Energy Site', $site->name);
        $this->assertEquals('Test City', $site->location);
        $this->assertTrue($site->fresh()->is_active); // Default should be true, refresh from DB
    }

    public function test_site_can_be_created_with_all_fields()
    {
        $site = Site::create([
            'name' => 'Complete Energy Site',
            'description' => 'A comprehensive energy facility',
            'location' => 'Energy City',
            'address' => '123 Energy Street',
            'latitude' => 40.7128,
            'longitude' => -74.0060,
            'contact_person' => 'John Doe',
            'contact_email' => 'john@energysite.com',
            'contact_phone' => '+1-555-0123',
            'is_active' => true,
        ]);

        $this->assertEquals('Complete Energy Site', $site->name);
        $this->assertEquals('A comprehensive energy facility', $site->description);
        $this->assertEquals('Energy City', $site->location);
        $this->assertEquals('123 Energy Street', $site->address);
        $this->assertEquals(40.7128, $site->latitude);
        $this->assertEquals(-74.0060, $site->longitude);
        $this->assertEquals('John Doe', $site->contact_person);
        $this->assertEquals('john@energysite.com', $site->contact_email);
        $this->assertEquals('+1-555-0123', $site->contact_phone);
        $this->assertTrue($site->is_active);
    }

    public function test_site_can_be_deactivated()
    {
        $site = Site::factory()->create(['is_active' => true]);

        $site->update(['is_active' => false]);

        $this->assertFalse($site->fresh()->is_active);
    }

    public function test_site_active_scope_returns_only_active_sites()
    {
        Site::factory()->create(['name' => 'Active Site', 'is_active' => true]);
        Site::factory()->create(['name' => 'Inactive Site', 'is_active' => false]);

        $activeSites = Site::active()->get();

        $this->assertCount(1, $activeSites);
        $this->assertEquals('Active Site', $activeSites->first()->name);
    }

    public function test_site_coordinates_are_cast_to_decimal()
    {
        $site = Site::factory()->create([
            'latitude' => '40.71280000',
            'longitude' => '-74.00600000',
        ]);

        $this->assertIsFloat($site->latitude);
        $this->assertIsFloat($site->longitude);
    }

    public function test_site_factory_creates_valid_site()
    {
        $site = Site::factory()->create();

        $this->assertInstanceOf(Site::class, $site);
        $this->assertNotEmpty($site->name);
        $this->assertNotEmpty($site->location);
        $this->assertIsBool($site->is_active);
    }

    public function test_site_name_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Site::create([
            'location' => 'Test City',
        ]);
    }

    public function test_site_location_is_required()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Site::create([
            'name' => 'Test Site',
        ]);
    }
}