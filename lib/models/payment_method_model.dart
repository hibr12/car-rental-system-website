enum PaymentMethodType { visa, mastercard, amex, paypal, applePay, googlePay }

class PaymentMethod {
  final String id;
  final PaymentMethodType type;
  final String? last4;
  final String? expiryDate;
  final bool isDefault;

  const PaymentMethod({
    required this.id,
    required this.type,
    this.last4,
    this.expiryDate,
    this.isDefault = false,
  });

  String get displayName {
    switch (type) {
      case PaymentMethodType.visa:
        return 'Visa •••• $last4';
      case PaymentMethodType.mastercard:
        return 'Mastercard •••• $last4';
      case PaymentMethodType.amex:
        return 'Amex •••• $last4';
      case PaymentMethodType.paypal:
        return 'PayPal';
      case PaymentMethodType.applePay:
        return 'Apple Pay';
      case PaymentMethodType.googlePay:
        return 'Google Pay';
    }
  }
}
