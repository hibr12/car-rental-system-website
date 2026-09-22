<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::where('email', 'hibruy@gmail.com')->first();

if ($user) {
    echo "User found:\n";
    echo "  Email: {$user->email}\n";
    echo "  ID: {$user->id}\n";
    echo "  Name: {$user->name}\n";
    echo "  Role: {$user->role}\n";
    echo "  Email Verified: " . ($user->email_verified_at ? 'YES (' . $user->email_verified_at . ')' : 'NO') . "\n";
    echo "  Branch ID: " . ($user->branch_id ?? 'None') . "\n";
    echo "  Created At: {$user->created_at}\n";
} else {
    echo "User NOT found with email: hibruy@gmail.com\n";
}