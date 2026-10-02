import 'dart:math';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Persistent local storage for auth tokens, the guest cart token, and
/// wishlist ids — both a secure store (auth tokens) and prefs (everything else).
class Storage {
  Storage._();

  static const _secureStorage = FlutterSecureStorage(
    aOptions: AndroidOptions(encryptedSharedPreferences: true),
  );

  static SharedPreferences? _prefs;

  static Future<void> init() async {
    _prefs = await SharedPreferences.getInstance();
  }

  // ------------------------------------------------------------------
  // Bearer token (secure — never leave the OS keychain/secure-storage).
  // ------------------------------------------------------------------
  static const _tokenKey = 'auth_token';
  static const _userIdKey = 'user_id';
  static const _userNameKey = 'user_name';
  static const _userEmailKey = 'user_email';
  static const _userPhoneKey = 'user_phone';

  static Future<String?> getToken() => _secureStorage.read(key: _tokenKey);
  static Future<void> saveToken(String token) =>
      _secureStorage.write(key: _tokenKey, value: token);
  static Future<void> clearToken() => _secureStorage.delete(key: _tokenKey);

  static Future<void> saveUser({
    required String token,
    required int id,
    required String name,
    String? email,
    String? phone,
  }) async {
    await saveToken(token);
    await _prefs?.setInt(_userIdKey, id);
    await _prefs?.setString(_userNameKey, name);
    if (email != null) await _prefs?.setString(_userEmailKey, email);
    if (phone != null) await _prefs?.setString(_userPhoneKey, phone);
  }

  static int? getUserId() => _prefs?.getInt(_userIdKey);
  static String? getUserName() => _prefs?.getString(_userNameKey);
  static String? getUserEmail() => _prefs?.getString(_userEmailKey);
  static String? getUserPhone() => _prefs?.getString(_userPhoneKey);

  static Future<void> clearUser() async {
    await clearToken();
    await _prefs?.remove(_userIdKey);
    await _prefs?.remove(_userNameKey);
    await _prefs?.remove(_userEmailKey);
    await _prefs?.remove(_userPhoneKey);
  }

  // ------------------------------------------------------------------
  // Cart token (plain prefs — not secret, just a random UUID).
  // ------------------------------------------------------------------
  static const _cartTokenKey = 'cart_token';

  static Future<String> getCartToken() async {
    var token = _prefs?.getString(_cartTokenKey);
    if (token == null || token.isEmpty) {
      // Generate a UUID-like string: manual to avoid uuid package dependency
      token = _generateUuid();
      await _prefs?.setString(_cartTokenKey, token);
    }
    return token;
  }

  // ------------------------------------------------------------------
  // Wishlist (plain prefs — a comma-separated list of product ids).
  // ------------------------------------------------------------------
  static const _wishlistKey = 'wishlist_ids';

  static List<int> getWishlistIds() {
    final raw = _prefs?.getString(_wishlistKey) ?? '';
    if (raw.isEmpty) return [];
    return raw
        .split(',')
        .map((s) => int.tryParse(s) ?? 0)
        .where((id) => id > 0)
        .toList();
  }

  static Future<void> saveWishlistIds(List<int> ids) async {
    await _prefs?.setString(_wishlistKey, ids.join(','));
  }

  static Future<void> toggleWishlistId(int id) async {
    final ids = getWishlistIds();
    if (ids.contains(id)) {
      ids.remove(id);
    } else {
      ids.add(id);
    }
    await saveWishlistIds(ids);
  }

  static bool isWishlisted(int id) => getWishlistIds().contains(id);

  /// A simple UUID-like token generator (no external package).
  static final _rng = Random();

  static String _generateUuid() {
    const chars = '0123456789abcdef';
    final random = List.generate(36, (_) => chars[_rng.nextInt(chars.length)]);
    // Format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    random[14] = '4';
    random[19] = chars[8 + _rng.nextInt(4)];
    return '${random.sublist(0, 8).join()}-'
        '${random.sublist(8, 12).join()}-'
        '${random.sublist(12, 16).join()}-'
        '${random.sublist(16, 20).join()}-'
        '${random.sublist(20).join()}';
  }
}
