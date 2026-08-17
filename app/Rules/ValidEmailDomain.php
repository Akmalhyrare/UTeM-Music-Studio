<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Single source of truth for the "must have a real domain extension" check.
 * Laravel's built-in `email` rule follows RFC 5321/5322, which syntactically
 * allows a single-label domain like "user@gmail" — this rule rejects that by
 * requiring the domain to end in a dot followed by a 2+ letter TLD (.com,
 * .edu.my, .net, ...). Used alongside `email` everywhere an email address is
 * collected, so every form enforces the same stricter check.
 */
class ValidEmailDomain implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! preg_match('/@[^@\s]+\.[A-Za-z]{2,}$/', (string) $value)) {
            $fail('The :attribute must include a valid domain extension (e.g. .com, .edu.my).');
        }
    }
}
