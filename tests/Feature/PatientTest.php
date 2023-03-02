<?php

namespace Tests\Feature;

use App\Enums\VisitStatus;
use App\Events\VisitCreated;
use App\Models\Patient;
use App\Models\Product;
use App\Models\Visit;
use Tests\TestCase;
use Illuminate\Support\Facades\Event;

class PatientTest extends TestCase
{
    public function test_receiptionist_register_a_patient()
    {
        Event::fake([VisitCreated::class]);

        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/patients', $patientData->toArray());
        $response->assertCreated();
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', $patientData->toArray());
        Event::assertDispatched(VisitCreated::class);
    }

    public function test_registering_a_patinet_also_record_a_visit()
    {
        Event::fake([VisitCreated::class]);
        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/patients', $patientData->toArray());
        $response->assertCreated();
        $this->assertDatabaseCount('visits', 1);
        $this->assertEquals(VisitStatus::PENDING->value, Visit::first()->status);
        $this->assertEquals(Visit::first()->amount, 0);
        Event::assertDispatched(VisitCreated::class);
    }

    public function test_registering_a_patient_with_book_fees()
    {
        Event::fake([VisitCreated::class]);
        $patientData = Patient::factory()->make();
        $response = $this->actingAs($this->recepitonist)->postJson('api/patients', [
            ...$patientData->toArray(),
            'with_book_fees' => 1,
        ]);
        $response->assertCreated();
        $this->assertDatabaseCount('visits', 1);
        $this->assertEquals(VisitStatus::PENDING->value, Visit::first()->status);
        $this->assertDatabaseCount('product_visit', 1);
        $this->assertEquals(Visit::first()->amount, Product::first()->sale_price);
        Event::assertDispatched(VisitCreated::class);
    }

    public function test_list_patients()
    {
        $this->actingAs($this->admin)->getJson('api/patients')->assertOk();
    }

    public function test_delete_a_patient()
    {
        $patient = Patient::factory()->create();
        $this->actingAs($this->admin)->deleteJson('api/patients/' . $patient->id)->assertOk();
        $this->assertNotNull($patient->fresh()->deleted_at);
    }
}
