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
        $this->actingAs($this->recepitonist)->postJson('api/receptionist/patients', $patientData->toArray())->assertCreated();
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', $patientData->toArray());
    }
}
