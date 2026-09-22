import 'package:intl/intl.dart';

/// Shared formatting helpers.
///
/// Mirrors the web frontend's `utils/formatters.js` so both clients present
/// values identically. Currency is always Ethiopian Birr (ETB) — the backend
/// hardcodes `'ETB'` and no client-side conversion is ever performed.
class Formatters {
  Formatters._();

  /// Formats an amount as ETB, e.g. `ETB 1,500` / `ETB 1,500.50`.
  ///
  /// Follows the web convention: no forced decimals; up to 2 decimals are
  /// shown only when the value actually has them.
  static String etb(num? amount) {
    final value = amount ?? 0;
    final hasCents = (value * 100).round() % 100 != 0;
    final formatter = NumberFormat.currency(
      locale: 'en_US',
      symbol: 'ETB ',
      decimalDigits: hasCents ? 2 : 0,
    );
    return formatter.format(value);
  }

  /// `Jan 5, 2026`
  static String date(DateTime? date) =>
      date == null ? 'N/A' : DateFormat('MMM d, yyyy').format(date);

  /// `Jan 5, 2026, 3:05 PM`
  static String dateTime(DateTime? date) => date == null
      ? 'N/A'
      : DateFormat('MMM d, yyyy · h:mm a').format(date.toLocal());

  /// `Aug 23` — compact form for list rows.
  static String shortDate(DateTime? date) =>
      date == null ? 'N/A' : DateFormat('MMM d').format(date);

  /// `5:00 PM`
  static String time(DateTime? date) =>
      date == null ? 'N/A' : DateFormat('h:mm a').format(date.toLocal());
}
