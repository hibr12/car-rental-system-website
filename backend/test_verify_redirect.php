<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\URL;

echo "=== Test Email Verification Link ===\n\n";

// Create a fresh unverified user
$user = User::create([
    'name' => 'Test User',
    'email' => 'finaltest' . time() . '@example.com',
    'password' => bcrypt('password123'),
    'phone' => '1234567890',
    'role' => 'customer',
]);

echo "Created user: {$user->email} (ID: {$user->id})\n";
echo "Email verified before: " . ($user->hasVerifiedEmail() ? 'YES' : 'NO') . "\n";

$hash = hash('sha256', $user->getEmailForVerification());
echo "Hash: $hash\n";

// Generate verification URL exactly as Laravel does
$verificationUrl = URL::temporarySignedRoute(
    'verification.verify',
    now()->addMinutes(60),
    ['id' => $user->id, 'hash' => $hash]
);

echo "\nVerification URL: $verificationUrl\n";

// Create a proper request with route binding
$request = \Illuminate\Http\Request::create($verificationUrl, 'GET');

// Create a route with the parameters
$route = new \Illuminate\Routing\Route('GET', '/api/auth/verify-email/{id}/{hash}', []);
$route->bind($request);
$route->setParameter('id', $user->id);
$route->setParameter('hash', $hash);

// Set the route resolver
$request->setRouteResolver(function () use ($route) {
    return $route;
});

$controller = new \App\Http\Controllers\AuthController();

// Test the controller - it should return a redirect response
$response = $controller->verifyEmail($request);

echo "\nResponse class: " . get_class($response) . "\n";
echo "Response status: " . $response->getStatusCode() . "\n";

if ($response instanceof \Illuminate\Http\RedirectResponse) {
    echo "Redirect target: " . $response->getTargetUrl() . "\n";
} else {
    echo "Response content: " . $response->getContent() . "\n";
}

$user->refresh();
echo "\nEmail verified after: " . ($user->hasVerifiedEmail() ? 'YES' : 'NO') . "\n";

echo "\n=== Test completed ===\n";