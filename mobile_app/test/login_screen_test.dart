import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:estele/providers/auth_provider.dart';
import 'package:estele/screens/auth/login_screen.dart';

Future<void> _pumpLogin(WidgetTester tester) async {
  SharedPreferences.setMockInitialValues({});
  // No platform plugin in tests — secure storage answers null (logged out).
  const channel = MethodChannel('plugins.it_nomadas.com/flutter_secure_storage');
  tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(
    channel,
    (call) async => null,
  );
  await tester.pumpWidget(
    MultiProvider(
      providers: [ChangeNotifierProvider(create: (_) => AuthProvider())],
      child: MaterialApp(
        routes: {'/otp': (_) => const Scaffold(body: Text('OTP screen'))},
        home: const LoginScreen(),
      ),
    ),
  );
  await tester.pump();
  await tester.pump();
}

void main() {
  testWidgets('invalid phone shows an inline validation error', (tester) async {
    await _pumpLogin(tester);

    await tester.enterText(find.byType(TextField).first, '12345');
    await tester.tap(find.text('SEND OTP'));
    await tester.pump();

    expect(find.text('Enter a valid 10-digit mobile number'), findsOneWidget);
  });

  testWidgets(
    'valid phone attempts the real API (test harness blocks HTTP → '
    'the server/network error surfaces, proving SEND OTP is wired)',
    (tester) async {
      await _pumpLogin(tester);

      await tester.enterText(find.byType(TextField).first, '9876543210');
      await tester.tap(find.text('SEND OTP'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 150));

      expect(
        find.textContaining('Unexpected response from server'),
        findsOneWidget,
      );
    },
  );

  testWidgets('login is a phone-only screen (no OTP card embedded)',
      (tester) async {
    await _pumpLogin(tester);

    expect(find.text('Mobile Number'), findsOneWidget);
    expect(find.text('SEND OTP'), findsOneWidget);
    // The old inline verify card is gone — six-box OTP lives on /otp.
    expect(find.text('VERIFY & CONTINUE'), findsNothing);
  });
}