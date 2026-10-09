<?php

namespace App\Rules;

use App\Support\OasBarangays;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OasBarangayAddress implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $address = is_string($value) ? trim($value) : '';

        if ($address === '' || strcasecmp($address, 'Other') === 0) {
            $fail('Select a barangay in Oas, or choose Other and enter the address.');

            return;
        }

        if (in_array($address, OasBarangays::labels(), true)) {
            return;
        }

        if (mb_strlen($address) < 3) {
            $fail('Enter the full address or barangay.');
        }
    }
}
