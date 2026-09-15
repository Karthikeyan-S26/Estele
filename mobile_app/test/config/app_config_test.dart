import 'package:flutter/foundation.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:estele/config/app_config.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('AppConfig.apiBaseUrl', () {
    tearDown(() {
      debugDefaultTargetPlatformOverride = null;
    });

    test('defaults to the Android emulator loopback alias on Android', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.android;

      expect(AppConfig.apiBaseUrl, 'http://10.0.2.2:8000/api');
    });

    test(
      'defaults to localhost on desktop platforms so 10.0.2.2 is not used',
      () {
        debugDefaultTargetPlatformOverride = TargetPlatform.windows;

        expect(AppConfig.apiBaseUrl, 'http://127.0.0.1:8000/api');
      },
    );

    test('defaults to localhost on iOS simulator builds', () {
      debugDefaultTargetPlatformOverride = TargetPlatform.iOS;

      expect(AppConfig.apiBaseUrl, 'http://127.0.0.1:8000/api');
    });
  });
}
