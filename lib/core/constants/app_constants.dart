class AppConstants {
  // SharedPreferences Keys
  static const String keyToken = 'auth_token';
  static const String keyThemeMode = 'theme_mode';
  static const String keyFirstLaunch = 'first_launch';

  // Pagination
  static const int defaultPageSize = 20;

  // Animation Durations
  static const Duration animationFast = Duration(milliseconds: 200);
  static const Duration animationNormal = Duration(milliseconds: 300);
  static const Duration animationSlow = Duration(milliseconds: 500);

  // Layout limits
  static const double maxMobileWidth = 600.0;
}
