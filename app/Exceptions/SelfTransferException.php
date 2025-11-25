<?php

namespace App\Exceptions;

use Exception;

class SelfTransferException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'You cannot transfer money to yourself.')
    {
        parent::__construct($message, 422);
    }
}

