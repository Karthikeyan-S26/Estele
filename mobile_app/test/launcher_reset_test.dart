import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/main.dart';

void main() {
  testWidgets(
    'a launcher relaunch resets the navigator to the splash route',
    (tester) async {
      registerLauncherResetHandler();

      await tester.pumpWidget(
        MaterialApp(
          navigatorKey: appNavigatorKey,
          initialRoute: '/',
          onGenerateRoute: (settings) {
            final name = settings.name ?? '/';
            return MaterialPageRoute(
              settings: settings,
              builder: (_) => Text(name == '/' ? 'SPLASH' : 'PRODUCT'),
            );
          },
        ),
      );
      expect(find.text('SPLASH'), findsOneWidget);

      // Walk away to a product page (a Navigator route, not an Activity).
      appNavigatorKey.currentState!.pushNamed('/product/x');
      await tester.pumpAndSettle();
      expect(find.text('PRODUCT'), findsOneWidget);

      // Reopening from the launcher icon fires resetToHome from MainActivity.
      await TestDefaultBinaryMessengerBinding
          .instance.defaultBinaryMessenger
          .handlePlatformMessage(
            'estele/launcher',
            const StandardMethodCodec().encodeMethodCall(
              const MethodCall('resetToHome'),
            ),
            (_) {},
          );
      await tester.pumpAndSettle();

      expect(find.text('SPLASH'), findsOneWidget);
      expect(find.text('PRODUCT'), findsNothing);
    },
  );
}