import 'package:flutter/foundation.dart';

/// App-wide configuration.
///
/// Point this at your Laravel backend. The API base URL defaults per runtime
/// so a local dev server "just works" everywhere — an Android emulator
/// reaches the host through the `10.0.2.2` loopback alias, every other local
/// target (Windows/macOS/Linux desktop, web, iOS simulator) through
/// `127.0.0.1`. Pin a specific host with a build-time override:
///
///   `--dart-define=API_BASE_URL=http://192.168.1.20:8000/api` (physical device)
///   `--dart-define=API_BASE_URL=https://api.estele.in/api` (production)
class AppConfig {
  AppConfig._();

  static const String _apiBaseUrlOverride = String.fromEnvironment(
    'API_BASE_URL',
  );

  /// Base URL of the Estele REST API (no trailing slash).
  static String get apiBaseUrl {
    if (_apiBaseUrlOverride.isNotEmpty) {
      return _apiBaseUrlOverride;
    }
    if (!kIsWeb && defaultTargetPlatform == TargetPlatform.android) {
      return 'http://10.0.2.2:8000/api';
    }
    return 'http://127.0.0.1:8000/api';
  }

  /// Free-shipping threshold used to display shipping estimates offline.
  static const double freeShippingThreshold = 999;

  /// Fallback shipping fee before the server responds.
  static const double defaultShippingFee = 49;

  /// Turns a media URL coming from the API into something the app can fetch.
  ///
  /// The backend may return either an absolute URL (`http://host/storage/...`)
  /// or a root-relative path (`/storage/11/x-card.png`). Both are normalized
  /// onto [apiBaseUrl]'s origin: relative paths are resolved against it, and
  /// absolute URLs are pinned to it. This guarantees media always loads from
  /// the same, reachable origin the API itself uses — adb reverse (localhost),
  /// a LAN IP, or staging — irrespective of the host the backend baked into
  /// its `APP_URL` at seed time.
  static String? resolveMediaUrl(String? url) {
    if (url == null || url.isEmpty) {
      return null;
    }
    final origin = Uri.parse(
      apiBaseUrl,
    ).replace(path: '', query: '', fragment: '');
    final parsed = Uri.tryParse(url);
    if (parsed == null) {
      return url;
    }
    if (!parsed.isAbsolute) {
      return origin.resolve(url).toString();
    }
    if (parsed.scheme == origin.scheme &&
        parsed.host.toLowerCase() == origin.host.toLowerCase() &&
        parsed.port == origin.port) {
      return url;
    }
    return parsed
        .replace(
          scheme: origin.scheme,
          host: origin.host,
          port: origin.port,
          userInfo: '',
        )
        .toString();
  }

  /// Connection timeout for every HTTP call.
  static const Duration connectTimeout = Duration(seconds: 15);

  /// Response timeout for every HTTP call.
  static const Duration receiveTimeout = Duration(seconds: 30);

  /// Razorpay key id from the backend health/config — injected at runtime
  /// after checkout when an order requires online payment.
  static String? razorpayKeyId;
}
