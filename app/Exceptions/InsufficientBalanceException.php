<?php

namespace App\Exceptions;

use Exception;

class InsufficientBalanceException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(float $required, float $available)
    {
        $message = sprintf(
            'Insufficient balance. Required: %s, Available: %s',
            number_format($required, 2),
            number_format($available, 2)
        );

        parent::__construct($message, 422);
    }
}

