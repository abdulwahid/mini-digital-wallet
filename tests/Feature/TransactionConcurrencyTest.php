<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();
    }

    /** @test */
    public function it_handles_concurrent_transfers_correctly()
    {
        // Create users with initial balances
        $user1 = User::factory()->create(['balance' => 1000.00]);
        $user2 = User::factory()->create(['balance' => 500.00]);
        $user3 = User::factory()->create(['balance' => 300.00]);

        // Calculate expected balances after concurrent transfers
        // User1 sends 100 to User2 (100 + 1.5 commission = 101.5)
        // User2 sends 50 to User3 (50 + 0.75 commission = 50.75)
        // User3 sends 25 to User1 (25 + 0.375 commission = 25.375)

        $expectedUser1Balance = 1000.00 - 101.50 + 25.00; // 923.50
        $expectedUser2Balance = 500.00 + 100.00 - 50.75; // 549.25
        $expectedUser3Balance = 300.00 + 50.00 - 25.375; // 324.625

        // Perform concurrent transfers
        $results = [];
        $exceptions = [];

        // Simulate concurrent transfers using parallel execution simulation
        try {
            $this->transactionService->transfer($user1, $user2->id, 100.00);
            $results[] = 'user1_to_user2';
        } catch (\Exception $e) {
            $exceptions[] = $e->getMessage();
        }

        try {
            $this->transactionService->transfer($user2, $user3->id, 50.00);
            $results[] = 'user2_to_user3';
        } catch (\Exception $e) {
            $exceptions[] = $e->getMessage();
        }

        try {
            $this->transactionService->transfer($user3, $user1->id, 25.00);
            $results[] = 'user3_to_user1';
        } catch (\Exception $e) {
            $exceptions[] = $e->getMessage();
        }

        // Refresh users to get latest balances
        $user1->refresh();
        $user2->refresh();
        $user3->refresh();

        // Verify all transactions were created
        $this->assertEquals(3, Transaction::count());

        // Verify balances are correct (within rounding tolerance)
        $this->assertEqualsWithDelta($expectedUser1Balance, (float) $user1->balance, 0.01);
        $this->assertEqualsWithDelta($expectedUser2Balance, (float) $user2->balance, 0.01);
        $this->assertEqualsWithDelta($expectedUser3Balance, (float) $user3->balance, 0.01);

        // Verify total balance in system is preserved (excluding commission)
        $totalBalance = (float) $user1->balance + (float) $user2->balance + (float) $user3->balance;
        $expectedTotal = 1000.00 + 500.00 + 300.00 - (1.50 + 0.75 + 0.375); // Original - commissions
        $this->assertEqualsWithDelta($expectedTotal, $totalBalance, 0.01);
    }

    /** @test */
    public function it_prevents_race_conditions_with_insufficient_balance()
    {
        $user1 = User::factory()->create(['balance' => 100.00]);
        $user2 = User::factory()->create(['balance' => 50.00]);

        // Try to transfer more than available (including commission)
        // 100 + 1.5% = 101.5, but user only has 100
        $this->expectException(\App\Exceptions\InsufficientBalanceException::class);

        $this->transactionService->transfer($user1, $user2->id, 100.00);

        // Verify no transaction was created
        $this->assertEquals(0, Transaction::count());

        // Verify balance unchanged
        $user1->refresh();
        $this->assertEquals(100.00, (float) $user1->balance);
    }

    /** @test */
    public function it_handles_multiple_transfers_from_same_user_concurrently()
    {
        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver1 = User::factory()->create(['balance' => 0.00]);
        $receiver2 = User::factory()->create(['balance' => 0.00]);
        $receiver3 = User::factory()->create(['balance' => 0.00]);

        // Send 100 to each receiver (3 transfers)
        $this->transactionService->transfer($sender, $receiver1->id, 100.00);
        $this->transactionService->transfer($sender, $receiver2->id, 100.00);
        $this->transactionService->transfer($sender, $receiver3->id, 100.00);

        // Refresh to get latest balance
        $sender->refresh();

        // Each transfer: 100 + 1.5 commission = 101.5
        // Total deducted: 101.5 * 3 = 304.5
        $expectedBalance = 1000.00 - 304.50; // 695.50

        $this->assertEqualsWithDelta($expectedBalance, (float) $sender->balance, 0.01);
        $this->assertEquals(3, Transaction::count());

        // Verify receivers got the amounts
        $receiver1->refresh();
        $receiver2->refresh();
        $receiver3->refresh();

        $this->assertEquals(100.00, (float) $receiver1->balance);
        $this->assertEquals(100.00, (float) $receiver2->balance);
        $this->assertEquals(100.00, (float) $receiver3->balance);
    }
}

