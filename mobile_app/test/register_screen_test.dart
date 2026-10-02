import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:estele/providers/auth_provider.dart';
import 'package:estele/screens/auth/register_screen.dart';

Future<void> _pumpRegister(WidgetTester tester) async {
  SharedPreferences.setMockInitialValues({});
  const channel = MethodChannel('plugins.it_nomadas.com/flutter_secure_storage');
  tester.binding.defaultBinaryMessenger.setMockMethodCallHandler(
    channel,
    (call) async => null,
  );
  await tester.pumpWidget(
    MultiProvider(
      providers: [ChangeNotifierProvider(create: (_) => AuthProvider())],
      child: MaterialApp(
        routes: {'/login': (_) => const Scaffold(body: Text('Login'))},
        home: const RegisterScreen(prefillPhone: '9876543210'),
      ),
    ),
  );
  await tester.pump();
  await tester.pump();
}

void main() {
  testWidgets('unverified account can still validate the name field',
      (tester) async {
    await _pumpRegister(tester);
    expect(find.text('Create Account'), findsOneWidget);

    // OTP not yet verified — Create Account blocks on the name first.
    await tester.tap(find.text('Create Account'));
    await tester.pump();

    expect(find.text('Please enter your full name'), findsOneWidget);
  });

  testWidgets(
    'inline send-otp attempts the real API in register too '
    '(harness network error proves the call happened)',
    (tester) async {
      await _pumpRegister(tester);
      expect(find.text('Send OTP'), findsOneWidget);

      await tester.tap(find.text('Send OTP'));
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 150));

      expect(
        find.textContaining('Unexpected response from server'),
        findsOneWidget,
      );
    },
  );
}