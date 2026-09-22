<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown inside the wallet transaction so the debit rolls back cleanly.
 * Carries the balance so the message can tell the customer what they have.
 */
class InsufficientFunds extends RuntimeException
{
    public function __construct(public readonly float $balance)
    {
        parent::__construct('Insufficient wallet balance.');
    }
}