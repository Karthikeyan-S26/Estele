import 'package:flutter/foundation.dart';

import '../data/api_client.dart';
import '../data/repositories/account_repository.dart';
import '../data/repositories/auth_repository.dart';
import '../data/storage.dart';
import '../models/user.dart';

enum AuthStatus { unknown, authenticated, unauthenticated }

class AuthProvider extends ChangeNotifier {
  AuthProvider() {
    _restore();
  }

  AuthStatus _status = AuthStatus.unknown;
  User? _user;
  bool _refreshing = false;
  String? _verificationToken;
  String? _verifiedPhone;

  AuthStatus get status => _status;
  User? get user => _user;
  bool get isAuthenticated => _status == AuthStatus.authenticated;
  bool get isLoading => _refreshing;
  String? get verificationToken => _verificationToken;

  /// The phone number the current registration nonce was verified against —
  /// /register must use the exact same number or the nonce is rejected.
  String? get verifiedPhone => _verifiedPhone;

  Future<void> _restore() async {
    final loggedIn = await AuthRepository.isLoggedIn();
    if (loggedIn) {
      final id = Storage.getUserId();
      _user = User(
        id: id ?? 0,
        name: Storage.getUserName() ?? '',
        email: Storage.getUserEmail(),
        phone: Storage.getUserPhone(),
      );
      // Best-effort refresh of the profile from the server (silent).
      _refreshProfile();
    }
    _status = loggedIn ? AuthStatus.authenticated : AuthStatus.unauthenticated;
    notifyListeners();
  }

  Future<void> _refreshProfile() async {
    await refreshProfile();
  }

  Future<void> refreshProfile() async {
    if (_refreshing) return;
    _refreshing = true;
    notifyListeners();
    try {
      _user = await AccountRepository.profile();
    } catch (_) {
      // Offline — keep cached profile.
    } finally {
      _refreshing = false;
      notifyListeners();
    }
  }

  /// Replaces the in-memory profile (used after profile edits) and keeps any
  /// persisted name/email in sync so the restored session stays current.
  Future<void> updateUser(User user) async {
    _user = user;
    final token = await Storage.getToken();
    if (token != null) {
      await Storage.saveUser(
        token: token,
        id: user.id,
        name: user.name,
        email: user.email,
        phone: user.phone,
      );
    }
    notifyListeners();
  }

  Future<String?> sendMobileOtp(String phone) async {
    try {
      await AuthRepository.sendMobileOtp(phone);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }

  /// Verifies the code and mirrors the backend branch: an existing number signs
  /// the user in; a new number is remembered as verified so account completion
  /// can finish registration.
  Future<String?> verifyMobileOtp(String phone, String code) async {
    try {
      final result = await AuthRepository.verifyMobileOtp(phone, code);
      if (result.isExisting) {
        _user = result.user;
        _status = AuthStatus.authenticated;
        _verificationToken = null;
        _verifiedPhone = null;
      } else {
        _verificationToken = result.verificationToken;
        _verifiedPhone = phone;
      }
      notifyListeners();
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }

  /// Completes a new customer's account with the verified phone. Requires a
  /// live, phone-matched registration nonce from verifyMobileOtp — the backend
  /// validates the nonce server-side, so an account can't be created for an
  /// unverified number.
  Future<String?> registerMobile({
    required String name,
    required String phone,
  }) async {
    final token = _verificationToken;
    if (token == null || _verifiedPhone != phone) {
      return 'Please verify your mobile number with OTP first.';
    }
    try {
      final user = await AuthRepository.registerMobile(
        name: name,
        phone: phone,
        verificationToken: token,
      );
      _verificationToken = null;
      _verifiedPhone = null;
      _user = user;
      _status = AuthStatus.authenticated;
      notifyListeners();
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }

  Future<void> logout() async {
    await AuthRepository.logout();
    _user = null;
    _status = AuthStatus.unauthenticated;
    _verificationToken = null;
    _verifiedPhone = null;
    notifyListeners();
  }

  /// Local-only session teardown for when the server rejects the token (401 —
  /// expired or revoked). No server call: the token is already dead, and firing
  /// /logout would just 401 again. The root shell listens for the status
  /// change and re-fetches the guest cart.
  Future<void> forceLogoutLocal() async {
    await Storage.clearUser();
    _user = null;
    _status = AuthStatus.unauthenticated;
    _verificationToken = null;
    _verifiedPhone = null;
    notifyListeners();
  }
}
