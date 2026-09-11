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

  AuthStatus get status => _status;
  User? get user => _user;
  bool get isAuthenticated => _status == AuthStatus.authenticated;
  bool get isLoading => _refreshing;
  String? get verificationToken => _verificationToken;

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

  Future<String?> login(String email, String password) async {
    try {
      final user = await AuthRepository.login(email, password);
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

  Future<String?> sendLoginOtp(String phone) async {
    try {
      await AuthRepository.sendLoginOtp(phone);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }

  Future<String?> verifyLoginOtp(String phone, String code) async {
    try {
      final user = await AuthRepository.verifyLoginOtp(phone, code);
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

  Future<String?> sendRegisterOtp(String phone) async {
    try {
      await AuthRepository.sendRegisterOtp(phone);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }

  Future<String?> verifyRegisterOtp(String phone, String code) async {
    try {
      _verificationToken = await AuthRepository.verifyRegisterOtp(phone, code);
      return null;
    } on ApiException catch (e) {
      _verificationToken = null;
      return e.message;
    } catch (_) {
      _verificationToken = null;
      return 'Unable to reach the server. Check your connection.';
    }
  }

  Future<String?> register({required String name, required String email, required String phone, required String password}) async {
    final token = _verificationToken;
    if (token == null) {
      return 'Please verify your phone number first.';
    }
    try {
      final user = await AuthRepository.register(
        name: name,
        email: email,
        phone: phone,
        password: password,
        verificationToken: token,
      );
      _verificationToken = null;
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
    notifyListeners();
  }

  Future<String?> forgotPassword(String email) async {
    try {
      await AuthRepository.forgotPassword(email);
      return null;
    } on ApiException catch (e) {
      return e.message;
    } catch (_) {
      return 'Unable to reach the server. Check your connection.';
    }
  }
}