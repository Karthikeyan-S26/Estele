import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config/app_config.dart';
import 'storage.dart';

/// The single HTTP client for all API calls.
///
/// Every repository goes through this so auth/bearer token, cart token, CORS
/// headers, and error shapes are handled in one place.
class ApiClient {
  ApiClient._();

  static final http.Client _client = http.Client();

  static const int _maxRateLimitAttempts = 3;

  /// Make a [method] request to the relative path under `/api/`.
  /// Throws [ApiException] on 4xx/5xx with the parsed `message`.
  static Future<Map<String, dynamic>> request(
    String method,
    String path, {
    Map<String, dynamic>? body,
    bool auth = false,
    bool requireCartToken = false,
    Map<String, String>? extraHeaders,
  }) async {
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
    };

    if (auth) {
      final token = await Storage.getToken();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }
    }

    if (requireCartToken) {
      final cartToken = await Storage.getCartToken();
      headers['X-Cart-Token'] = cartToken;
    }

    if (extraHeaders != null) {
      headers.addAll(extraHeaders);
    }

    final url = Uri.parse('${AppConfig.apiBaseUrl}$path');
    final request = http.Request(method, url);
    request.headers.addAll(headers);

    if (body != null) {
      request.body = jsonEncode(body);
    }

    for (var attempt = 0; ; attempt++) {
      final response = await _client.send(request).timeout(
            AppConfig.connectTimeout + AppConfig.receiveTimeout,
          );

      final responseBody = await response.stream.bytesToString();
      final Map<String, dynamic> json;
      try {
        json = jsonDecode(responseBody) as Map<String, dynamic>;
      } catch (_) {
        throw ApiException(
          statusCode: response.statusCode,
          message: 'Unexpected response from server',
        );
      }

      // Transient rate limit — retry a couple of times with backoff.
      if (response.statusCode == 429 && attempt < _maxRateLimitAttempts - 1) {
        final retryAfter = _retryAfter(response.headers);
        await Future<void>.delayed(
          Duration(seconds: (retryAfter ?? 1).clamp(1, 10)),
        );
        continue;
      }

      if (response.statusCode >= 200 && response.statusCode < 300) {
        return json;
      }

      // Consistent error shape from the backend.
      final message = json['message'] as String? ?? 'An error occurred.';
      final errors = json['errors'] as Map<String, dynamic>?;
      throw ApiException(statusCode: response.statusCode, message: message, errors: errors);
    }
  }

  /// Parse the `Retry-After` header (seconds) if present.
  static int? _retryAfter(Map<String, String> headers) {
    final raw = headers['retry-after'] ?? headers['Retry-After'];
    if (raw == null || raw.isEmpty) return null;
    return int.tryParse(raw.trim());
  }

  /// GET convenience.
  static Future<Map<String, dynamic>> get(
    String path, {
    bool auth = false,
    bool requireCartToken = false,
    Map<String, String>? extraHeaders,
  }) =>
      request('GET', path, auth: auth, requireCartToken: requireCartToken, extraHeaders: extraHeaders);

  /// POST convenience.
  static Future<Map<String, dynamic>> post(
    String path, {
    Map<String, dynamic>? body,
    bool auth = false,
    bool requireCartToken = false,
    Map<String, String>? extraHeaders,
  }) =>
      request('POST', path, body: body, auth: auth, requireCartToken: requireCartToken, extraHeaders: extraHeaders);

  /// PATCH convenience.
  static Future<Map<String, dynamic>> patch(
    String path, {
    Map<String, dynamic>? body,
    bool auth = false,
    bool requireCartToken = false,
    Map<String, String>? extraHeaders,
  }) =>
      request('PATCH', path, body: body, auth: auth, requireCartToken: requireCartToken, extraHeaders: extraHeaders);

  /// DELETE convenience.
  static Future<Map<String, dynamic>> delete(
    String path, {
    bool auth = false,
    bool requireCartToken = false,
    Map<String, String>? extraHeaders,
  }) =>
      request('DELETE', path, auth: auth, requireCartToken: requireCartToken, extraHeaders: extraHeaders);

  /// Parse a paginated `data` + `meta` response. Returns the raw JSON map
  /// so callers can iterate `json['data']` themselves.
  static Future<({List<Map<String, dynamic>> items, Map<String, dynamic> meta})> paginated(
    String path, {
    bool auth = false,
    Map<String, String>? extraHeaders,
  }) async {
    final json = await get(path, auth: auth, extraHeaders: extraHeaders);
    return (
      items: (json['data'] as List<dynamic>? ?? []).cast<Map<String, dynamic>>(),
      meta: (json['meta'] as Map<String, dynamic>?) ?? {},
    );
  }
}

/// Thrown when a non-2xx API response is received.
class ApiException implements Exception {
  ApiException({required this.statusCode, required this.message, this.errors});

  final int statusCode;
  final String message;
  final Map<String, dynamic>? errors;

  bool get isUnauthenticated => statusCode == 401;
  bool get isForbidden => statusCode == 403;
  bool get isNotFound => statusCode == 404;
  bool get isValidation => statusCode == 422;
  bool get isRateLimited => statusCode == 429;

  /// Extract the first validation error message for a specific field.
  String? fieldError(String field) {
    if (errors == null) return null;
    final fieldErrors = errors![field];
    if (fieldErrors is List && fieldErrors.isNotEmpty) {
      return fieldErrors.first as String?;
    }
    return null;
  }

  @override
  String toString() => 'ApiException($statusCode): $message';
}