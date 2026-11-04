<?php

namespace Tests\Feature\Payments;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentIdempotencyConstraintTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgresql_allows_only_one_live_chapa_intent_per_booking(): void
    {
        $this->requirePostgreSql();
        $booking = Booking::factory()->create();

        $this->createPayment($booking, Payment::STATUS_PROCESSING, 'PG-LIVE-ONE');

        $baseLevel = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $this->createPayment($booking, Payment::STATUS_PENDING, 'PG-LIVE-TWO');
            $this->fail('PostgreSQL should reject a second live Chapa intent.');
        } catch (QueryException $e) {
            $this->assertSame('23505', $e->errorInfo[0] ?? $e->getCode());
            $this->assertStringContainsString(
                'payments_one_live_chapa_per_booking',
                strtolower($e->getMessage()),
            );
        } finally {
            while (DB::transactionLevel() > $baseLevel) {
                DB::rollBack();
            }
        }

        $this->assertDatabaseCount('payments', 1);
    }

    public function test_postgresql_allows_a_new_intent_after_the_previous_attempt_failed(): void
    {
        $this->requirePostgreSql();
        $booking = Booking::factory()->create();

        $this->createPayment($booking, Payment::STATUS_FAILED, 'PG-FAILED');
        $this->createPayment($booking, Payment::STATUS_PENDING, 'PG-RETRY');

        $this->assertDatabaseCount('payments', 2);
    }

    public function test_postgresql_allows_only_one_verified_paid_payment_per_booking(): void
    {
        $this->requirePostgreSql();
        $booking = Booking::factory()->create();

        $this->createPayment(
            $booking,
            Payment::STATUS_PAID,
            'PG-SETTLED-ONE',
            Payment::VERIFICATION_VERIFIED,
        );

        $baseLevel = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $this->createPayment(
                $booking,
                Payment::STATUS_PAID,
                'PG-SETTLED-TWO',
                Payment::VERIFICATION_MANUALLY_CONFIRMED,
            );
            $this->fail('PostgreSQL should reject a second verified paid payment.');
        } catch (QueryException $e) {
            $this->assertSame('23505', $e->errorInfo[0] ?? $e->getCode());
            $this->assertStringContainsString(
                'payments_one_settled_per_booking',
                strtolower($e->getMessage()),
            );
        } finally {
            while (DB::transactionLevel() > $baseLevel) {
                DB::rollBack();
            }
        }

        // Historical/reconciliation rows outside the settled predicate remain
        // possible without weakening the one-authoritative-payment guarantee.
        $this->createPayment(
            $booking,
            Payment::STATUS_PAID,
            'PG-UNVERIFIED-HISTORY',
            Payment::VERIFICATION_UNVERIFIED,
        );

        $this->assertDatabaseCount('payments', 2);
    }

    private function requirePostgreSql(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            $this->markTestSkipped('PostgreSQL partial unique index integration test.');
        }
    }

    private function createPayment(
        Booking $booking,
        string $status,
        string $reference,
        string $verificationStatus = Payment::VERIFICATION_UNVERIFIED,
    ): Payment
    {
        return Payment::create([
            'booking_id' => $booking->id,
            'attempt_number' => 1,
            'user_id' => $booking->user_id,
            'branch_id' => $booking->branch_id,
            'amount' => 100,
            'expected_amount' => 100,
            'currency' => 'ETB',
            'payment_method' => Payment::METHOD_ONLINE_PAYMENT,
            'gateway' => Payment::GATEWAY_CHAPA,
            'transaction_reference' => $reference,
            'status' => $status,
            'verification_status' => $verificationStatus,
            'is_archived' => false,
        ]);
    }
}
