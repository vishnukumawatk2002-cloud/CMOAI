<?php

namespace App\Domain\Exceptions;

use Exception;

class PlanLimitExceededException extends Exception
{
    public function __construct(
        public readonly string $limitType,
        public readonly int $limit,
        string $message = 'Plan limit exceeded.',
    ) {
        parent::__construct($message);
    }
}
