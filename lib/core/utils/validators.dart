class AppValidators {
  static String? required(String? value,
      {String message = 'This field is required'}) {
    if (value == null || value.trim().isEmpty) {
      return message;
    }
    return null;
  }

  static String? email(String? value,
      {String message = 'Enter a valid email address'}) {
    if (value == null || value.trim().isEmpty)
      return null; // Use required() for empty check
    final emailRegex = RegExp(r'^[^@]+@[^@]+\.[^@]+');
    if (!emailRegex.hasMatch(value)) {
      return message;
    }
    return null;
  }

  static String? phone(String? value,
      {String message = 'Enter a valid phone number'}) {
    if (value == null || value.trim().isEmpty) return null;
    final phoneRegex = RegExp(r'^\+?[0-9\s\-\(\)]{7,15}$');
    if (!phoneRegex.hasMatch(value)) {
      return message;
    }
    return null;
  }

  static String? password(String? value,
      {String message = 'Password must be at least 8 characters'}) {
    if (value == null || value.trim().isEmpty) return null;
    if (value.length < 8) {
      return message;
    }
    return null;
  }

  static String? confirmPassword(String? value, String password,
      {String message = 'Passwords do not match'}) {
    if (value == null || value.trim().isEmpty) return null;
    if (value != password) {
      return message;
    }
    return null;
  }

  static String? cardNumber(String? value,
      {String message = 'Enter a valid 16-digit card number'}) {
    if (value == null || value.trim().isEmpty) return null;
    final cleanValue = value.replaceAll(RegExp(r'\s+|-'), '');
    if (cleanValue.length < 13 ||
        cleanValue.length > 19 ||
        int.tryParse(cleanValue) == null) {
      return message;
    }
    return null;
  }

  static String? expiryDate(String? value,
      {String message = 'Enter a valid expiry date (MM/YY)'}) {
    if (value == null || value.trim().isEmpty) return null;
    final regex = RegExp(r'^(0[1-9]|1[0-2])\/?([0-9]{2})$');
    if (!regex.hasMatch(value)) {
      return message;
    }
    return null;
  }

  static String? cvv(String? value,
      {String message = 'Enter a valid CVV (3-4 digits)'}) {
    if (value == null || value.trim().isEmpty) return null;
    final regex = RegExp(r'^[0-9]{3,4}$');
    if (!regex.hasMatch(value)) {
      return message;
    }
    return null;
  }
}
