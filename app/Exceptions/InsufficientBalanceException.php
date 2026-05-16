<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(
        public readonly float $required,
        public readonly float $available,
        string $message = '',
    ) {
        parent::__construct(
            $message !== '' ? $message : sprintf(
                'Insufficient balance: requires %s, has %s.',
                number_format($required, 4),
                number_format($available, 4),
            ),
        );
    }
}
