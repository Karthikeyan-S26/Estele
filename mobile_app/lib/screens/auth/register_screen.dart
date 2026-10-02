import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../providers/checkout_intent.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

/// Account completion for a NEW customer — mirrors the website's /register
/// form ("Create Your Account", Full Name + Mobile, "Verified via OTP."). No
/// email or password: the app authenticates by mobile number + OTP only.
///
/// Usually arrives from the login flow with the phone already OTP-verified (the
/// provider holds the single-use nonce). Entered directly, it runs the same OTP
/// round inline before the name field can be submitted. If the number turns out
/// to belong to an existing customer, verify signs the user in directly (same
/// as the website) instead of creating a duplicate.
class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key, this.prefillPhone});

  final String? prefillPhone;

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  late final TextEditingController _name;
  late final TextEditingController _phone;
  final _otp = TextEditingController();

  late bool _verified;
  bool _otpSent = false;
  bool _submitting = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    final auth = context.read<AuthProvider>();
    // A live, phone-matched nonce from the login flow means this number has
    // already been OTP-verified — skip straight to the name field.
    final prefill = widget.prefillPhone;
    _verified =
        prefill != null &&
        auth.verifiedPhone == prefill &&
        auth.verificationToken != null;
    _phone = TextEditingController(text: prefill ?? '');
    _name = TextEditingController();
    if (_verified) _otpSent = true;
  }

  @override
  void dispose() {
    _name.dispose();
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
        // Existing customer discovered mid-registration — the website behaves
        // the same way (verified number goes straight to the account). If a
        // Buy Now order is mid-flight, drop back into checkout.
        _finish();
      } else if (auth.verificationToken != null) {
        setState(() => _verified = true);
      }
    } else {
      setState(() {
        _submitting = false;
        _error = err;
      });
    }
  }

  Future<void> _submit() async {
    if (_name.text.trim().isEmpty) {
      setState(() => _error = 'Please enter your full name');
      return;
    }
    if (!_verified) {
      setState(
        () => _error = 'Please verify your mobile number with OTP first',
      );
      return;
    }
    setState(() {
      _submitting = true;
      _error = null;
    });
    final err = await context.read<AuthProvider>().registerMobile(
      name: _name.text.trim(),
      phone: _phone.text.trim(),
    );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (err == null) {
      _finish();
    } else {
      setState(() => _error = err);
    }
  }

  /// After a successful create-account (or a mid-registration find of an
  /// existing customer) the web app returns the user to wherever they came
  /// from. For a Buy Now express order the whole guest-auth stack is replaced
  /// by checkout (the verified + merged cart is already server-side);
  /// otherwise go back a step — the login screen dismisses itself once it
  /// observes the authenticated session.
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

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create Your Account')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Create Your Account',
                style: AppTypography.scriptAccent(size: 30),
              ),
              const SizedBox(height: 4),
              Text(
                'Join Estele for exclusive member offers & faster checkout.',
                style: AppTypography.sectionTitle(size: 19),
              ),
              const SizedBox(height: 18),

              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _ErrorText(_error!),
                ),

              TextFormField(
                controller: _phone,
                enabled: !_verified,
                keyboardType: TextInputType.phone,
                maxLength: 10,
                decoration: InputDecoration(
                  labelText: 'Mobile Number',
                  prefixText: '+91 ',
                  counterText: '',
                  suffixIcon: _verified
                      ? const Icon(Icons.verified, color: AppColors.success)
                      : null,
                  helperText: _verified ? 'Verified via OTP.' : null,
                ),
              ),

              if (!_verified) ...[
                const SizedBox(height: 12),
                TextFormField(
                  controller: _otp,
                  keyboardType: TextInputType.number,
                  maxLength: 6,
                  decoration: const InputDecoration(
                    labelText: 'One-Time Code',
                    counterText: '',
                  ),
                ),
                const SizedBox(height: 16),
                if (_otpSent)
                  FilledButton.icon(
                    onPressed: _submitting ? null : _verifyOtp,
                    icon: _submitting
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.verified_user_outlined, size: 18),
                    label: const Text('Verify'),
                  )
                else
                  FilledButton.icon(
                    onPressed: _submitting ? null : _sendOtp,
                    icon: _submitting
                        ? const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          )
                        : const Icon(Icons.sms_outlined, size: 18),
                    label: const Text('Send OTP'),
                  ),
              ],

              const SizedBox(height: 22),
              TextFormField(
                controller: _name,
                enabled: _verified,
                textCapitalization: TextCapitalization.words,
                decoration: const InputDecoration(labelText: 'Full Name'),
                onFieldSubmitted: (_) => _verified ? _submit() : null,
              ),
              const SizedBox(height: 20),

              FilledButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Text('Create Account'),
              ),

              const SizedBox(height: 20),
              // Same source as the website footer ("Terms & Conditions" →
              // the return-policy CMS page). Informational link only — the
              // website's /register has no acceptance checkbox, so account
              // creation is never gated on it here either.
              Center(
                child: RichText(
                  textAlign: TextAlign.center,
                  text: TextSpan(
                    style: AppTypography.bodySmall(),
                    children: [
                      const TextSpan(
                        text: 'By creating an account you agree to our ',
                      ),
                      TextSpan(
                        text: 'Terms & Conditions',
                        style: AppTypography.bodySmall().copyWith(
                          color: AppColors.accentDark,
                          decoration: TextDecoration.underline,
                        ),
                        recognizer: TapGestureRecognizer()
                          ..onTap = () => Navigator.of(
                            context,
                          ).pushNamed('/cms/return-policy'),
                      ),
                      const TextSpan(text: '.'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    'Already have an account?',
                    style: AppTypography.bodySmall(),
                  ),
                  TextButton(
                    onPressed: () =>
                        Navigator.of(context).pushReplacementNamed('/login'),
                    child: const Text('Log in'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
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
