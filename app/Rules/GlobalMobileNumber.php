<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class GlobalMobileNumber implements Rule
{
    public function passes($attribute, $value)
    {
        // Regular expression to validate global mobile numbers
        return preg_match('/^\+?\d{1,3}\s?\d{3,14}$/', $value);
    }

    public function message()
    {
        return 'Invalid global mobile number format.';
    }
}
