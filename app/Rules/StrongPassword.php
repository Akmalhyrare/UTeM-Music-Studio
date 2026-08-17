<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Single source of truth for the application-wide password policy.
 * Used by both registration (RegisterRequest) and change-password
 * (UpdateAccountPasswordRequest) so the two flows can never drift apart.
 *
 * Policy: minimum 8 characters, at least one uppercase letter, one
 * lowercase letter, one number, and one special character.
 */
class StrongPassword implements ValidationRule
{
    public const MIN_LENGTH = 8;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if (mb_strlen($value) < self::MIN_LENGTH) {
            $fail('The :attribute must be at least '.self::MIN_LENGTH.' characters long.');
        }

        if (! preg_match('/[A-Z]/', $value)) {
            $fail('The :attribute must contain at least one uppercase letter.');
        }

        if (! preg_match('/[a-z]/', $value)) {
            $fail('The :attribute must contain at least one lowercase letter.');
        }

        if (! preg_match('/[0-9]/', $value)) {
            $fail('The :attribute must contain at least one number.');
        }

        if (! preg_match('/[^A-Za-z0-9]/', $value)) {
            $fail('The :attribute must contain at least one special character.');
        }
    }
}
