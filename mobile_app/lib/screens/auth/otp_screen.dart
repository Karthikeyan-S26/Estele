import 'dart:async';

import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../providers/checkout_intent.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';
import '../../widgets/otp_code_field.dart';

/// Website OTP page as a native screen — "Enter Verification Code", the
/// masked recipient number with an Edit action, six boxes that verify
/// automatically the instant the 6th digit is entered (no extra button),
/// a working resend countdown, and one retry-tolerant error path. The code
/// is verified against the real `/auth/mobile/verify-otp` API; the backend
/// decides whether the number belongs to an existing customer (→ signed in)
/// or a new one (→ carried to Create Account).
class OtpScreen extends StatefulWidget {
  const OtpScreen({super.key, required this.phone});

  final String phone;

  @override
  State<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends State<OtpScreen> {
  final _code = OtpCodeFieldController();
  Timer? _resendTimer;
  int _resendIn = 0;
  bool _sending = false;
  bool _verifying = false;
  String? _error;

  String get _maskedPhone {
    final p = widget.phone.trim();
    if (p.length <= 4) return p;
    return 'XXXXXX${p.substring(p.length - 4)}';
  }

  @override
  void initState() {
    super.initState();
    _startResendCountdown();
  }

  @override
  void dispose() {
    _resendTimer?.cancel();
    _code.dispose();
    super.dispose();
  }

  void _startResendCountdown() {
    _resendTimer?.cancel();
    setState(() => _resendIn = 30);
    _resendTimer = Timer.periodic(const Duration(seconds: 1), (t) {
      if (!mounted) return;
      if (_resendIn <= 1) {
        t.cancel();
        setState(() => _resendIn = 0);
      } else {
        setState(() => _resendIn--);
      }
    });
  }

  Future<void> _verify(String code) async {
    if (_verifying) return; // duplicate-submission guard
    setState(() {
      _verifying = true;
      _error = null;
    });

    final auth = context.read<AuthProvider>();
    final err = await auth.verifyMobileOtp(widget.phone.trim(), code);
    if (!mounted) return;

    if (err != null) {
      _code.clear();
      setState(() {
        _verifying = false;
        _error = err;
      });
      return;
    }

    if (auth.isAuthenticated) {
      // Existing customer — signed in, guest cart merged server-side.
      _finish();
    } else if (auth.verificationToken != null) {
      // New customer — verified phone + nonce carried to account creation.
      Navigator.of(context)
          .pushReplacementNamed('/register', arguments: widget.phone.trim());
    } else {
      setState(() => _verifying = false);
    }
  }

  /// Land wherever the original flow expected. For a Buy Now express order
  /// the whole guest-auth stack is replaced by checkout (fresh, authenticated
  /// instance — the pending item was merged into the user cart server-side).
  /// Otherwise just go back a step; the login screen dismisses itself once it
  /// observes the now-authenticated session.
  void _finish() {
    if (CheckoutIntent.isArmed) {
      CheckoutIntent.disarm();
      Navigator.of(context).pushNamedAndRemoveUntil(
        '/checkout',
        (route) => route.isFirst,
      );
    } else {
      Navigator.of(context).pop();
    }
  }

  Future<void> _resend() async {
    if (_resendIn > 0 || _sending || _verifying) return;
    setState(() {
      _sending = true;
      _error = null;
    });
    final err = await context.read<AuthProvider>().sendMobileOtp(
      widget.phone.trim(),
    );
    if (!mounted) return;
    setState(() => _sending = false);
    if (err != null) {
      setState(() => _error = err);
      return;
    }
    _code.clear();
    _error = null;
    ScaffoldMessenger.of(
      context,
    ).showSnackBar(const SnackBar(content: Text('A new code has been sent.')));
    _startResendCountdown();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.ivory,
      appBar: AppBar(
        backgroundColor: AppColors.ivory,
        foregroundColor: AppColors.heading,
        elevation: 0,
        title: const Text('Verification'),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
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
                      'Enter Verification Code',
                      textAlign: TextAlign.center,
                      style: AppTypography.editorial(
                        size: 24,
                        weight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      "We've sent a 6-digit code to your mobile number.",
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(size: 13),
                    ),
                    const SizedBox(height: 6),
                    Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text(
                          'Sent to: +91 $_maskedPhone',
                          style: AppTypography.bodySmall(
                            size: 13,
                            weight: FontWeight.w700,
                          ),
                        ),
                        const SizedBox(width: 8),
                        TextButton(
                          onPressed:
                              _sending || _verifying ? null : () => Navigator.of(context).pop(),
                          style: TextButton.styleFrom(
                            padding: EdgeInsets.zero,
                            minimumSize: const Size(0, 32),
                            tapTargetSize: MaterialTapTargetSize.shrinkWrap,
                          ),
                          child: Text(
                            'Edit',
                            style: AppTypography.bodySmall(
                              size: 13,
                              color: AppColors.accentDark,
                            ).copyWith(decoration: TextDecoration.underline),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 24),

                    if (_error != null) ...[
                      _ErrorBanner(_error!),
                      const SizedBox(height: 12),
                    ],

                    OtpCodeField(
                      controller: _code,
                      onCompleted: _verify,
                      disabled: _verifying || _sending,
                    ),
                    const SizedBox(height: 10),
                    Text(
                      'Entering the code verifies automatically — no extra button needed.',
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(
                        size: 11.5,
                        color: AppColors.muted,
                      ),
                    ),

                    const SizedBox(height: 18),
                    Center(
                      child: _verifying
                          ? Padding(
                              padding: const EdgeInsets.symmetric(vertical: 6),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const SizedBox(
                                    width: 16,
                                    height: 16,
                                    child: CircularProgressIndicator(
                                      strokeWidth: 2,
                                    ),
                                  ),
                                  const SizedBox(width: 10),
                                  Text(
                                    'Verifying…',
                                    style: AppTypography.bodySmall(size: 13),
                                  ),
                                ],
                              ),
                            )
                          : Text(
                              "Didn't receive the code?",
                              style: AppTypography.bodySmall(size: 13),
                            ),
                    ),
                    const SizedBox(height: 4),
                    Center(
                      child: _sending
                          ? const SizedBox(
                              width: 16,
                              height: 16,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                              ),
                            )
                          : TextButton(
                              onPressed: _resendIn > 0 ? null : _resend,
                              child: Text(
                                _resendIn > 0
                                    ? 'Resend code in 0:${_resendIn.toString().padLeft(2, '0')}'
                                    : 'Resend code',
                                style: AppTypography.bodyMedium(
                                  size: 13,
                                  color: _resendIn > 0
                                      ? AppColors.muted
                                      : AppColors.accentDark,
                                ),
                              ),
                            ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      "We'll send a one-time code. No password needed.",
                      textAlign: TextAlign.center,
                      style: AppTypography.bodySmall(
                        size: 11.5,
                        color: AppColors.muted,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner(this.message);

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