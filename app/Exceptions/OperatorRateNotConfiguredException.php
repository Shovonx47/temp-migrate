<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an SMS is queued for an operator that has no rate configured
 * (neither user override nor system default) for the sender type in use.
 */
class OperatorRateNotConfiguredException extends RuntimeException
{
    public function __construct(
        public readonly string $phone,
        public readonly ?string $operatorName,
        public readonly bool $isMasking,
        string $message = '',
    ) {
        $type = $isMasking ? 'masking' : 'non-masking';

        parent::__construct(
            $message !== '' ? $message : sprintf(
                'No %s rate configured for operator [%s] (phone %s).',
                $type,
                $operatorName ?? 'unknown',
                $phone,
            ),
        );
    }
}
