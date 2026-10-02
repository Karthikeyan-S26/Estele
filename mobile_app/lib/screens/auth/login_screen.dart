import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

/// Website-parity mobile login: phone number + "SEND OTP" only. The OTP round
/// lives on its own verification screen (`/otp`) with six auto-verifying
/// boxes; a single code serves both login and registration — an existing
/// number signs in, a new number is carried to account completion.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phone = TextEditingController();

  // Held as a field (not re-read from `context` in dispose): looking up an
  // inherited widget during unmount is unsafe, and the pattern is already
  // used by the checkout / account / root screens.
  late final AuthProvider _auth;

  bool _submitting = false;
  String? _error;
  // Marketing-updates consent — a real interactive checkbox, checked by
  // default. Informational only (the website has no consent gate): it is NOT
  // wired into the SEND OTP validation below.
  bool _consentGiven = true;

  @override
  void initState() {
    super.initState();
    _auth = context.read<AuthProvider>();
    _auth.addListener(_onAuthChanged);
  }

  @override
  void dispose() {
    _auth.removeListener(_onAuthChanged);
    _phone.dispose();
    super.dispose();
  }

  /// The OTP (existing user) or create-account (new user) screens pushed above
  /// this one flip the provider to authenticated the instant sign-in
  /// completes — that is the signal to dismiss the login screen itself and
  /// reveal whatever prompted it (Bag → checkout wall, the Account icon, …).
  /// Scheduling the pop post-frame lets the completion screen's own
  /// navigation (e.g. the Buy Now jump to checkout) run first.
  void _onAuthChanged() {
    if (!_auth.isAuthenticated) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (!mounted || !_auth.isAuthenticated) return;
      Navigator.of(context).pop();
    });
  }

  bool _validPhone() {
    final v = _phone.text.trim();
    return v.length == 10 && RegExp(r'^\d{10}$').hasMatch(v);
  }

  Future<void> _sendOtp() async {
    if (!_validPhone()) {
      setState(() => _error = 'Enter a valid 10-digit mobile number');
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    final err = await context.read<AuthProvider>().sendMobileOtp(
      _phone.text.trim(),
    );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (err != null) {
      setState(() => _error = err);
      return;
    }
    // OTP issued — move to the verification screen, carrying the phone.
    Navigator.of(context).pushNamed('/otp', arguments: _phone.text.trim());
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.ivory,
      appBar: AppBar(
        backgroundColor: AppColors.ivory,
        foregroundColor: AppColors.heading,
        elevation: 0,
        title: const Text('Login'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 28),
          child: Center(
            child: ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 450),
              child: Container(
                padding: const EdgeInsets.fromLTRB(24, 28, 24, 24),
                decoration: BoxDecoration(
                  color: AppColors.paper,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.line),
                  boxShadow: const [
                    BoxShadow(
                      color: Color(0x14000000),
                      blurRadius: 12,
                      offset: Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    Text(
                      'Login for faster checkout',
                      textAlign: TextAlign.center,
                      style: AppTypography.editorial(
                        size: 24,
                        weight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      "We'll send a one-time code. No password needed.",
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(size: 13),
                    ),
                    const SizedBox(height: 24),

                    if (_error != null) ...[
                      _ErrorText(_error!),
                      const SizedBox(height: 12),
                    ],

                    // Mobile Number
                    Text('Mobile Number', style: AppTypography.bodyMedium(
                      size: 13,
                    )),
                    const SizedBox(height: 6),
                    Row(
                      children: [
                        Container(
                          height: 48,
                          padding: const EdgeInsets.symmetric(horizontal: 14),
                          alignment: Alignment.center,
                          decoration: const BoxDecoration(
                            color: AppColors.warmBeige,
                            borderRadius: BorderRadius.horizontal(
                              left: Radius.circular(8),
                            ),
                            border: Border(
                              top: BorderSide(color: AppColors.lineStrong),
                              bottom: BorderSide(color: AppColors.lineStrong),
                              left: BorderSide(color: AppColors.lineStrong),
                            ),
                          ),
                          child: Text(
                            '+91',
                            style: AppTypography.bodyMedium(
                              size: 13,
                              weight: FontWeight.w600,
                            ),
                          ),
                        ),
                        Expanded(
                          child: SizedBox(
                            height: 48,
                            child: TextField(
                              controller: _phone,
                              enabled: !_submitting,
                              keyboardType: TextInputType.phone,
                              maxLength: 10,
                              autofocus: true,
                              textInputAction: TextInputAction.done,
                              onSubmitted: (_) => _sendOtp(),
                              decoration: InputDecoration(
                                counterText: '',
                                hintText: '10-digit mobile number',
                                hintStyle: AppTypography.bodySmall(size: 14),
                                filled: true,
                                fillColor: AppColors.paper,
                                contentPadding: const EdgeInsets.symmetric(
                                  horizontal: 16,
                                ),
                                enabledBorder: OutlineInputBorder(
                                  borderRadius: const BorderRadius.horizontal(
                                    right: Radius.circular(8),
                                  ),
                                  borderSide: const BorderSide(
                                    color: AppColors.lineStrong,
                                  ),
                                ),
                                focusedBorder: OutlineInputBorder(
                                  borderRadius: const BorderRadius.horizontal(
                                    right: Radius.circular(8),
                                  ),
                                  borderSide: const BorderSide(
                                    color: AppColors.accent,
                                    width: 1.4,
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ),
                      ],
                    ),

                    const SizedBox(height: 20),
                    FilledButton(
                      onPressed: _submitting ? null : _sendOtp,
                      style: FilledButton.styleFrom(
                        backgroundColor: AppColors.accentDark,
                        disabledBackgroundColor: AppColors.accentDark
                            .withValues(alpha: 0.6),
                        minimumSize: const Size.fromHeight(48),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(8),
                        ),
                        elevation: 1,
                      ),
                      child: _submitting
                          ? const SizedBox(
                              width: 18,
                              height: 18,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                                color: Colors.white,
                              ),
                            )
                          : Text(
                              'SEND OTP',
                              style: AppTypography.button(
                                size: 13,
                                letterSpacing: 0.6,
                              ),
                            ),
                    ),

                    const SizedBox(height: 20),
                    Text(
                      "New here? You'll be guided to finish creating an account right after your number is verified.",
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(size: 12.5).copyWith(
                        height: 1.6,
                      ),
                    ),
                    const SizedBox(height: 4),
                    TextButton(
                      onPressed: () =>
                          Navigator.of(context).pushReplacementNamed('/register'),
                      child: Text(
                        'Create account',
                        style: AppTypography.bodyMedium(
                          size: 13,
                          color: AppColors.accentDark,
                        ),
                      ),
                    ),
                    const SizedBox(height: 8),
                    _termsLine(context),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }

  /// Kushals-style consent row: a real interactive Checkbox (checked by
  /// default, brand accent) beside wrapping consent text whose
  /// "Terms & Conditions apply." span opens the existing return-policy CMS
  /// page. Not a gate — the website has no consent checkbox, and _sendOtp
  /// validates phone only. Lives inside the scrollable card so it stays
  /// reachable with the keyboard open.
  Widget _termsLine(BuildContext context) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        SizedBox(
          width: 28,
          height: 28,
          child: Checkbox(
            value: _consentGiven,
            onChanged: (v) => setState(() => _consentGiven = v ?? false),
            activeColor: AppColors.accentDark,
            checkColor: Colors.white,
            side: const BorderSide(color: AppColors.lineStrong, width: 1.4),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(4),
            ),
            visualDensity: VisualDensity.compact,
            materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Padding(
            padding: const EdgeInsets.only(top: 5),
            child: RichText(
              text: TextSpan(
                style: AppTypography.bodySmall(size: 12.5),
                children: [
                  const TextSpan(
                    text:
                        'I agree to receive updates and communication from Estele. ',
                  ),
                  TextSpan(
                    text: 'Terms & Conditions apply.',
                    style: AppTypography.bodySmall(size: 12.5).copyWith(
                      color: AppColors.accentDark,
                      decoration: TextDecoration.underline,
                    ),
                    recognizer: TapGestureRecognizer()
                      ..onTap = () =>
                          Navigator.of(context).pushNamed('/cms/return-policy'),
                  ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _ErrorText extends StatelessWidget {
  const _ErrorText(this.message);

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: AppColors.pinkSoft,
        borderRadius: BorderRadius.circular(3),
        border: Border.all(color: AppColors.accent.withValues(alpha: 0.4)),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline, size: 16, color: AppColors.error),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              message,
              style: AppTypography.bodySmall(
                size: 12.5,
                color: AppColors.error,
              ),
            ),
          ),
        ],
      ),
    );
  }
}