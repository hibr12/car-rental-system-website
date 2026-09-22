import random

brands_models = {
    'Toyota': ['Camry', 'Corolla', 'RAV4', 'Highlander', 'Prius'],
    'Hyundai': ['Elantra', 'Sonata', 'Tucson', 'Santa Fe', 'Palisade'],
    'Kia': ['Forte', 'Optima', 'Sportage', 'Sorento', 'Telluride'],
    'Tesla': ['Model 3', 'Model Y', 'Model S', 'Model X'],
    'BMW': ['3 Series', '5 Series', 'X3', 'X5', 'M4'],
    'Mercedes': ['C-Class', 'E-Class', 'GLC', 'GLE', 'S-Class'],
    'Audi': ['A4', 'A6', 'Q5', 'Q7', 'e-tron'],
    'Land Rover': ['Range Rover', 'Defender', 'Discovery'],
    'Ford': ['Mustang', 'Explorer', 'F-150', 'Escape', 'Bronco'],
    'Chevrolet': ['Camaro', 'Silverado', 'Equinox', 'Tahoe', 'Malibu'],
    'Nissan': ['Altima', 'Sentra', 'Rogue', 'Pathfinder', 'GT-R'],
    'Honda': ['Civic', 'Accord', 'CR-V', 'Pilot', 'Odyssey'],
    'Mazda': ['Mazda3', 'Mazda6', 'CX-5', 'CX-9'],
    'Jeep': ['Wrangler', 'Grand Cherokee', 'Cherokee', 'Compass'],
    'Lexus': ['IS', 'ES', 'RX', 'NX', 'GX']
}

colors = ['Black', 'White', 'Silver', 'Gray', 'Red', 'Blue']
transmissions = ['Automatic', 'Manual']
fuel_types = ['Gasoline', 'Hybrid', 'Electric', 'Diesel']
locations = ['New York, NY', 'Los Angeles, CA', 'Chicago, IL', 'Houston, TX', 'Miami, FL', 'San Francisco, CA']

def generate_vehicles():
    dart_code = "import '../models/vehicle_model.dart';\n\n"
    dart_code += "final List<Vehicle> mockVehicles = [\n"
    
    id_counter = 1
    
    # Ensure at least 100 cars, let's do 120 (approx 8 for each of the 15 brands)
    for brand, models in brands_models.items():
        for model in models:
            for _ in range(2): # 2 of each model = roughly 140 cars
                year = random.randint(2019, 2024)
                
                # Logic for pricing and categories
                if brand in ['Tesla', 'BMW', 'Mercedes', 'Audi', 'Land Rover', 'Lexus']:
                    base_price = random.randint(80, 200)
                    category = 'Luxury'
                else:
                    base_price = random.randint(35, 90)
                    category = 'Economy'
                
                if brand == 'Tesla':
                    fuel = 'Electric'
                else:
                    fuel = random.choice(fuel_types)
                
                if fuel == 'Electric':
                    category = 'Electric'
                    
                if model in ['RAV4', 'Highlander', 'Tucson', 'Santa Fe', 'Palisade', 'Sportage', 'Sorento', 'Telluride', 'Model X', 'Model Y', 'X3', 'X5', 'GLC', 'GLE', 'Q5', 'Q7', 'Range Rover', 'Defender', 'Discovery', 'Explorer', 'Escape', 'Bronco', 'Equinox', 'Tahoe', 'Rogue', 'Pathfinder', 'CR-V', 'Pilot', 'CX-5', 'CX-9', 'Wrangler', 'Grand Cherokee', 'RX', 'NX', 'GX']:
                    category = 'SUV'
                
                rating = round(random.uniform(3.8, 5.0), 1)
                review_count = random.randint(5, 120)
                seats = 5 if category != 'SUV' else random.choice([5, 7])
                color = random.choice(colors)
                location = random.choice(locations)
                is_avail = str(random.choice([True, True, True, False])).lower()
                is_feat = str(random.choice([True, False, False])).lower()
                
                features = ["'Bluetooth'", "'Backup Camera'", "'Leather Seats'"]
                if category == 'Luxury':
                    features.extend(["'Sunroof'", "'Heated Seats'"])
                if category == 'SUV':
                    features.extend(["'All-Wheel Drive'"])
                if fuel == 'Electric':
                    features.extend(["'Autopilot'"])
                
                # generate simple images using unsplash source or placehold
                image_url = f"'https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?auto=format&fit=crop&q=80&w=800'" # fallback
                
                dart_code += f"""  Vehicle(
    id: 'v{id_counter}',
    brand: '{brand}',
    model: '{model}',
    year: {year},
    pricePerDay: {base_price}.0,
    rating: {rating},
    reviewCount: {review_count},
    fuelType: '{fuel}',
    transmission: '{random.choice(transmissions)}',
    seats: {seats},
    color: '{color}',
    location: '{location}',
    isAvailable: {is_avail},
    description: 'Experience the thrill of driving the {brand} {model}. This {year} {category} offers a perfect blend of comfort, performance, and style.',
    imageUrls: [{image_url}],
    features: [{', '.join(features)}],
    category: '{category}',
    isFeatured: {is_feat},
  ),
"""
                id_counter += 1
                
    dart_code += "];\n"
    
    with open('lib/mock_data/mock_vehicles.dart', 'w') as f:
        f.write(dart_code)

generate_vehicles()
