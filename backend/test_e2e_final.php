<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

echo "=== Complete E2E Flow Test ===\n\n";

// ============================================
// TEST 1: Email Verification Flow
// ============================================
echo "=== TEST 1: Email Verification Flow ===\n\n";

$user1 = User::create([
    'name' => 'E2E User 1',
    'email' => 'e2e1' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
]);

echo "Created user: {$user1->email} (ID: {$user1->id})\n";

$hash = hash('sha256', $user1->getEmailForVerification());
$verificationUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
    'verification.verify',
    now()->addMinutes(60),
    ['id' => $user1->id, 'hash' => $hash]
);

echo "Verification URL: $verificationUrl\n";

// Test verification
$request = \Illuminate\Http\Request::create($verificationUrl, 'GET');
$route = new \Illuminate\Routing\Route('GET', '/api/auth/verify-email/{id}/{hash}', []);
$route->bind($request = \Illuminate\Http\Request::create($verificationUrl, 'GET'));
$route->setParameter('id', $user1->id);
$route->setParameter('hash', $hash);
$request->setRouteResolver(fn() => $route);

$controller = new \App\Http\Controllers\AuthController();
$response = $controller->verifyEmail($request);

echo "Verification response: " . get_class($response) . " - " . $response->getStatusCode() . "\n";
if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "Redirect to: " . $response->getTargetUrl() . "\n";
}

$user1->refresh();
echo "Email verified: " . ($user1->hasVerifiedEmail() ? 'YES' : 'NO') . "\n\n";

// ============================================
// TEST 2: Password Reset Flow
// ============================================
echo "\n=== TEST 2: Password Reset Flow ===\n\n";

$user2 = User::create([
    'name' => 'E2E User 2',
    'email' => 'e2e2' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
]);

echo "Created user: {$user2->email} (ID: {$user2->id})\n";

// Request password reset
$status = \Illuminate\Support\Facades\Password::sendResetLink(['email' => $user2->email]);
echo "Send reset link status: $status\n";

// Get token from database and replace with known token
$rawToken = 'e2e-test-token-' . Str::random(20);
$hashedToken = Hash::make($rawToken);
DB::table('password_reset_tokens')->where('email', $user2->email)->update([
    'token' => Hash::make($rawToken),
    'created_at' => now(),
]);

echo "Raw token: $rawToken\n";

// Test password reset via controller
$resetRequest = Request::create('/api/auth/reset-password', 'POST', [
    'token' => $rawToken,
    'email' => $user2->email,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123',
]);

$resetController = new \App\Http\Controllers\AuthController();
$resetResponse = $resetController->resetPassword($resetRequest);

echo "Reset response: " . $resetResponse->getContent() . "\n";

$user2->refresh();
if (Hash::check('newpassword123', $user2->password)) {
    echo "Password reset: SUCCESS\n";
} else {
    echo "Password reset: FAILED\n";
}

// Test invalid token
echo "\n--- Test Invalid Token ---\n";
$user3 = User::create([
    'name' => 'E2E User 3',
    'email' => 'e2e3' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
});

DB::table('password_reset_tokens')->insert([
    'email' => $user3->email,
    'token' => Hash::make('valid-token-' . Str::random(20)),
    'created_at' => now(),
]);

$invalidResetRequest = Request::create('/api/auth/reset-password', 'POST', [
    'token' => 'invalid-token',
    'email' => $user3->email,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123',
]);

$invalidResetResponse = (new \App\Http\Controllers\AuthController())->resetPassword($invalidResetRequest);
echo "Invalid token response: " . $invalidResetResponse->getContent() . "\n";

// Test expired token
echo "\n--- Test Expired Token ---\n";
$user4 = User::create([
    'name' => 'E2E User 4',
    'email' => 'e2e4' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
]);

DB::table('password_reset_tokens')->insert([
    'email' => $user4->email,
    'token' => Hash::make('expired-token-' . Str::random(20)),
    'created_at' => now()->subHours(2),
]);

$expiredToken = 'expired-token-test';
DB::table('password_reset_tokens')->where('email', $user4->email)->update([
    'token' => Hash::make($expiredToken),
    'created_at' => now()->subHours(2),
]);

$expiredResetRequest = Request::create('/api/auth/reset-password', 'POST', [
    'token' => $expiredToken,
    'email' => $user4->email,
    'password' => 'newpassword123',
    'password_confirmation' => 'newpassword123',
]);

$expiredResetResponse = (new \App\Http\Controllers\AuthController())->resetPassword($expiredResetRequest);
echo "Expired token response: " . $expiredResetResponse->getContent() . "\n";

echo "\n=== All E2E Tests Completed ===\n";