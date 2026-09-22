class PriceEstimate {
  final int vehicleId;
  final double pricePerDay;
  final int numberOfDays;
  final double subtotal;
  final double additionalCharges;
  final double discount;
  final double totalPrice;

  PriceEstimate({
    required this.vehicleId,
    required this.pricePerDay,
    required this.numberOfDays,
    required this.subtotal,
    required this.additionalCharges,
    required this.discount,
    required this.totalPrice,
  });

  factory PriceEstimate.fromJson(Map<String, dynamic> json) {
    return PriceEstimate(
      vehicleId: _parseInt(json['vehicle_id']) ?? 0,
      pricePerDay: _parseDouble(json['price_per_day']),
      numberOfDays: _parseInt(json['number_of_days']) ?? 0,
      subtotal: _parseDouble(json['subtotal']),
      additionalCharges: _parseDouble(json['additional_charges']),
      discount: _parseDouble(json['discount']),
      totalPrice: _parseDouble(json['total_price']),
    );
  }

  static double _parseDouble(dynamic value) {
    if (value is num) return value.toDouble();
    if (value is String) {
      final sanitized = value.replaceAll(',', '').trim();
      return double.tryParse(sanitized) ?? 0.0;
    }
    return 0.0;
  }

  static int? _parseInt(dynamic value) {
    if (value == null) return null;
    if (value is int) return value;
    if (value is num) return value.toInt();
    if (value is String) return int.tryParse(value);
    return null;
  }
}
