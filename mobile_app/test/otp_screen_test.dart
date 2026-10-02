import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:estele/providers/auth_provider.dart';
import 'package:estele/screens/auth/otp_screen.dart';

Future<void> _pumpOtp(WidgetTester tester) async {
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
        routes: {
          '/register': (_) => const Scaffold(body: Text('Create Your Account')),
          '/login': (_) => const Scaffold(body: Text('Login')),
        },
        home: const OtpScreen(phone: '9876543210'),
      ),
    ),
  );
  await tester.pump();
  await tester.pump();
}

void main() {
  testWidgets('renders website-parity copy + masked recipient', (tester) async {
    await _pumpOtp(tester);

    expect(find.text('Enter Verification Code'), findsOneWidget);
    expect(
      find.text("We've sent a 6-digit code to your mobile number."),
      findsOneWidget,
    );
    expect(find.text('Sent to: +91 XXXXXX3210'), findsOneWidget);
    expect(find.text('Edit'), findsOneWidget);

    // Let the resend countdown expire so no timer leaks from this test.
    await tester.pump(const Duration(seconds: 31));
  });

  testWidgets(
    'auto-verifies on the 6th digit — real API called, no Verify button '
    '(harness network error proves the call happened)',
    (tester) async {
      await _pumpOtp(tester);

      // No manual verify button exists on this screen.
      expect(find.textContaining('VERIFY'), findsNothing);

      await tester.enterText(find.byType(TextField), '123456');
      await tester.pump();
      await tester.pump(const Duration(milliseconds: 150));

      expect(
        find.textContaining('Unexpected response from server'),
        findsOneWidget,
      );

      await tester.pump(const Duration(seconds: 31));
    },
  );

  testWidgets('resend is disabled during the countdown, then re-enables',
      (tester) async {
    await _pumpOtp(tester);

    TextButton resend() => tester.widget<TextButton>(
      find.ancestor(
        of: find.textContaining('Resend code'),
        matching: find.byType(TextButton),
      ),
    );

    expect(find.text('Resend code in 0:30'), findsOneWidget);
    expect(resend().onPressed, isNull);

    await tester.pump(const Duration(seconds: 30));
    await tester.pump();

    expect(find.text('Resend code'), findsOneWidget);
    expect(resend().onPressed, isNotNull);
  });
}