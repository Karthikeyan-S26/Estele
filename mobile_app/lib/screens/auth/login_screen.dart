import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

/// Website-equivalent of the site's /login — mobile number + OTP only. One OTP
/// round covers both login and registration: an existing number signs in, a new
/// number is carried to account completion.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _phone = TextEditingController();
  final _otp = TextEditingController();

  bool _otpSent = false;
  bool _submitting = false;
  String? _error;

  @override
  void dispose() {
    _phone.dispose();
    _otp.dispose();
    super.dispose();
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
    setState(() {
      _submitting = false;
      if (err == null) {
        _otpSent = true;
      } else {
        _error = err;
      }
    });
  }

  Future<void> _verifyOtp() async {
    if (_otp.text.trim().length != 6) {
      setState(() => _error = 'Enter the 6-digit code sent to your phone');
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.verifyMobileOtp(
      _phone.text.trim(),
      _otp.text.trim(),
    );
    if (!mounted) return;
    if (err == null) {
      if (auth.isAuthenticated) {
        Navigator.of(context).pop();
      } else if (auth.verificationToken != null) {
        // New customer — verified phone is carried to account completion.
        Navigator.of(
          context,
        ).pushReplacementNamed('/register', arguments: _phone.text.trim());
      }
    } else {
      setState(() {
        _submitting = false;
        _error = err;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.ivory,
      appBar: AppBar(
        backgroundColor: AppColors.ivory,
        foregroundColor: AppColors.heading,
        elevation: 0,
        title: const Text(''),
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
                child: _otpSent ? _buildVerifyCard() : _buildPhoneCard(),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildPhoneCard() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Welcome Back',
          textAlign: TextAlign.center,
          style: AppTypography.editorial(size: 24, weight: FontWeight.w600),
        ),
        const SizedBox(height: 4),
        Text(
          "We'll text a one-time verification code to your phone.",
          textAlign: TextAlign.center,
          style: AppTypography.bodySmall(size: 13),
        ),
        const SizedBox(height: 24),

        if (_error != null) ...[
          _ErrorText(_error!),
          const SizedBox(height: 12),
        ],

        // Mobile Number
        Text('Mobile Number', style: AppTypography.bodyMedium(size: 13)),
        const SizedBox(height: 6),
        Row(
          children: [
            Container(
              height: 48,
              padding: const EdgeInsets.symmetric(horizontal: 14),
              alignment: Alignment.center,
              decoration: const BoxDecoration(
                color: AppColors.warmBeige,
                borderRadius: BorderRadius.horizontal(left: Radius.circular(8)),
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
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: const BorderRadius.horizontal(
                        right: Radius.circular(8),
                      ),
                      borderSide: const BorderSide(color: AppColors.lineStrong),
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
            disabledBackgroundColor: AppColors.accentDark.withValues(
              alpha: 0.6,
            ),
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
                  style: AppTypography.button(size: 13, letterSpacing: 0.6),
                ),
        ),

        const SizedBox(height: 20),
        Text(
          "New here? You'll be guided to finish creating an account right after your number is verified.",
          textAlign: TextAlign.center,
          style: AppTypography.bodySmall(size: 12.5).copyWith(height: 1.6),
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
      ],
    );
  }

  Widget _buildVerifyCard() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(
          'Enter Verification Code',
          textAlign: TextAlign.center,
          style: AppTypography.editorial(size: 24, weight: FontWeight.w600),
        ),
        const SizedBox(height: 4),
        Text.rich(
          textAlign: TextAlign.center,
          TextSpan(
            style: AppTypography.bodySmall(size: 13),
            children: [
              const TextSpan(text: "We sent a 6-digit code to "),
              TextSpan(
                text: '+91 ${_phone.text.trim()}',
                style: AppTypography.bodySmall(
                  size: 13,
                  weight: FontWeight.w700,
                  color: AppColors.heading,
                ),
              ),
              const TextSpan(text: '.'),
            ],
          ),
        ),
        const SizedBox(height: 24),

        if (_error != null) ...[
          _ErrorText(_error!),
          const SizedBox(height: 12),
        ],

        // One-Time Code — centered, wide tracking like the web's letter-spacing.
        Text(
          'One-Time Code',
          textAlign: TextAlign.center,
          style: AppTypography.bodyMedium(size: 13),
        ),
        const SizedBox(height: 6),
        SizedBox(
          height: 52,
          child: TextField(
            controller: _otp,
            enabled: !_submitting,
            keyboardType: TextInputType.number,
            maxLength: 6,
            autofocus: true,
            textAlign: TextAlign.center,
            onSubmitted: (_) => _verifyOtp(),
            style: AppTypography.body(
              size: 20,
              weight: FontWeight.w600,
            ).copyWith(letterSpacing: 8),
            decoration: InputDecoration(
              counterText: '',
              hintText: '••••••',
              hintStyle: AppTypography.body(size: 18, color: AppColors.muted),
              filled: true,
              fillColor: AppColors.paper,
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
                borderSide: const BorderSide(color: AppColors.lineStrong),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(8),
                borderSide: const BorderSide(
                  color: AppColors.accent,
                  width: 1.4,
                ),
              ),
            ),
          ),
        ),

        const SizedBox(height: 20),
        FilledButton(
          onPressed: _submitting ? null : _verifyOtp,
          style: FilledButton.styleFrom(
            backgroundColor: AppColors.accentDark,
            disabledBackgroundColor: AppColors.accentDark.withValues(
              alpha: 0.6,
            ),
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
                  'VERIFY & CONTINUE',
                  style: AppTypography.button(size: 13, letterSpacing: 0.6),
                ),
        ),

        const SizedBox(height: 14),
        TextButton(
          onPressed: _submitting ? null : _sendOtp,
          child: Text(
            "Didn't receive code? Resend",
            style: AppTypography.bodyMedium(
              size: 13,
              color: AppColors.accentDark,
            ),
          ),
        ),
        const SizedBox(height: 8),
        TextButton(
          onPressed: _submitting
              ? null
              : () => setState(() {
                  _otpSent = false;
                  _otp.clear();
                  _error = null;
                }),
          child: Text(
            'Wrong mobile number? Start over',
            style: AppTypography.bodyMedium(
              size: 13,
              color: AppColors.accentDark,
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
