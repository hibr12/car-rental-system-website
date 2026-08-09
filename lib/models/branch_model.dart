class Branch {
  final String id;
  final String name;
  final String address;
  final String city;
  final String phone;
  final String email;
  final double latitude;
  final double longitude;
  final String imageUrl;
  final String openingHours;
  final bool isOpen;
  final int availableVehicles;
  final double rating;

  const Branch({
    required this.id,
    required this.name,
    required this.address,
    required this.city,
    required this.phone,
    required this.email,
    required this.latitude,
    required this.longitude,
    required this.imageUrl,
    required this.openingHours,
    this.isOpen = true,
    required this.availableVehicles,
    required this.rating,
  });
}
