import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/screens/splash_screen.dart';
import 'package:estele/theme/app_colors.dart';

void main() {
  testWidgets(
    'shows a clean white branded splash, then replaces it with Home',
    (tester) async {
      var landed = false;
      await tester.pumpWidget(
        MaterialApp(
          routes: {
            '/home': (_) => Builder(
                  builder: (context) {
                    landed = true;
                    return const Scaffold(
                      body: Text('HOME SHELL'),
                    );
                  },
                ),
          },
          home: const SplashScreen(),
        ),
      );

      // Branded splash: white layer + gold wordmark centred.
      final scaffold = tester.widget<Scaffold>(find.byType(Scaffold));
      expect(scaffold.backgroundColor, AppColors.paper);
      expect(find.text('Estele'), findsOneWidget);

      // Held briefly before navigating away.
      await tester.pump(const Duration(milliseconds: 700));
      expect(landed, isFalse);
      expect(find.byType(SplashScreen), findsOneWidget);

      // After the hold window the splash is *replaced* by Home.
      await tester.pump(SplashScreen.hold);
      await tester.pumpAndSettle();
      expect(landed, isTrue);
      expect(find.byType(SplashScreen), findsNothing);
      expect(find.text('HOME SHELL'), findsOneWidget);
    },
  );
}