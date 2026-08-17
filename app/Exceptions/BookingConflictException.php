<?php

namespace App\Exceptions;

use Exception;

class BookingConflictException extends Exception
{
    public function __construct(string $message = 'This studio has already been booked for the selected time. Please choose another available slot.')
    {
        parent::__construct($message);
    }
}
