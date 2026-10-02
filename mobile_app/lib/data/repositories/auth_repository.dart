import '../../models/user.dart';
import '../api_client.dart';
import '../storage.dart';

/// Result of the website-parity mobile OTP verify. The backend branches on the
/// phone's registration state: an existing number returns [user] (bearer token
/// saved locally); a new number returns the single-use [verificationToken] the
/// client passes back to /register to complete the account.
class MobileAuthResult {
  const MobileAuthResult({this.user, this.verificationToken});

  final User? user;
  final String? verificationToken;

  bool get isExisting => user != null;
  bool get isNew => verificationToken != null;
}

class AuthRepository {
  /// Website-parity send — one OTP round serves login AND registration, so a
  /// code is issued for any mobile number (registered or not), exactly like the
  /// website's OTP login.
  static Future<void> sendMobileOtp(String phone) async {
    await ApiClient.post('/auth/mobile/send-otp', body: {'phone': phone});
  }

  /// Verify the code and branch server-side: existing number -> logged-in user
  /// + bearer token; new number -> single-use registration nonce.
  static Future<MobileAuthResult> verifyMobileOtp(
    String phone,
    String code,
  ) async {
    final json = await ApiClient.post(
      '/auth/mobile/verify-otp',
      body: {'phone': phone, 'code': code},
      requireCartToken: true,
    );

    final data = json['data'] as Map<String, dynamic>;

    if (data['account'] == 'existing') {
      final user = User.fromJson(data['user'] as Map<String, dynamic>);
      await Storage.saveUser(
        token: data['token'] as String,
        id: user.id,
        name: user.name,
        email: user.email,
        phone: user.phone,
      );
      return MobileAuthResult(user: user);
    }

    return MobileAuthResult(
      verificationToken: data['verification_token'] as String?,
    );
  }

  /// Complete a new customer's account — name + verified phone only (no email
  /// or password, matching the website's register form). The single-use
  /// [verificationToken] must come from the preceding verifyMobileOtp call; the
  /// backend validates it server-side and consumes it on success, so an account
  /// can never be created for an unverified phone.
  static Future<User> registerMobile({
    required String name,
    required String phone,
    required String verificationToken,
  }) async {
    final json = await ApiClient.post(
      '/register',
      body: {
        'name': name,
        'phone': phone,
        'verification_token': verificationToken,
      },
      requireCartToken: true,
    );

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

  /// Check whether a session is currently active locally (no server call).
  static Future<bool> isLoggedIn() async {
    final token = await Storage.getToken();
    return token != null && token.isNotEmpty;
  }
}
