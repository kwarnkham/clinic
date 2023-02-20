<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RecepitonistTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_receiptionist_register_a_patient()
    {
        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/receptionist/patients', $patientData->toArray());
        $response->assertCreated();
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', $patientData->toArray());
    }

    public function test_registering_a_patinet_also_record_a_visit()
    {
        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/receptionist/patients', $patientData->toArray());
        $response->assertCreated();
        $this->assertDatabaseCount('visits', 1);
    }
}
