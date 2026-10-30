<?php

namespace App\Core\Exceptions;

use Exception;

class DomainException extends Exception
{
    public function __construct(
        string $message,
        protected int $statusCode = 422,
        ?Exception $previous = null
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }
}
