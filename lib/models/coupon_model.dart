enum DiscountType { percentage, fixed }

class Coupon {
  final String id;
  final String code;
  final String description;
  final DiscountType type;
  final double value;
  final DateTime expiryDate;
  final double? minSpend;

  const Coupon({
    required this.id,
    required this.code,
    required this.description,
    required this.type,
    required this.value,
    required this.expiryDate,
    this.minSpend,
  });

  bool get isValid => DateTime.now().isBefore(expiryDate);
}
