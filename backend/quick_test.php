<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Services\DriverLicenseService;

echo "=== Test Driver License ===\n\n";

$user = User::create([
    'name' => 'Test User',
    'email' => 'test' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
]);

echo "Created user: {$user->email} (ID: {$user->id})\n";

// Test license submission
$licenseService = app(\App\Services\DriverLicenseService::class);

try {
    $license = $licenseService->submit([
        'document_type' => 'driver_license',
        'document_number' => 'TEST' . time(),
        'full_name' => 'Test User',
        'license_category' => 'automobile',
        'issue_date' => '2020-01-01',
        'expiry_date' => '2030-01-01',
    ], User::first(), null, null);
    
    echo "SUCCESS: License created with ID {$license->id}\n";
    echo "Status: {$license->status}\n";
    echo "Document type: {$license->document_type}\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Completed ===\n";