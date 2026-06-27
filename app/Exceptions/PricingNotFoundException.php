<?php

namespace App\Exceptions;

use Exception;

class PricingNotFoundException extends Exception
{
    protected array $params;

    public function __construct(string $message = 'Pricing record not found for the given criteria.', array $params = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->params = $params;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
