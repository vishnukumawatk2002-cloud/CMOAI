<?php

namespace App\Domain\Exceptions;

use Exception;

class BrandAccessDeniedException extends Exception
{
    public function __construct(string $message = 'You do not have access to this brand.')
    {
        parent::__construct($message);
    }
}
