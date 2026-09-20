<?php

namespace App\Services;

class ValidationMessageService
{
    /**
     * Map Laravel validation rule to user-friendly message.
     */
    public static function translate(string $field, string $rule, array $parameters = []): string
    {
        $fieldLabel = self::getFieldLabel($field);

        return match ($rule) {
            'required' => "Please enter {$fieldLabel}.",
            'string' => "{$fieldLabel} must be text.",
            'email' => "Please enter a valid email address.",
            'max' => "{$fieldLabel} must not exceed {$parameters[0]} characters.",
            'min' => "{$fieldLabel} must be at least {$parameters[0]} characters.",
            'confirmed' => "{$fieldLabel} confirmation does not match.",
            'unique' => "An account with this {$fieldLabel} already exists. Please use a different one.",
            'exists' => "Selected {$fieldLabel} does not exist.",
            'in' => "Please select a valid {$fieldLabel}.",
            'date' => "{$fieldLabel} must be a valid date.",
            'before' => "{$fieldLabel} must be before {$parameters[0]}.",
            'after' => "{$fieldLabel} must be after {$parameters[0]}.",
            'before_or_equal' => "{$fieldLabel} must be on or before {$parameters[0]}.",
            'after_or_equal' => "{$fieldLabel} must be on or after {$parameters[0]}.",
            'numeric' => "{$fieldLabel} must be a number.",
            'integer' => "{$fieldLabel} must be a whole number.",
            'boolean' => "{$fieldLabel} must be true or false.",
            'file' => "Please upload a valid file for {$fieldLabel}.",
            'mimes' => "{$fieldLabel} must be one of: " . implode(', ', $parameters),
            'max' => isset($parameters[0]) && is_numeric($parameters[0]) && $parameters[0] > 100
                ? "{$fieldLabel} is too large. Maximum size is " . round($parameters[0] / 1024) . " MB."
                : "{$fieldLabel} must not exceed {$parameters[0]} characters.",
            'nullable' => "",
            'sometimes' => "",
            'regex' => "{$fieldLabel} format is invalid.",
            'distinct' => "{$fieldLabel} contains duplicate values.",
            'array' => "{$fieldLabel} must be a list.",
            'json' => "{$fieldLabel} must be valid JSON.",
            'ip' => "{$fieldLabel} must be a valid IP address.",
            'url' => "{$fieldLabel} must be a valid URL.",
            'uuid' => "{$fieldLabel} must be a valid UUID.",
            'timezone' => "{$fieldLabel} must be a valid timezone.",
            'digits' => "{$fieldLabel} must be {$parameters[0]} digits.",
            'digits_between' => "{$fieldLabel} must be between {$parameters[0]} and {$parameters[1]} digits.",
            'size' => "{$fieldLabel} size is invalid.",
            'between' => "{$fieldLabel} must be between {$parameters[0]} and {$parameters[1]}.",
            'different' => "{$fieldLabel} must be different from {$parameters[0]}.",
            'same' => "{$fieldLabel} must match {$parameters[0]}.",
            'prohibited' => "{$fieldLabel} is not allowed.",
            'prohibits' => "{$fieldLabel} cannot be used with {$parameters[0]}.",
            'required_if' => "{$fieldLabel} is required when {$parameters[0]} is {$parameters[1]}.",
            'required_unless' => "{$fieldLabel} is required unless {$parameters[0]} is {$parameters[1]}.",
            'required_with' => "{$fieldLabel} is required when {$parameters[0]} is present.",
            'required_with_all' => "{$fieldLabel} is required when {$parameters[0]} are present.",
            'required_without' => "{$fieldLabel} is required when {$parameters[0]} is not present.",
            'required_without_all' => "{$fieldLabel} is required when none of {$parameters[0]} are present.",
            'required_array_keys' => "{$fieldLabel} must contain entries for: " . implode(', ', $parameters),
            default => "{$fieldLabel} is invalid.",
        };
    }

    /**
     * Get human-readable field label.
     */
    private static function getFieldLabel(string $field): string
    {
        $labels = [
            'name' => 'your name',
            'email' => 'email address',
            'password' => 'password',
            'password_confirmation' => 'password confirmation',
            'phone' => 'phone number',
            'countryCode' => 'country code',
            'role' => 'role',
            'branch_id' => 'branch',
            'document_type' => 'document type',
            'document_number' => 'document number',
            'full_name' => 'full name',
            'date_of_birth' => 'date of birth',
            'license_category' => 'license category',
            'issue_date' => 'issue date',
            'expiry_date' => 'expiry date',
            'issuing_authority' => 'issuing authority',
            'issuing_country' => 'issuing country',
            'university_name' => 'university name',
            'department' => 'department',
            'front_document' => 'front document',
            'back_document' => 'back document',
            'vehicle_id' => 'vehicle',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'pickup_location' => 'pickup location',
            'dropoff_location' => 'dropoff location',
            'category_id' => 'category',
            'brand' => 'brand',
            'model' => 'model',
            'year' => 'year',
            'license_plate' => 'license plate',
            'vin' => 'VIN',
            'color' => 'color',
            'mileage' => 'mileage',
            'fuel_type' => 'fuel type',
            'transmission' => 'transmission',
            'seats' => 'seats',
            'daily_rate' => 'daily rate',
            'status' => 'status',
            'code' => 'code',
            'address' => 'address',
            'city' => 'city',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'opening_time' => 'opening time',
            'closing_time' => 'closing time',
            'manager_id' => 'manager',
            'amount' => 'amount',
            'payment_method' => 'payment method',
            'currency' => 'currency',
            'booking_id' => 'booking',
            'review' => 'review',
            'rating' => 'rating',
            'comment' => 'comment',
            'title' => 'title',
            'description' => 'description',
            'message' => 'message',
            'subject' => 'subject',
            'token' => 'reset token',
        ];

        return $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    /**
     * Translate all validation errors to user-friendly messages.
     */
    public static function translateErrors(array $errors): array
    {
        $translated = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                // Try to extract rule and parameters from Laravel message
                $translated[] = self::translateMessage($field, $message);
            }
        }

        return $translated;
    }

    /**
     * Translate a single validation message.
     */
    private static function translateMessage(string $field, string $message): string
    {
        // Common Laravel default messages to friendly versions
        $patterns = [
            '/The (.+) field is required\./' => "Please enter $1.",
            '/The (.+) must be a string\./' => "$1 must be text.",
            '/The (.+) must be a valid email address\./' => "Please enter a valid email address.",
            '/The (.+) may not be greater than (\d+) characters\./' => "$1 must not exceed $2 characters.",
            '/The (.+) must be at least (\d+) characters\./' => "$1 must be at least $2 characters.",
            '/The (.+) confirmation does not match\./' => "$1 confirmation does not match.",
            '/The (.+) has already been taken\./' => "An account with this $1 already exists. Please use a different one.",
            '/The selected (.+) is invalid\./' => "Please select a valid $1.",
            '/The (.+) must be a valid date\./' => "$1 must be a valid date.",
            '/The (.+) must be a date before (.+)\./' => "$1 must be before $2.",
            '/The (.+) must be a date after (.+)\./' => "$1 must be after $2.",
            '/The (.+) must be a number\./' => "$1 must be a number.",
            '/The (.+) must be an integer\./' => "$1 must be a whole number.",
            '/The (.+) must be a file\./' => "Please upload a valid file for $1.",
            '/The (.+) must have a valid file type\./' => "$1 must be a valid file type.",
            '/The (.+) may not be greater than (\d+) kilobytes\./' => "$1 is too large. Maximum size is $2 KB.",
            '/The (.+) field must be (\w+)\./' => "$1 must be $2.",
            '/The (.+) format is invalid\./' => "$1 format is invalid.",
        ];

        foreach ($patterns as $pattern => $replacement) {
            if (preg_match($pattern, $message)) {
                return preg_replace($pattern, $replacement, $message);
            }
        }

        // If no pattern matches, return with field label
        $fieldLabel = self::getFieldLabel($field);
        return str_replace($field, $fieldLabel, $message);
    }
}