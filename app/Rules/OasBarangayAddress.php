<?php

namespace App\Rules;

use App\Support\OasBarangays;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OasBarangayAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! in_array($value, OasBarangays::labels(), true)) {
            $fail('Please select a barangay in Oas, Albay.');
        }
    }
}
