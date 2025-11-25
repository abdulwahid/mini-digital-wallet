<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    /**
     * Commission rate as a percentage (1.5%).
     */
    private const COMMISSION_RATE = 0.015;

    /**
     * Calculate the commission fee for a given amount.
     *
     * @param  float|string  $amount
     * @return float
     */
    public function calculateCommission($amount): float
    {
        return round((float) $amount * self::COMMISSION_RATE, 2);
    }

    /**
     * Calculate the total amount the sender needs to pay (amount + commission).
     *
     * @param  float|string  $amount
     * @return float
     */
    public function calculateTotalAmount($amount): float
    {
        $commission = $this->calculateCommission($amount);
        return round((float) $amount + $commission, 2);
    }

    /**
     * Check if the user has sufficient balance for the transaction (including commission).
     *
     * @param  User  $user
     * @param  float|string  $amount
     * @return bool
     */
    public function hasSufficientBalance(User $user, $amount): bool
    {
        $totalAmount = $this->calculateTotalAmount($amount);
        return (float) $user->balance >= $totalAmount;
    }

    /**
     * Get the required balance for a transaction (amount + commission).
     *
     * @param  float|string  $amount
     * @return float
     */
    public function getRequiredBalance($amount): float
    {
        return $this->calculateTotalAmount($amount);
    }

    /**
     * Perform an atomic transaction transfer with row-level locking.
     *
     * @param  User  $sender
     * @param  int  $receiverId
     * @param  float|string  $amount
     * @return Transaction
     *
     * @throws \Illuminate\Database\QueryException
     * @throws \RuntimeException
     */
    public function transfer(User $sender, int $receiverId, $amount): Transaction
    {
        $amount = (float) $amount;
        $commission = $this->calculateCommission($amount);
        $totalAmount = $this->calculateTotalAmount($amount);

        return DB::transaction(function () use ($sender, $receiverId, $amount, $commission, $totalAmount) {
            // Lock sender row for update to prevent concurrent modifications
            $lockedSender = User::where('id', $sender->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Lock receiver row for update
            $receiver = User::where('id', $receiverId)
                ->lockForUpdate()
                ->firstOrFail();

            // Double-check balance after acquiring lock
            if ((float) $lockedSender->balance < $totalAmount) {
                throw new \RuntimeException('Insufficient balance for this transaction.');
            }

            // Update sender balance (subtract amount + commission)
            $lockedSender->balance = (float) $lockedSender->balance - $totalAmount;
            $lockedSender->save();

            // Update receiver balance (add amount only, no commission)
            $receiver->balance = (float) $receiver->balance + $amount;
            $receiver->save();

            // Create transaction record
            $transaction = Transaction::create([
                'sender_id' => $lockedSender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'commission_fee' => $commission,
            ]);

            return $transaction->load(['sender', 'receiver']);
        });
    }
}

