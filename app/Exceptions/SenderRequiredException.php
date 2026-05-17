<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when batch processing reaches the pricing step without a sender
 * resolved on the batch. Pricing depends on the sender's masking type, so
 * we cannot proceed without one.
 */
class SenderRequiredException extends RuntimeException
{
    public function __construct(string $message = 'A sender is required to price this message batch.')
    {
        parent::__construct($message);
    }
}
