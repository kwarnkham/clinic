<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\Visit;
use Tests\TestCase;

class PharmacistTest extends TestCase
{
    public function test_confirm_products_in_a_visit()
    {
        $visit = Visit::factory()->for(Patient::factory())->create();
        $response = $this->actingAs($this->pharmacist)->postJson('api/pharmacist/visits/' . $visit->id . '/confirm');
        $response->assertOk();
        $this->assertEquals($visit->fresh()->status, VisitStatus::CONFIRMED->value);
    }
}
