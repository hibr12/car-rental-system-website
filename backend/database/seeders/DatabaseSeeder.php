<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\ContactMessage;
use App\Models\Maintenance;
use App\Models\Payment;
use App\Models\Review;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleImage;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedCompany();
        $this->seedUsers();
        $this->seedBranches();
        $this->call(InitialBranchesSeeder::class);
        $this->seedCategories();
        $this->seedVehicles();
        $this->seedBookings();
        $this->seedPayments();
        $this->seedReviews();
        $this->seedMaintenance();
        $this->seedContactMessages();
    }

    private function seedCompany(): void
    {
        $company = Company::updateOrCreate(
            ['code' => 'APEX'],
            [
                'name' => 'Apex Rentals',
                'address' => '123 Main Street, Addis Ababa, Ethiopia',
                'phone' => '+251 11 123 4567',
                'email' => 'info@apexrentals.com',
                'is_active' => true,
            ]
        );

        config(['app.company_id' => $company->id]);
    }

    private function seedBranches(): void
    {
        $company = Company::where('code', 'APEX')->first();

        $branchesData = [
            [
                'name' => 'Bole Branch',
                'code' => 'BOLE',
                'address' => 'Bole Subcity, Addis Ababa',
                'city' => 'Addis Ababa',
                'phone' => '+251 11 111 1111',
                'email' => 'bole@apexrentals.com',
                'latitude' => 9.0320,
                'longitude' => 38.7529,
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'CMC Branch',
                'code' => 'CMC',
                'address' => 'CMC Circle, Addis Ababa',
                'city' => 'Addis Ababa',
                'phone' => '+251 11 222 2222',
                'email' => 'cmc@apexrentals.com',
                'latitude' => 9.0350,
                'longitude' => 38.7667,
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'Airport Branch',
                'code' => 'AIRPORT',
                'address' => 'Bole International Airport, Addis Ababa',
                'city' => 'Addis Ababa',
                'phone' => '+251 11 333 3333',
                'email' => 'airport@apexrentals.com',
                'latitude' => 8.9711,
                'longitude' => 38.7826,
                'opening_time' => '06:00:00',
                'closing_time' => '22:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'Kazanchis Branch',
                'code' => 'KAZANCHIS',
                'address' => 'Kazanchis, Addis Ababa',
                'city' => 'Addis Ababa',
                'phone' => '+251 11 444 4444',
                'email' => 'kazanchis@apexrentals.com',
                'latitude' => 9.0107,
                'longitude' => 38.7766,
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'status' => 'active',
            ],
            [
                'name' => 'Piassa Branch',
                'code' => 'PIASSA',
                'address' => 'Piassa, Addis Ababa',
                'city' => 'Addis Ababa',
                'phone' => '+251 11 555 5555',
                'email' => 'piassa@apexrentals.com',
                'latitude' => 9.0333,
                'longitude' => 38.7500,
                'opening_time' => '08:00:00',
                'closing_time' => '18:00:00',
                'status' => 'active',
            ],
        ];

        foreach ($branchesData as $branchData) {
            Branch::updateOrCreate(
                ['code' => $branchData['code']],
                array_merge($branchData, ['company_id' => $company->id])
            );
        }

        $boleBranch = Branch::where('code', 'BOLE')->first();
        $cmcBranch = Branch::where('code', 'CMC')->first();
        $kazBranch = Branch::where('code', 'KAZ')->first()
            ?? Branch::where('code', 'KAZANCHIS')->first();
        $airportBranch = Branch::where('code', 'AIRPORT')->first();

        if ($boleBranch) {
            $boleManager = User::updateOrCreate(
                ['email' => 'bole.manager@apexrentals.com'],
                [
                    'name' => 'Bole Branch Manager',
                    'password' => 'password',
                    'phone' => '+251 11 111 0000',
                    'role' => User::ROLE_BRANCH_MANAGER,
                    'branch_id' => $boleBranch->id,
                ]
            );
            $boleBranch->update(['manager_id' => $boleManager->id]);
        }

        if ($cmcBranch) {
            $cmcBranch->update([
                'manager_id' => User::updateOrCreate(
                    ['email' => 'cmc.manager@apexrentals.com'],
                    [
                        'name' => 'CMC Branch Manager',
                        'password' => 'password',
                        'phone' => '+251 11 222 0000',
                        'role' => User::ROLE_BRANCH_MANAGER,
                        'branch_id' => $cmcBranch->id,
                    ]
                )->id,
            ]);
        }

        if ($kazBranch) {
            $kazManager = User::updateOrCreate(
                ['email' => 'kazanchis.manager@apexrentals.com'],
                [
                    'name' => 'Kazanchis Branch Manager',
                    'password' => 'password',
                    'phone' => '+251 11 444 0000',
                    'role' => User::ROLE_BRANCH_MANAGER,
                    'branch_id' => $kazBranch->id,
                ]
            );
            $kazBranch->update(['manager_id' => $kazManager->id]);
        }

        if ($airportBranch) {
            $airportBranch->update([
                'manager_id' => User::updateOrCreate(
                    ['email' => 'airport.manager@apexrentals.com'],
                    [
                        'name' => 'Airport Branch Manager',
                        'password' => 'password',
                        'phone' => '+251 11 333 0000',
                        'role' => User::ROLE_BRANCH_MANAGER,
                        'branch_id' => $airportBranch->id,
                    ]
                )->id,
            ]);
        }

        $piassaBranch = Branch::where('code', 'PIASSA')->first();
        if ($piassaBranch) {
            $piassaManager = User::updateOrCreate(
                ['email' => 'piassa.manager@apexrentals.com'],
                [
                    'name' => 'Piassa Branch Manager',
                    'password' => 'password',
                    'phone' => '+251 11 555 0000',
                    'role' => User::ROLE_BRANCH_MANAGER,
                    'branch_id' => $piassaBranch->id,
                ]
            );
            $piassaBranch->update(['manager_id' => $piassaManager->id]);
        }
    }

    private function seedUsers(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin@carrental.com'],
            [
                'name' => 'Admin User',
                'password' => 'password',
                'phone' => '+251 11 123 4567',
                'role' => User::ROLE_COMPANY_ADMIN,
            ]
        );

        $fleetManager = User::updateOrCreate(
            ['email' => 'fleet@carrental.com'],
            [
                'name' => 'Fleet Manager',
                'password' => 'password',
                'phone' => '+251 11 123 4568',
                'role' => User::ROLE_FLEET_MANAGER,
                'branch_id' => null,
            ]
        );

        $boleBranch = Branch::where('code', 'BOLE')->first();
        $cmcBranch = Branch::where('code', 'CMC')->first();

        $staff = User::updateOrCreate(
            ['email' => 'staff@carrental.com'],
            [
                'name' => 'Branch Staff',
                'password' => 'password',
                'phone' => '+251 11 123 4569',
                'role' => User::ROLE_BRANCH_STAFF,
                'branch_id' => $boleBranch?->id,
            ]
        );

        if (User::where('role', User::ROLE_CUSTOMER)->count() < 5) {
            User::factory()->count(5)->customer()->create();
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Economy', 'slug' => 'economy', 'description' => 'Affordable and fuel-efficient vehicles for daily commuting'],
            ['name' => 'Compact', 'slug' => 'compact', 'description' => 'Compact cars perfect for city driving'],
            ['name' => 'Sedan', 'slug' => 'sedan', 'description' => 'Comfortable mid-size cars perfect for business and family travel'],
            ['name' => 'SUV', 'slug' => 'suv', 'description' => 'Spacious sport utility vehicles for all terrain and family adventures'],
            ['name' => 'Luxury', 'slug' => 'luxury', 'description' => 'Premium high-end vehicles for an exceptional driving experience'],
            ['name' => 'Sports', 'slug' => 'sports', 'description' => 'High-performance vehicles for speed enthusiasts'],
            ['name' => 'Electric', 'slug' => 'electric', 'description' => 'Eco-friendly electric vehicles for sustainable transportation'],
            ['name' => 'Van', 'slug' => 'van', 'description' => 'Spacious vans for group travel and cargo transport'],
            ['name' => 'Pickup', 'slug' => 'pickup', 'description' => 'Versatile pickup trucks for work and adventure'],
            ['name' => '4x4', 'slug' => '4x4', 'description' => 'Four-wheel drive vehicles for off-road capability'],
            ['name' => 'Minibus', 'slug' => 'minibus', 'description' => 'Large capacity vehicles for group transportation'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }

    private function seedVehicles(): void
    {
        $categories = Category::all()->keyBy('slug');
        $admin = User::where('email', 'admin@carrental.com')->first();
        $branches = Branch::all();
        $branchMap = [];
        foreach ($branches as $branch) {
            $branchMap[strtolower($branch->name)] = $branch->id;
        }

        $vehicles = [
            ['category_slug' => 'economy', 'brand' => 'Toyota', 'model' => 'Yaris', 'year' => 2024, 'registration_number' => 'ECO-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 35, 'status' => 'available', 'featured' => false, 'location' => 'Bole Branch', 'description' => 'Compact and fuel-efficient, perfect for city driving and daily commutes.'],
            ['category_slug' => 'economy', 'brand' => 'Honda', 'model' => 'Fit', 'year' => 2023, 'registration_number' => 'ECO-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Silver', 'rental_price_per_day' => 38, 'status' => 'available', 'featured' => false, 'location' => 'CMC Branch', 'description' => 'Versatile hatchback with excellent fuel economy and spacious interior.'],
            ['category_slug' => 'sedan', 'brand' => 'Toyota', 'model' => 'Camry', 'year' => 2024, 'registration_number' => 'SED-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 65, 'status' => 'available', 'featured' => true, 'location' => 'Bole Branch', 'description' => 'Elegant mid-size sedan with premium comfort and advanced safety features.'],
            ['category_slug' => 'sedan', 'brand' => 'Honda', 'model' => 'Accord', 'year' => 2023, 'registration_number' => 'SED-002', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Blue', 'rental_price_per_day' => 70, 'status' => 'available', 'featured' => true, 'location' => 'Airport Branch', 'description' => 'Hybrid sedan combining efficiency with a refined driving experience.'],
            ['category_slug' => 'suv', 'brand' => 'Ford', 'model' => 'Explorer', 'year' => 2024, 'registration_number' => 'SUV-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 7, 'color' => 'Gray', 'rental_price_per_day' => 95, 'status' => 'available', 'featured' => true, 'location' => 'Bole Branch', 'description' => 'Full-size SUV with three-row seating, ideal for family adventures.'],
            ['category_slug' => 'suv', 'brand' => 'Hyundai', 'model' => 'Tucson', 'year' => 2024, 'registration_number' => 'SUV-002', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Red', 'rental_price_per_day' => 80, 'status' => 'available', 'featured' => false, 'location' => 'Airport Branch', 'description' => 'Modern hybrid SUV with bold design and advanced tech features.'],
            ['category_slug' => 'suv', 'brand' => 'Kia', 'model' => 'Sportage', 'year' => 2023, 'registration_number' => 'SUV-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 75, 'status' => 'maintenance', 'featured' => false, 'location' => 'Bole Branch', 'description' => 'Stylish compact SUV with excellent warranty and features.'],
            ['category_slug' => 'luxury', 'brand' => 'BMW', 'model' => '5 Series', 'year' => 2024, 'registration_number' => 'LUX-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 180, 'status' => 'available', 'featured' => true, 'location' => 'Bole Branch', 'description' => 'Executive luxury sedan with cutting-edge technology and performance.'],
            ['category_slug' => 'luxury', 'brand' => 'Mercedes-Benz', 'model' => 'C-Class', 'year' => 2024, 'registration_number' => 'LUX-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Silver', 'rental_price_per_day' => 200, 'status' => 'available', 'featured' => true, 'location' => 'CMC Branch', 'description' => 'Iconic luxury sedan offering unparalleled comfort and prestige.'],
            ['category_slug' => 'luxury', 'brand' => 'Audi', 'model' => 'A4', 'year' => 2023, 'registration_number' => 'LUX-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 170, 'status' => 'rented', 'featured' => false, 'location' => 'Airport Branch', 'description' => 'Sophisticated German engineering with Quattro all-wheel drive.'],
            ['category_slug' => 'sports', 'brand' => 'Ford', 'model' => 'Mustang', 'year' => 2024, 'registration_number' => 'SPT-001', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 4, 'color' => 'Red', 'rental_price_per_day' => 150, 'status' => 'available', 'featured' => true, 'location' => 'Airport Branch', 'description' => 'Legendary American muscle car with thrilling V8 performance.'],
            ['category_slug' => 'sports', 'brand' => 'BMW', 'model' => 'Z4', 'year' => 2023, 'registration_number' => 'SPT-002', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 2, 'color' => 'Blue', 'rental_price_per_day' => 160, 'status' => 'available', 'featured' => false, 'location' => 'Kazanchis Branch', 'description' => 'Open-top roadster delivering pure driving pleasure.'],
            ['category_slug' => 'electric', 'brand' => 'Tesla', 'model' => 'Model 3', 'year' => 2024, 'registration_number' => 'ELE-001', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'White', 'rental_price_per_day' => 90, 'status' => 'available', 'featured' => true, 'location' => 'Bole Branch', 'description' => 'All-electric sedan with Autopilot and impressive range.'],
            ['category_slug' => 'electric', 'brand' => 'Tesla', 'model' => 'Model Y', 'year' => 2024, 'registration_number' => 'ELE-002', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Black', 'rental_price_per_day' => 110, 'status' => 'available', 'featured' => true, 'location' => 'Kazanchis Branch', 'description' => 'Electric crossover SUV with spacious interior and long range.'],
            ['category_slug' => 'electric', 'brand' => 'Nissan', 'model' => 'Leaf', 'year' => 2023, 'registration_number' => 'ELE-003', 'fuel_type' => 'electric', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Green', 'rental_price_per_day' => 60, 'status' => 'available', 'featured' => false, 'location' => 'Piassa Branch', 'description' => 'Affordable electric hatchback for eco-conscious drivers.'],
            ['category_slug' => 'van', 'brand' => 'Ford', 'model' => 'Transit', 'year' => 2024, 'registration_number' => 'VAN-001', 'fuel_type' => 'diesel', 'transmission' => 'automatic', 'seats' => 12, 'color' => 'White', 'rental_price_per_day' => 120, 'status' => 'available', 'featured' => false, 'location' => 'Airport Branch', 'description' => 'Full-size passenger van for group travel and corporate events.'],
            ['category_slug' => 'van', 'brand' => 'Mercedes-Benz', 'model' => 'Sprinter', 'year' => 2023, 'registration_number' => 'VAN-002', 'fuel_type' => 'diesel', 'transmission' => 'automatic', 'seats' => 8, 'color' => 'Silver', 'rental_price_per_day' => 140, 'status' => 'available', 'featured' => false, 'location' => 'CMC Branch', 'description' => 'Premium passenger van with luxury amenities for VIP transport.'],
            ['category_slug' => 'sedan', 'brand' => 'Nissan', 'model' => 'Altima', 'year' => 2024, 'registration_number' => 'SED-003', 'fuel_type' => 'petrol', 'transmission' => 'automatic', 'seats' => 5, 'color' => 'Gray', 'rental_price_per_day' => 55, 'status' => 'available', 'featured' => false, 'location' => 'Kazanchis Branch', 'description' => 'Reliable mid-size sedan with comfortable ride and modern tech.'],
            ['category_slug' => 'economy', 'brand' => 'Kia', 'model' => 'Rio', 'year' => 2023, 'registration_number' => 'ECO-003', 'fuel_type' => 'petrol', 'transmission' => 'manual', 'seats' => 5, 'color' => 'Blue', 'rental_price_per_day' => 30, 'status' => 'unavailable', 'featured' => false, 'location' => 'Piassa Branch', 'description' => 'Budget-friendly subcompact with great value for money.'],
            ['category_slug' => 'suv', 'brand' => 'Toyota', 'model' => 'Highlander', 'year' => 2024, 'registration_number' => 'SUV-004', 'fuel_type' => 'hybrid', 'transmission' => 'automatic', 'seats' => 7, 'color' => 'Navy', 'rental_price_per_day' => 105, 'status' => 'available', 'featured' => true, 'location' => 'Kazanchis Branch', 'description' => 'Three-row hybrid SUV combining efficiency with family versatility.'],
        ];

        foreach ($vehicles as $vehicleData) {
            $category = $categories[$vehicleData['category_slug']] ?? $categories->first();
            $branchLocation = $vehicleData['location'] ?? 'Main Branch';
            $branchId = $branchMap[$branchLocation] ?? $mainBranch?->id;
            unset($vehicleData['category_slug']);

            $location = $vehicleData['location'];
            unset($vehicleData['location']);

            $branchId = $branchMap[strtolower($location)] ?? null;

            $vehicle = Vehicle::updateOrCreate(
                ['registration_number' => $vehicleData['registration_number']],
                array_merge($vehicleData, [
                    'category_id' => $category->id,
                    'branch_id' => $branchId,
                    'created_by' => $admin?->id,
                ])
            );

            if ($vehicle->images()->count() === 0) {
                $vehicleImages = $this->getVehicleImages($vehicle->brand, $vehicle->model, $vehicle->registration_number);
                $imageCount = count($vehicleImages);
                for ($i = 0; $i < $imageCount; $i++) {
                    VehicleImage::create([
                        'vehicle_id' => $vehicle->id,
                        'image_url' => $vehicleImages[$i],
                        'is_primary' => $i === 0,
                    ]);
                }
            }
        }
    }

    private function seedBookings(): void
    {
        if (Booking::count() > 0) {
            return;
        }

        $customers = User::where('role', 'customer')->get();
        $vehicles = Vehicle::whereIn('status', ['available', 'rented'])->get();

        if ($customers->isEmpty() || $vehicles->isEmpty()) {
            return;
        }

        $bookingsData = [
            [
                'customer_index' => 0,
                'vehicle_index' => 0,
                'pickup_location' => 'Main Branch',
                'return_location' => 'Main Branch',
                'pickup_offset' => -10,
                'return_offset' => -7,
                'status' => 'completed',
                'payment_status' => 'paid',
            ],
            [
                'customer_index' => 1,
                'vehicle_index' => 2,
                'pickup_location' => 'Downtown Branch',
                'return_location' => 'Airport Branch',
                'pickup_offset' => -5,
                'return_offset' => -2,
                'status' => 'completed',
                'payment_status' => 'paid',
            ],
            [
                'customer_index' => 2,
                'vehicle_index' => 4,
                'pickup_location' => 'Main Branch',
                'return_location' => 'Main Branch',
                'pickup_offset' => -1,
                'return_offset' => 2,
                'status' => 'active',
                'payment_status' => 'paid',
            ],
            [
                'customer_index' => 0,
                'vehicle_index' => 7,
                'pickup_location' => 'Airport Branch',
                'return_location' => 'Main Branch',
                'pickup_offset' => 3,
                'return_offset' => 6,
                'status' => 'confirmed',
                'payment_status' => 'pending',
            ],
            [
                'customer_index' => 3,
                'vehicle_index' => 10,
                'pickup_location' => 'Main Branch',
                'return_location' => 'Main Branch',
                'pickup_offset' => 5,
                'return_offset' => 8,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ],
            [
                'customer_index' => 4,
                'vehicle_index' => 12,
                'pickup_location' => 'Downtown Branch',
                'return_location' => 'Downtown Branch',
                'pickup_offset' => -15,
                'return_offset' => -13,
                'status' => 'completed',
                'payment_status' => 'paid',
            ],
            [
                'customer_index' => 1,
                'vehicle_index' => 1,
                'pickup_location' => 'Airport Branch',
                'return_location' => 'Airport Branch',
                'pickup_offset' => -3,
                'return_offset' => -1,
                'status' => 'cancelled',
                'payment_status' => 'unpaid',
            ],
            [
                'customer_index' => 2,
                'vehicle_index' => 5,
                'pickup_location' => 'Airport Branch',
                'return_location' => 'Main Branch',
                'pickup_offset' => 7,
                'return_offset' => 10,
                'status' => 'pending',
                'payment_status' => 'unpaid',
            ],
        ];

        foreach ($bookingsData as $data) {
            $customer = $customers[$data['customer_index']] ?? $customers->first();
            $vehicle = $vehicles[$data['vehicle_index']] ?? $vehicles->first();

            $pickupDate = Carbon::now()->addDays($data['pickup_offset']);
            $returnDate = Carbon::now()->addDays($data['return_offset']);
            $numberOfDays = (int) max(1, $pickupDate->diffInDays($returnDate));
            $pricePerDay = (float) $vehicle->rental_price_per_day;
            $subtotal = $numberOfDays * $pricePerDay;
            $totalPrice = $subtotal;

             $branchId = $vehicle->branch_id;

             Booking::create([
                 'booking_reference' => 'BK-' . $pickupDate->format('Ymd') . '-' . strtoupper(Str::random(6)),
                 'user_id' => $customer->id,
                 'vehicle_id' => $vehicle->id,
                 'branch_id' => $branchId,
                 'pickup_location' => $data['pickup_location'],
                 'return_location' => $data['return_location'],
                 'pickup_date' => $pickupDate,
                 'return_date' => $returnDate,
                 'number_of_days' => $numberOfDays,
                 'price_per_day' => $pricePerDay,
                 'subtotal' => $subtotal,
                 'additional_charges' => 0,
                 'discount' => 0,
                 'total_price' => $totalPrice,
                 'status' => $data['status'],
                 'payment_status' => $data['payment_status'],
                 'notes' => null,
             ]);
        }
    }

    private function seedPayments(): void
    {
        if (Payment::count() > 0) {
            return;
        }

        $paidBookings = Booking::where('payment_status', 'paid')->get();

        foreach ($paidBookings as $booking) {
            $paymentMethods = ['cash', 'bank_transfer', 'card', 'online_payment'];

            Payment::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'branch_id' => $booking->branch_id,
                'amount' => $booking->total_price,
                'currency' => 'ETB',
                'payment_method' => $paymentMethods[array_rand($paymentMethods)],
                'transaction_reference' => 'TXN-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8)),
                'gateway_reference' => 'GW-' . strtoupper(Str::random(12)),
                'status' => 'paid',
                'paid_at' => $booking->created_at->addDay(),
            ]);
        }
    }

    private function seedReviews(): void
    {
        if (Review::count() > 0) {
            return;
        }

        $completedBookings = Booking::where('status', 'completed')->get();

        $reviewComments = [
            5 => [
                'Absolutely fantastic experience! The car was in pristine condition and the service was outstanding.',
                'Perfect vehicle for our family trip. Clean, well-maintained, and exactly as described.',
                'Will definitely rent again. The booking process was seamless and the car exceeded expectations.',
            ],
            4 => [
                'Great car and smooth rental process. Minor issue with the pickup timing but overall very satisfied.',
                'Very comfortable ride. The vehicle was clean and performed well throughout our trip.',
                'Good value for money. The car was reliable and the staff was helpful.',
            ],
            3 => [
                'Decent car but could use some updates. The rental process was straightforward though.',
                'Average experience. The car was okay but not quite what I expected for the price.',
            ],
        ];

        foreach ($completedBookings as $booking) {
            $ratingRand = rand(1, 100);
            $rating = $ratingRand <= 10 ? 3 : ($ratingRand <= 40 ? 4 : 5);
            $comments = $reviewComments[$rating] ?? $reviewComments[4];

            Review::create([
                'user_id' => $booking->user_id,
                'vehicle_id' => $booking->vehicle_id,
                'booking_id' => $booking->id,
                'branch_id' => $booking->branch_id,
                'rating' => $rating,
                'comment' => $comments[array_rand($comments)],
                'status' => 'approved',
            ]);
        }
    }

    private function seedMaintenance(): void
    {
        if (Maintenance::count() > 0) {
            return;
        }

        $admin = User::where('email', 'admin@carrental.com')->first();
        $maintenanceVehicle = Vehicle::where('registration_number', 'SUV-003')->first();
        $vehicles = Vehicle::where('status', 'available')->take(3)->get();

        $maintenanceRecords = [
            [
                'vehicle' => $maintenanceVehicle,
                'title' => 'Scheduled Brake Inspection',
                'description' => 'Complete brake system inspection including pads, rotors, and brake fluid levels.',
                'maintenance_type' => 'Brake Service',
                'cost' => 280.00,
                'start_date' => Carbon::now()->subDays(5),
                'end_date' => null,
                'status' => 'in_progress',
                'notes' => 'Waiting for replacement brake pads to arrive.',
            ],
            [
                'vehicle' => $vehicles[0] ?? null,
                'title' => 'Annual Oil Change & Filter',
                'description' => 'Routine oil change with synthetic oil and new oil filter.',
                'maintenance_type' => 'Oil Change',
                'cost' => 120.00,
                'start_date' => Carbon::now()->addDays(10),
                'end_date' => null,
                'status' => 'scheduled',
                'notes' => 'Customer requested premium synthetic oil.',
            ],
            [
                'vehicle' => $vehicles[1] ?? null,
                'title' => 'Tire Rotation & Alignment',
                'description' => 'Four-wheel alignment and tire rotation for even wear distribution.',
                'maintenance_type' => 'Tire Service',
                'cost' => 180.00,
                'start_date' => Carbon::now()->subDays(20),
                'end_date' => Carbon::now()->subDays(19),
                'status' => 'completed',
                'notes' => 'All tires within safe tread depth. Alignment corrected.',
            ],
            [
                'vehicle' => $vehicles[2] ?? null,
                'title' => 'AC System Service',
                'description' => 'Air conditioning system inspection, refrigerant recharge, and filter replacement.',
                'maintenance_type' => 'AC Service',
                'cost' => 220.00,
                'start_date' => Carbon::now()->subDays(30),
                'end_date' => Carbon::now()->subDays(28),
                'status' => 'completed',
                'notes' => 'AC system fully restored to optimal performance.',
            ],
        ];

        foreach ($maintenanceRecords as $record) {
            if (!$record['vehicle']) {
                continue;
            }

            Maintenance::create([
                'vehicle_id' => $record['vehicle']->id,
                'branch_id' => $record['vehicle']->branch_id,
                'title' => $record['title'],
                'description' => $record['description'],
                'maintenance_type' => $record['maintenance_type'],
                'cost' => $record['cost'],
                'start_date' => $record['start_date'],
                'end_date' => $record['end_date'],
                'status' => $record['status'],
                'notes' => $record['notes'],
                'created_by' => $admin->id,
            ]);
        }
    }

    private function getVehicleImages(string $brand, string $model, string $reg): array
    {
        $vehicleKey = strtolower($brand . '-' . $model);

        $imageMap = [
            'toyota-yaris' => [
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&h=600&fit=crop',
            ],
            'honda-fit' => [
                'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
            ],
            'toyota-camry' => [
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1619682817481-e994891cd1f5?w=800&h=600&fit=crop',
            ],
            'honda-accord' => [
                'https://images.unsplash.com/photo-1619682817481-e994891cd1f5?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
            ],
            'ford-explorer' => [
                'https://images.unsplash.com/photo-1551830820-330a71b99659?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=800&h=600&fit=crop',
            ],
            'hyundai-tucson' => [
                'https://images.unsplash.com/photo-1633789242441-8a4206345e38?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1551830820-330a71b99659?w=800&h=600&fit=crop',
            ],
            'kia-sportage' => [
                'https://images.unsplash.com/photo-1633789242441-8a4206345e38?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&h=600&fit=crop',
            ],
            'bmw-5 series' => [
                'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1616455579100-2ceaa4eb2d37?w=800&h=600&fit=crop',
            ],
            'mercedes-benz-c-class' => [
                'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=600&fit=crop',
            ],
            'audi-a4' => [
                'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=600&fit=crop',
            ],
            'ford-mustang' => [
                'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1584345604476-8ec5f4527b10?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1544636331-e26879cd4d9b?w=800&h=600&fit=crop',
            ],
            'bmw-z4' => [
                'https://images.unsplash.com/photo-1555215695-3004980ad54e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617531653332-bd46c24f2068?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?w=800&h=600&fit=crop',
            ],
            'tesla-model 3' => [
                'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617886322168-72b886573c35?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=800&h=600&fit=crop',
            ],
            'tesla-model y' => [
                'https://images.unsplash.com/photo-1617886322168-72b886573c35?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=800&h=600&fit=crop',
            ],
            'nissan-leaf' => [
                'https://images.unsplash.com/photo-1593941707882-a5bba14938c7?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1560958089-b8a1929cea89?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1617886322168-72b886573c35?w=800&h=600&fit=crop',
            ],
            'ford-transit' => [
                'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&h=600&fit=crop',
            ],
            'mercedes-benz-sprinter' => [
                'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1570125909232-eb263c188f7e?w=800&h=600&fit=crop',
            ],
            'nissan-altima' => [
                'https://images.unsplash.com/photo-1617469767053-d3b523a0b982?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1619682817481-e994891cd1f5?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
            ],
            'kia-rio' => [
                'https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1621007947382-bb3c3994e3fb?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1606664515524-ed2f786a0bd6?w=800&h=600&fit=crop',
            ],
            'toyota-highlander' => [
                'https://images.unsplash.com/photo-1551830820-330a71b99659?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1609521263047-f8f205293f24?w=800&h=600&fit=crop',
                'https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800&h=600&fit=crop',
            ],
        ];

        $key = strtolower($brand . '-' . $model);

        if (isset($imageMap[$key])) {
            return $imageMap[$key];
        }

        return [
            "https://images.unsplash.com/photo-1549399542-7e3f8b79c341?w=800&h=600&fit=crop",
            "https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&h=600&fit=crop",
        ];
    }

    private function seedContactMessages(): void
    {
        if (ContactMessage::count() > 0) {
            return;
        }

        $messages = [
            [
                'name' => 'John Smith',
                'email' => 'john.smith@email.com',
                'phone' => '+1-555-0101',
                'subject' => 'Corporate Fleet Inquiry',
                'message' => 'We are interested in establishing a corporate account for our company. Could you provide information about fleet rental rates and long-term lease options?',
                'status' => 'pending',
            ],
            [
                'name' => 'Emily Johnson',
                'email' => 'emily.j@email.com',
                'phone' => '+1-555-0202',
                'subject' => 'Booking Modification Request',
                'message' => 'I have an existing booking and would like to extend my rental period by 2 additional days. Is this possible without additional penalty?',
                'status' => 'read',
            ],
            [
                'name' => 'Michael Brown',
                'email' => 'mbrown@email.com',
                'subject' => 'Vehicle Availability Question',
                'message' => 'Do you have any 7-seat SUVs available for the upcoming holiday weekend? I need to rent one for a family road trip.',
                'status' => 'replied',
                'replied_at' => Carbon::now()->subDays(2),
            ],
        ];

        foreach ($messages as $message) {
            ContactMessage::create($message);
        }
    }
}
