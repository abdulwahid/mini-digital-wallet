<?php

namespace App\Services;

use App\Models\User;

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
}

