<?php

namespace Tests\Feature;

use App\Events\TransactionCompleted;
use App\Models\Transaction;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class TransactionBroadcastTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transactionService = new TransactionService();
    }

    /** @test */
    public function it_dispatches_transaction_completed_event_after_transfer()
    {
        Event::fake();

        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        $transaction = $this->transactionService->transfer($sender, $receiver->id, 100.00);

        // Verify event was dispatched
        Event::assertDispatched(TransactionCompleted::class, function ($event) use ($transaction, $sender, $receiver) {
            return $event->transaction->id === $transaction->id
                && $event->transaction->sender_id === $sender->id
                && $event->transaction->receiver_id === $receiver->id;
        });
    }

    /** @test */
    public function it_broadcasts_to_both_sender_and_receiver_channels()
    {
        Event::fake();

        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        $this->transactionService->transfer($sender, $receiver->id, 100.00);

        Event::assertDispatched(TransactionCompleted::class, function ($event) use ($sender, $receiver) {
            $channels = $event->broadcastOn();
            
            // Should broadcast to both user channels
            $senderChannelName = 'private-user.'.$sender->id;
            $receiverChannelName = 'private-user.'.$receiver->id;
            
            // Check if channels exist (PrivateChannel objects)
            $hasSenderChannel = false;
            $hasReceiverChannel = false;
            
            foreach ($channels as $channel) {
                $channelName = method_exists($channel, 'name') ? $channel->name : (string) $channel;
                if (str_contains($channelName, 'user.'.$sender->id)) {
                    $hasSenderChannel = true;
                }
                if (str_contains($channelName, 'user.'.$receiver->id)) {
                    $hasReceiverChannel = true;
                }
            }
            
            return $hasSenderChannel && $hasReceiverChannel;
        });
    }

    /** @test */
    public function it_includes_correct_balances_in_broadcast_data()
    {
        Event::fake();

        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        $this->transactionService->transfer($sender, $receiver->id, 100.00);

        // Refresh to get updated balances
        $sender->refresh();
        $receiver->refresh();

        Event::assertDispatched(TransactionCompleted::class, function ($event) use ($sender, $receiver) {
            $broadcastData = $event->broadcastWith();
            
            return abs($broadcastData['sender_balance'] - (float) $sender->balance) < 0.01
                && abs($broadcastData['receiver_balance'] - (float) $receiver->balance) < 0.01;
        });
    }

    /** @test */
    public function it_includes_transaction_data_in_broadcast()
    {
        Event::fake();

        $sender = User::factory()->create(['balance' => 1000.00]);
        $receiver = User::factory()->create(['balance' => 500.00]);

        $transaction = $this->transactionService->transfer($sender, $receiver->id, 100.00);

        Event::assertDispatched(TransactionCompleted::class, function ($event) use ($transaction) {
            $broadcastData = $event->broadcastWith();
            
            return isset($broadcastData['transaction'])
                && $broadcastData['transaction']['id'] === $transaction->id
                && $broadcastData['transaction']['amount'] === 100.00
                && isset($broadcastData['transaction']['sender'])
                && isset($broadcastData['transaction']['receiver']);
        });
    }
}

