<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->customer()->create();
        $this->booking = Booking::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'confirmed',
            'payment_status' => 'unpaid',
        ]);
    }

    public function test_invoice_generated_after_payment(): void
    {
        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => $this->booking->total_price,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('invoices', [
            'booking_id' => $this->booking->id,
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => 'paid',
        ]);
    }

    public function test_customer_can_view_own_invoices(): void
    {
        Invoice::create([
            'booking_id' => $this->booking->id,
            'user_id' => $this->customer->id,
            'invoice_number' => 'INV-2026-001',
            'subtotal' => 2000,
            'tax_amount' => 300,
            'total_amount' => 2300,
            'status' => 'paid',
        ]);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/invoices');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_customer_cannot_view_other_invoices(): void
    {
        $otherCustomer = User::factory()->customer()->create();
        $otherBooking = Booking::factory()->create([
            'user_id' => $otherCustomer->id,
            'status' => 'active',
        ]);

        Invoice::create([
            'booking_id' => $otherBooking->id,
            'user_id' => $otherCustomer->id,
            'invoice_number' => 'INV-2026-002',
            'subtotal' => 1500,
            'tax_amount' => 225,
            'total_amount' => 1725,
            'status' => 'paid',
        ]);

        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/invoices');

        $response->assertOk();

        $data = $response->json('data.data');
        $this->assertIsArray($data);
        $this->assertCount(0, $data);
    }

    public function test_invoice_auto_numbering(): void
    {
        $token = $this->customer->createToken('auth-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/payments', [
                'booking_id' => $this->booking->id,
                'amount' => $this->booking->total_price,
                'payment_method' => 'card',
            ]);

        $response->assertStatus(201);

        $invoice = Invoice::where('booking_id', $this->booking->id)->first();
        $this->assertNotNull($invoice);
        $this->assertMatchesRegularExpression('/^INV-\d{8}-[A-Z0-9]{6}$/', $invoice->invoice_number);
    }
}
