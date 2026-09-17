<?php

require_once "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

use App\Models\User;
use App\Models\DriverLicense;

echo "=== Debug: Customer License Lookup ===" . PHP_EOL . PHP_EOL;

// Find a customer user
$user = User::where('role', 'customer')->first();

if (!$user) {
    echo "No customer user found in database." . PHP_EOL;
    exit(1);
}

echo "User ID: " . $user->id . PHP_EOL;
echo "User role: " . $user->role . PHP_EOL;

// Check for existing licenses
$licenses = DriverLicense::where('user_id', $user->id)->get();
echo "Number of licenses found: " . $licenses->count() . PHP_EOL;

$licenses->each(function ($license) {
    echo "License ID: " . $license->id . PHP_EOL;
    echo "  document_type: " . $license->document_type . PHP_EOL;
    echo "  status: " . $license->status . PHP_EOL;
    echo "  full_name: " . $license->full_name . PHP_EOL;
    echo "  front_document_path: " . ($license->front_document_path ?: "null") . PHP_EOL;
    echo "  back_document_path: " . ($license->back_document_path ?: "null") . PHP_EOL;
    echo "  license_category: " . ($license->license_category ?: "null") . PHP_EOL;
    echo "  document_number: " . ($license->document_number ?: "null") . PHP_EOL;
    echo PHP_EOL;
});

// Test the service - getActiveLicense
echo "=== Testing DriverLicenseService::getActiveLicense ===" . PHP_EOL . PHP_EOL;

\Illuminate\Support\Facades\Storage::fake("local");

try {
    $activeLicense = \App\Services\DriverLicenseService::getActiveLicense($user);
    // Need to instantiate the service properly
    echo "Need to use IoC container..." . PHP_EOL;
    
    // Let's just check what the model query returns directly
    echo PHP_EOL . "=== Direct Model Query ===" . PHP_EOL;
    $directLicense = DriverLicense::where('user_id', $user->id)
        ->whereNotIn('status', [DriverLicense::STATUS_REPLACED])
        ->latest()
        ->first();
    
    echo "Direct query result: " . ($directLicense ? "found (ID: " . $directLicense->id . ")" : "null") . PHP_EOL;
    if ($directLicense) {
        echo "Status: " . $directLicense->status . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . substr($e->getTraceAsString(), 0, 500) . PHP_EOL;
}

echo PHP_EOL . "=== Done ===" . PHP_EOL;