<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USERS ===\n";
foreach (App\Models\User::select('id','name','email','role')->get() as $u) {
    echo "  ID: {$u->id} | Email: {$u->email} | Role: {$u->role} | Name: {$u->name}\n";
}

echo "\n=== VEHICLES (first 5) ===\n";
foreach (App\Models\Vehicle::select('id','brand','model','rental_price_per_day','status')->take(5)->get() as $v) {
    echo "  ID: {$v->id} | {$v->brand} {$v->model} | Price/day: {$v->rental_price_per_day} | Status: {$v->status}\n";
}

echo "\n=== BOOKINGS ===\n";
foreach (App\Models\Booking::select('id','user_id','vehicle_id','booking_reference','status','payment_status','total_price')->get() as $b) {
    echo "  ID: {$b->id} | User: {$b->user_id} | Vehicle: {$b->vehicle_id} | Ref: {$b->booking_reference} | Status: {$b->status} | Payment: {$b->payment_status} | Total: {$b->total_price}\n";
}

echo "\n=== PAYMENTS ===\n";
foreach (App\Models\Payment::select('id','booking_id','user_id','amount','payment_method','status','transaction_reference')->get() as $p) {
    echo "  ID: {$p->id} | Booking: {$p->booking_id} | User: {$p->user_id} | Amount: {$p->amount} | Method: {$p->payment_method} | Status: {$p->status} | Ref: {$p->transaction_reference}\n";
}

echo "\n=== REVIEWS ===\n";
foreach (App\Models\Review::select('id','user_id','vehicle_id','booking_id','rating','status')->get() as $r) {
    echo "  ID: {$r->id} | User: {$r->user_id} | Vehicle: {$r->vehicle_id} | Booking: {$r->booking_id} | Rating: {$r->rating} | Status: {$r->status}\n";
}

echo "\n=== PASSWORDS ===\n";
echo "  admin@carrental.com -> password\n";
echo "  staff@carrental.com -> password\n";
echo "  customer@tet.com -> password\n";
echo "  sisay3575@gmail.com -> (check user)\n";
echo "  customer@tet.com -> password\n";
