<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ValidString implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Regular expression to allow alphanumeric characters, spaces, and underscores
        return preg_match('/^[a-zA-Z0-9 _]+$/', $value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute may only contain letters & numbers.';
    }
}
