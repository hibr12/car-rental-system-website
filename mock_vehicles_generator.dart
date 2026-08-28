import 'dart:io';
import 'dart:math';

void main() {
  final brandsModels = {
    'Toyota': [
      'Camry',
      'Corolla',
      'RAV4',
      'Highlander',
      'Prius',
      'Supra',
      'Tacoma'
    ],
    'Hyundai': [
      'Elantra',
      'Sonata',
      'Tucson',
      'Santa Fe',
      'Palisade',
      'Kona',
      'Ioniq 5'
    ],
    'Kia': [
      'Forte',
      'Optima',
      'Sportage',
      'Sorento',
      'Telluride',
      'Stinger',
      'EV6'
    ],
    'Tesla': ['Model 3', 'Model Y', 'Model S', 'Model X', 'Cybertruck'],
    'BMW': ['3 Series', '5 Series', 'X3', 'X5', 'M4', 'M3', 'X7', 'i4'],
    'Mercedes': [
      'C-Class',
      'E-Class',
      'GLC',
      'GLE',
      'S-Class',
      'G-Class',
      'EQS'
    ],
    'Audi': ['A4', 'A6', 'Q5', 'Q7', 'e-tron', 'RS5', 'Q8'],
    'Land Rover': ['Range Rover', 'Defender', 'Discovery', 'Velar', 'Evoque'],
    'Ford': ['Mustang', 'Explorer', 'F-150', 'Escape', 'Bronco', 'Mach-E'],
    'Chevrolet': [
      'Camaro',
      'Silverado',
      'Equinox',
      'Tahoe',
      'Malibu',
      'Corvette'
    ],
    'Nissan': ['Altima', 'Sentra', 'Rogue', 'Pathfinder', 'GT-R', 'Armada'],
    'Honda': ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey', 'Ridgeline'],
    'Mazda': ['Mazda3', 'Mazda6', 'CX-5', 'CX-9', 'MX-5 Miata'],
    'Jeep': ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass', 'Gladiator'],
    'Lexus': ['IS', 'ES', 'RX', 'NX', 'GX', 'LC', 'LX']
  };

  final colors = [
    'Black',
    'White',
    'Silver',
    'Gray',
    'Red',
    'Blue',
    'Midnight Green',
    'Champagne',
    'Matte Black',
    'Navy'
  ];
  final transmissions = ['Automatic', 'Manual'];
  final fuelTypes = ['Gasoline', 'Hybrid', 'Electric', 'Diesel'];
  final locations = [
    'New York, NY',
    'Los Angeles, CA',
    'Chicago, IL',
    'Houston, TX',
    'Miami, FL',
    'San Francisco, CA',
    'Seattle, WA',
    'Austin, TX',
    'Denver, CO',
    'Las Vegas, NV',
    'Boston, MA',
    'Atlanta, GA',
    'San Diego, CA',
    'Phoenix, AZ',
    'Dallas, TX'
  ];

  final ownerNames = [
    'Michael Scott',
    'Sarah Connor',
    'John Doe',
    'Jane Smith',
    'Alex Johnson',
    'Emily Davis',
    'Chris Evans',
    'Tony Stark',
    'Bruce Wayne',
    'Clark Kent',
    'Diana Prince',
    'Peter Parker',
    'Natasha Romanoff',
    'Steve Rogers',
    'Wanda Maximoff',
    'David Miller',
    'Jessica Wilson',
    'Daniel Taylor',
    'Laura Moore',
    'James Anderson'
  ];

  final random = Random();
  String dartCode = "import '../models/vehicle_model.dart';\n";
  dartCode += "import '../models/owner_model.dart';\n\n";
  dartCode += "final List<Vehicle> mockVehicles = [\n";

  int idCounter = 1;

  for (final brandEntry in brandsModels.entries) {
    final brand = brandEntry.key;
    final models = brandEntry.value;

    for (final model in models) {
      for (int i = 0; i < 2; i++) {
        final year = 2018 + random.nextInt(7); // 2018-2024

        int basePrice;
        String category;
        if (['Tesla', 'BMW', 'Mercedes', 'Audi', 'Land Rover', 'Lexus']
                .contains(brand) ||
            ['Corvette', 'GT-R', 'Supra'].contains(model)) {
          basePrice = 80 + random.nextInt(151);
          category = 'Luxury';
        } else {
          basePrice = 35 + random.nextInt(66);
          category = 'Economy';
        }

        String fuel;
        if (brand == 'Tesla' ||
            ['Ioniq 5', 'EV6', 'i4', 'EQS', 'e-tron', 'Mach-E']
                .contains(model)) {
          fuel = 'Electric';
          category = 'Electric';
        } else {
          fuel = fuelTypes[random.nextInt(fuelTypes.length)];
          if (fuel == 'Electric') {
            category = 'Electric';
          }
        }

        final suvModels = [
          'RAV4',
          'Highlander',
          'Tucson',
          'Santa Fe',
          'Palisade',
          'Kona',
          'Sportage',
          'Sorento',
          'Telluride',
          'Model X',
          'X3',
          'X5',
          'X7',
          'GLC',
          'GLE',
          'G-Class',
          'Q5',
          'Q7',
          'Q8',
          'Range Rover',
          'Defender',
          'Discovery',
          'Velar',
          'Evoque',
          'Explorer',
          'Escape',
          'Bronco',
          'Equinox',
          'Tahoe',
          'Rogue',
          'Pathfinder',
          'Armada',
          'CR-V',
          'Pilot',
          'CX-5',
          'CX-9',
          'Wrangler',
          'Grand Cherokee',
          'Cherokee',
          'Compass',
          'RX',
          'NX',
          'GX',
          'LX'
        ];

        if (suvModels.contains(model)) {
          category = 'SUV';
        }

        final ratingStr =
            (4.0 + (random.nextDouble() * 1.0)).toStringAsFixed(1);
        final reviewCount = 5 + random.nextInt(200);
        final seats = category == 'SUV'
            ? (random.nextBool() ? 5 : 7)
            : (['MX-5 Miata', 'Supra', 'Corvette'].contains(model) ? 2 : 5);
        final color = colors[random.nextInt(colors.length)];
        final location = locations[random.nextInt(locations.length)];
        final isAvail = random.nextInt(4) != 0;
        final isFeat = random.nextInt(6) == 0;
        final transmission =
            transmissions[random.nextInt(transmissions.length)];

        List<String> features = [
          "'Bluetooth'",
          "'Backup Camera'",
          "'Apple CarPlay'"
        ];
        if (category == 'Luxury') {
          features.addAll(["'Sunroof'", "'Heated Seats'", "'Premium Audio'"]);
        }
        if (category == 'SUV') {
          features.add("'All-Wheel Drive'");
        }
        if (category == 'Electric') {
          features.add("'Autopilot'");
        }
        final feats = features.join(', ');

        // Unsplash random car photo placeholder, to ensure variety we use random seed
        final imageUrl1 =
            "'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800&sig=$idCounter'";
        final imageUrl2 =
            "'https://images.unsplash.com/photo-1494976388531-d1058494cdd8?auto=format&fit=crop&q=80&w=800&sig=$idCounter'";
        final imageUrl3 =
            "'https://images.unsplash.com/photo-1503376712396-6b0800b12f14?auto=format&fit=crop&q=80&w=800&sig=$idCounter'";

        final ownerName = ownerNames[random.nextInt(ownerNames.length)];
        final ownerImg =
            "'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=200&sig=$idCounter'";
        final ownerRating =
            (4.5 + (random.nextDouble() * 0.5)).toStringAsFixed(1);
        final ownerTrips = 10 + random.nextInt(300);
        final isSuperHost = random.nextBool();
        final responseTime =
            random.nextBool() ? "'Under 1 hour'" : "'Under 3 hours'";

        dartCode += '''  Vehicle(
    id: 'v$idCounter',
    brand: '$brand',
    model: '$model',
    year: $year,
    pricePerDay: $basePrice.0,
    rating: $ratingStr,
    reviewCount: $reviewCount,
    fuelType: '$fuel',
    transmission: '$transmission',
    seats: $seats,
    color: '$color',
    location: '$location',
    isAvailable: $isAvail,
    description: 'Experience the thrill of driving this beautifully maintained $brand $model. This $year $category offers a perfect blend of comfort, performance, and style, ready for your next adventure in $location.',
    imageUrls: [$imageUrl1, $imageUrl2, $imageUrl3],
    features: [$feats],
    category: '$category',
    isFeatured: $isFeat,
    owner: Owner(
      id: 'o$idCounter',
      name: '$ownerName',
      profileImageUrl: $ownerImg,
      rating: $ownerRating,
      totalTrips: $ownerTrips,
      isSuperHost: $isSuperHost,
      memberSince: DateTime.now().subtract(const Duration(days: ${100 + random.nextInt(1000)})),
      responseTime: $responseTime,
      responseRate: '100%',
    ),
  ),
''';
        idCounter++;
      }
    }
  }

  dartCode += "];\n";
  File('lib/mock_data/mock_vehicles.dart').writeAsStringSync(dartCode);
}
