import '../../models/user.dart';
import '../api_client.dart';
import '../storage.dart';

class AuthRepository {
  /// Email + password login.
  static Future<User> login(String email, String password) async {
    final json = await ApiClient.post('/login', body: {
      'email': email,
      'password': password,
    }, requireCartToken: true);

    final data = json['data'] as Map<String, dynamic>;
    final user = User.fromJson(data['user'] as Map<String, dynamic>);
    await Storage.saveUser(
      token: data['token'] as String,
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
    );
    return user;
  }

  /// OTP send for phone login.
  static Future<void> sendLoginOtp(String phone) async {
    await ApiClient.post('/login/mobile/send-otp', body: {'phone': phone});
  }

  /// OTP verify for phone login.
  static Future<User> verifyLoginOtp(String phone, String code) async {
    final json = await ApiClient.post('/login/mobile/verify-otp', body: {
      'phone': phone,
      'code': code,
    }, requireCartToken: true);

    final data = json['data'] as Map<String, dynamic>;
    final user = User.fromJson(data['user'] as Map<String, dynamic>);
    await Storage.saveUser(
      token: data['token'] as String,
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
    );
    return user;
  }

  /// OTP send for registration.
  static Future<void> sendRegisterOtp(String phone) async {
    await ApiClient.post('/register/send-otp', body: {'phone': phone});
  }

  /// OTP verify for registration. Returns the server-issued, single-use
  /// [verification_token] the backend requires on the final /register call.
  static Future<String> verifyRegisterOtp(String phone, String code) async {
    final json = await ApiClient.post('/register/verify-otp', body: {'phone': phone, 'code': code});
    return (json['data'] as Map<String, dynamic>)['verification_token'] as String;
  }

  /// Register a new account. [verificationToken] must come from the preceding
  /// verifyRegisterOtp call — the backend validates it server-side and consumes
  /// it on success, so an account can never be created for an unverified phone.
  static Future<User> register({
    required String name,
    required String email,
    required String phone,
    required String password,
    required String verificationToken,
  }) async {
    final json = await ApiClient.post('/register', body: {
      'name': name,
      'email': email,
      'phone': phone,
      'password': password,
      'password_confirmation': password,
      'verification_token': verificationToken,
    }, requireCartToken: true);

    final data = json['data'] as Map<String, dynamic>;
    final user = User.fromJson(data['user'] as Map<String, dynamic>);
    await Storage.saveUser(
      token: data['token'] as String,
      id: user.id,
      name: user.name,
      email: user.email,
      phone: user.phone,
    );
    return user;
  }

  /// Log out and clear all local auth state.
  static Future<void> logout() async {
    try {
      await ApiClient.post('/logout', auth: true);
    } catch (_) {
      // Logout server error is non-fatal — always clear locally.
    }
    await Storage.clearUser();
  }

  /// Forgot password — sends the reset link via email.
  static Future<void> forgotPassword(String email) async {
    await ApiClient.post('/forgot-password', body: {'email': email});
  }

  /// Reset password (with emailed token).
  static Future<void> resetPassword({
    required String token,
    required String email,
    required String password,
  }) async {
    await ApiClient.post('/reset-password', body: {
      'token': token,
      'email': email,
      'password': password,
      'password_confirmation': password,
    });
  }

  /// Check whether a session is currently active locally (no server call).
  static Future<bool> isLoggedIn() async {
    final token = await Storage.getToken();
    return token != null && token.isNotEmpty;
  }
}