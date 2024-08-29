<?php

namespace App\Helpers;

class Helpers
{
    public static function error_processor($validator)
    {
        // Process and return validation errors
        $errors = [];
        foreach ($validator->errors()->all() as $message) {
            $errors[] = $message;
        }
        return $errors;
    }
}
