<?php

namespace App\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(string $message = 'One or more items do not have enough available stock.')
    {
        parent::__construct($message);
    }
}
