import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

/// OTP-first registration: verify the phone with an OTP, then create the
/// account. The verified phone number is remembered and sent with the final
/// register call so the backend can validate it.
class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _formKey = GlobalKey<FormState>();
  final _name = TextEditingController();
  final _email = TextEditingController();
  final _phone = TextEditingController();
  final _otp = TextEditingController();
  final _password = TextEditingController();

  bool _verified = false;
  bool _otpSent = false;
  bool _submitting = false;
  String? _otpError;
  String? _formError;

  @override
  void dispose() {
    _name.dispose();
    _email.dispose();
    _phone.dispose();
    _otp.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _sendOtp() async {
    setState(() {
      _submitting = true;
      _otpError = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.sendRegisterOtp(_phone.text.trim());
    if (!mounted) return;
    setState(() {
      _submitting = false;
      if (err == null) {
        _otpSent = true;
      } else {
        _otpError = err;
      }
    });
  }

  Future<void> _verifyOtp() async {
    setState(() {
      _submitting = true;
      _otpError = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.verifyRegisterOtp(_phone.text.trim(), _otp.text.trim());
    if (!mounted) return;
    setState(() {
      _submitting = false;
      if (err == null) {
        _verified = true;
      } else {
        _otpError = err;
      }
    });
  }

  Future<void> _submit() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    if (!_verified) {
      setState(() => _formError = 'Please verify your phone number first.');
      return;
    }
    setState(() {
      _submitting = true;
      _formError = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.register(
      name: _name.text.trim(),
      email: _email.text.trim(),
      phone: _phone.text.trim(),
      password: _password.text,
    );
    if (!mounted) return;
    setState(() => _submitting = false);
    if (err == null) {
      Navigator.of(context).popUntil((r) => r.isFirst);
    } else {
      setState(() => _formError = err);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create account')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Join Estele', style: AppTypography.scriptAccent(size: 30)),
              Text('Create your account to start your collection', style: AppTypography.sectionTitle(size: 19)),
              const SizedBox(height: 18),

              if (_formError != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: Container(
                    padding: const EdgeInsets.all(10),
                    decoration: BoxDecoration(
                      color: AppColors.pinkSoft,
                      borderRadius: BorderRadius.circular(3),
                      border: Border.all(color: AppColors.accent.withValues(alpha: 0.4)),
                    ),
                    child: Text(_formError!, style: AppTypography.bodySmall(size: 12.5, color: AppColors.error)),
                  ),
                ),

              // Step 1 — phone verification
              Text('Step 1 · Verify your phone', style: AppTypography.label(letterSpacing: 1)),
              const SizedBox(height: 10),
              Row(
                children: [
                  Expanded(
                    child: TextFormField(
                      controller: _phone,
                      enabled: !_verified,
                      keyboardType: TextInputType.phone,
                      decoration: const InputDecoration(
                        labelText: 'Phone',
                        prefixText: '+91 ',
                        suffixIcon: Icon(Icons.verified_outlined),
                      ),
                      validator: (v) => (v == null || v.length != 10 || !RegExp(r'^\d{10}$').hasMatch(v))
                          ? 'Valid 10-digit number required'
                          : null,
                    ),
                  ),
                  const SizedBox(width: 10),
                  if (!_otpSent)
                    OutlinedButton(
                      onPressed: _submitting ? null : _sendOtp,
                      child: _submitting
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Text('Send OTP'),
                    ),
                ],
              ),
              if (_otpSent && !_verified) ...[
                const SizedBox(height: 10),
                Row(
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _otp,
                        enabled: !_verified,
                        keyboardType: TextInputType.number,
                        maxLength: 6,
                        decoration: InputDecoration(
                          labelText: 'Enter OTP',
                          counterText: '',
                          errorText: _otpError,
                        ),
                      ),
                    ),
                    const SizedBox(width: 10),
                    OutlinedButton(
                      onPressed: _submitting ? null : _verifyOtp,
                      child: _submitting
                          ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Text('Verify'),
                    ),
                  ],
                ),
              ],
              if (_verified)
                Row(
                  children: [
                    const Icon(Icons.check_circle, color: AppColors.success, size: 18),
                    const SizedBox(width: 6),
                    Text('Phone verified', style: AppTypography.bodyMedium(weight: FontWeight.w600, color: AppColors.success)),
                  ],
                ),

              const SizedBox(height: 22),
              // Step 2 — details
              Text('Step 2 · Your details', style: AppTypography.label(letterSpacing: 1)),
              const SizedBox(height: 10),
              Form(
                key: _formKey,
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    TextFormField(
                      controller: _name,
                      decoration: const InputDecoration(labelText: 'Full name'),
                      validator: (v) => (v == null || v.trim().isEmpty) ? 'Name is required' : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _email,
                      keyboardType: TextInputType.emailAddress,
                      decoration: const InputDecoration(labelText: 'Email'),
                      validator: (v) => (v != null && v.isNotEmpty && !v.contains('@')) ? 'Enter a valid email' : null,
                    ),
                    const SizedBox(height: 12),
                    TextFormField(
                      controller: _password,
                      obscureText: true,
                      decoration: const InputDecoration(labelText: 'Password (min 8 characters)'),
                      validator: (v) => (v == null || v.length < 8) ? 'Password must be at least 8 characters' : null,
                    ),
                    const SizedBox(height: 20),
                    FilledButton(
                      onPressed: _submitting ? null : _submit,
                      child: _submitting
                          ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                          : const Text('Create account'),
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 20),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text('Already a member?', style: AppTypography.bodySmall()),
                  TextButton(
                    onPressed: () => Navigator.of(context).pushNamed('/login'),
                    child: const Text('Sign in'),
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