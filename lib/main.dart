import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'core/theme/app_theme.dart';
import 'core/routes/app_routes.dart';
import 'core/colors/app_colors.dart';
import 'core/config/auth_state.dart';

void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  // Load persisted token into synchronous AuthState before the first
  // frame so the GoRouter redirect guard can read it immediately.
  await AuthState.init();
  AuthState.initApiClientCallback();

  SystemChrome.setSystemUIOverlayStyle(
    const SystemUiOverlayStyle(
      statusBarColor: Colors.transparent,
      statusBarIconBrightness: Brightness.dark,
      systemNavigationBarColor: AppColors.surface,
      systemNavigationBarIconBrightness: Brightness.dark,
    ),
  );

  SystemChrome.setPreferredOrientations([
    DeviceOrientation.portraitUp,
  ]);

  runApp(const AbayCarRentalsApp());
}

class AbayCarRentalsApp extends StatelessWidget {
  const AbayCarRentalsApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'Abay Car Rentals',
      debugShowCheckedModeBanner: false,
      theme: AppTheme.lightTheme,
      routerConfig: AppRoutes.router,
    );
  }
}
