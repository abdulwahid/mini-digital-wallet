<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TransactionCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Transaction $transaction,
        public float $senderBalance,
        public float $receiverBalance
    ) {
        // Load relationships for broadcasting
        $this->transaction->load(['sender:id,name,email', 'receiver:id,name,email']);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.'.$this->transaction->sender_id),
            new PrivateChannel('user.'.$this->transaction->receiver_id),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'transaction.completed';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'transaction' => [
                'id' => $this->transaction->id,
                'sender_id' => $this->transaction->sender_id,
                'receiver_id' => $this->transaction->receiver_id,
                'amount' => (float) $this->transaction->amount,
                'commission_fee' => (float) $this->transaction->commission_fee,
                'created_at' => $this->transaction->created_at->toIso8601String(),
                'sender' => [
                    'id' => $this->transaction->sender->id,
                    'name' => $this->transaction->sender->name,
                    'email' => $this->transaction->sender->email,
                ],
                'receiver' => [
                    'id' => $this->transaction->receiver->id,
                    'name' => $this->transaction->receiver->name,
                    'email' => $this->transaction->receiver->email,
                ],
            ],
            'sender_balance' => $this->senderBalance,
            'receiver_balance' => $this->receiverBalance,
        ];
    }
}

