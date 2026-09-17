<?php

require_once "vendor/autoload.php";
$app = require_once "bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

use App\Models\User;
use App\Models\DriverLicense;
use App\Services\DriverLicenseService;

echo "=== Driver License Submission Tests ===" . PHP_EOL . PHP_EOL;

// Test 1: Driver's license submission with front+back documents
echo "=== Test 1: Driver license with front+back ===" . PHP_EOL;

\Illuminate\Support\Facades\Storage::fake("local");

$service = app(DriverLicenseService::class);

try {
    $license = $service->submit([
        "document_type" => DriverLicense::DOCUMENT_TYPE_DRIVER_LICENSE,
        "full_name" => "Test User",
        "license_number" => "TEST123456",
        "license_category" => "automobile",
        "issue_date" => "2020-01-01",
        "expiry_date" => "2030-01-01",
    ], User::first(), 
    \Illuminate\Http\Testing\File::create("front.pdf", 100, "application/pdf"),
    \Illuminate\Http\Testing\File::create("back.pdf", 100, "application/pdf")
    );
    
    echo "SUCCESS: License created with ID " . $license->id . PHP_EOL;
    echo "Status: " . $license->status . PHP_EOL;
    echo "Front document: " . ($license->front_document_path ?: "none") . PHP_EOL;
    echo "Back document: " . ($license->back_document_path ?: "none") . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
    echo "Trace: " . substr($e->getTraceAsString(), 0, 200) . PHP_EOL;
}

// Test 2: National ID with single file (no back document required)
echo PHP_EOL . "=== Test 2: National ID with front only ===" . PHP_EOL;

$user2 = User::create([
    "name" => "National ID User",
    "email" => "national" . time() . "@example.com",
    "password" => bcrypt("password123"),
    "phone" => "1234567890",
    "role" => "customer",
]);

try {
    $license2 = $service->submit([
        "document_type" => DriverLicense::DOCUMENT_TYPE_NATIONAL_ID,
        "full_name" => "National ID User",
        "document_number" => "NID987654321",
    ], $user2, 
    \Illuminate\Http\Testing\File::create("front.pdf", 100, "application/pdf"),
    null  // No back document for national ID
    );
    
    echo "SUCCESS: National ID license created with ID " . $license2->id . PHP_EOL;
    echo "Status: " . $license2->status . PHP_EOL;
    echo "Front document: " . ($license2->front_document_path ?: "none") . PHP_EOL;
    echo "Back document: " . ($license2->back_document_path ?: "none") . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

// Test 3: University ID with single file
echo PHP_EOL . "=== Test 3: University ID with front only ===" . PHP_EOL;

$user3 = User::create([
    "name" => "University ID User",
    "email" => "university" . time() . "@example.com",
    "password" => bcrypt("password123"),
    "phone" => "1234567890",
    "role" => "customer",
]);

try {
    $license3 = $service->submit([
        "document_type" => DriverLicense::DOCUMENT_TYPE_UNIVERSITY_ID,
        "full_name" => "University ID User",
        "document_number" => "UID112233",
        "university_name" => "Test University",
    ], $user3, 
    \Illuminate\Http\Testing\File::create("front.pdf", 100, "application/pdf"),
    null  // No back document for university ID
    );
    
    echo "SUCCESS: University ID license created with ID " . $license3->id . PHP_EOL;
    echo "Status: " . $license3->status . PHP_EOL;
    echo "Front document: " . ($license3->front_document_path ?: "none") . PHP_EOL;
    echo "Back document: " . ($license3->back_document_path ?: "none") . PHP_EOL;
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== All tests completed ===" . PHP_EOL;