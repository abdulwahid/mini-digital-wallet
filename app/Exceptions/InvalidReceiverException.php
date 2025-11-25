<?php

namespace App\Exceptions;

use Exception;

class InvalidReceiverException extends Exception
{
    /**
     * Create a new exception instance.
     */
    public function __construct(string $message = 'Invalid receiver specified.')
    {
        parent::__construct($message, 422);
    }
}

