import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/main.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  testWidgets('ApexRentalsApp smoke test', (WidgetTester tester) async {
    SharedPreferences.setMockInitialValues({});

    await tester.pumpWidget(const ApexRentalsApp());
    expect(find.byType(ApexRentalsApp), findsOneWidget);

    // Let the splash screen's minimum-display timer fire and the router
    // settle on its destination (onboarding for a fresh install).
    await tester.pump(const Duration(seconds: 2));
    await tester.pumpAndSettle();
  });
}
