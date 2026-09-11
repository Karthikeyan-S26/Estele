import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

/// Login with either email+password or phone OTP. All real auth calls go to
/// the backend; errors come back inline.
class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _email = TextEditingController();
  final _password = TextEditingController();
  final _phone = TextEditingController();
  final _otp = TextEditingController();

  bool _otpMode = false;
  bool _otpSent = false;
  bool _submitting = false;
  String? _serverError;
  String? _otpError;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    _phone.dispose();
    _otp.dispose();
    super.dispose();
  }

  Future<void> _sendOtp() async {
    setState(() {
      _submitting = true;
      _otpError = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.sendLoginOtp(_phone.text.trim());
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
    final err = await auth.verifyLoginOtp(_phone.text.trim(), _otp.text.trim());
    if (!mounted) return;
    setState(() => _submitting = false);
    if (err == null) {
      Navigator.of(context).pop();
    } else {
      setState(() => _otpError = err);
    }
  }

  Future<void> _submitPassword() async {
    if (!(_formKey.currentState?.validate() ?? false)) return;
    setState(() {
      _submitting = true;
      _serverError = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.login(_email.text.trim(), _password.text);
    if (!mounted) return;
    setState(() => _submitting = false);
    if (err == null) {
      Navigator.of(context).pop();
    } else {
      setState(() => _serverError = err);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Sign in')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('Welcome back', style: AppTypography.scriptAccent(size: 30)),
              Text(
                _otpMode ? 'Continue with your phone' : 'Sign in to your Estele account',
                style: AppTypography.sectionTitle(size: 19),
              ),
              const SizedBox(height: 18),

              // Mode switch
              SegmentedButton<bool>(
                segments: const [
                  ButtonSegment(value: false, label: Text('Email / Password'), icon: Icon(Icons.mail_outline, size: 16)),
                  ButtonSegment(value: true, label: Text('Phone OTP'), icon: Icon(Icons.smartphone, size: 16)),
                ],
                selected: {_otpMode},
                onSelectionChanged: (s) => setState(() {
                  _otpMode = s.first;
                  _otpSent = false;
                  _otpError = null;
                  _serverError = null;
                }),
              ),
              const SizedBox(height: 18),

              if (_serverError != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 12),
                  child: _ErrorText(_serverError!),
                ),

              Form(
                key: _formKey,
                child: _otpMode ? _buildOtpForm() : _buildEmailForm(),
              ),

              const SizedBox(height: 18),
              Align(
                alignment: Alignment.center,
                child: TextButton(
                  onPressed: () => Navigator.of(context).pushNamed('/forgot-password'),
                  child: const Text('Forgot password?'),
                ),
              ),
              const SizedBox(height: 24),
              Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text("Don't have an account?", style: AppTypography.bodySmall()),
                  TextButton(
                    onPressed: () => Navigator.of(context).pushReplacementNamed('/register'),
                    child: const Text('Create one'),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildEmailForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextFormField(
          controller: _email,
          keyboardType: TextInputType.emailAddress,
          decoration: const InputDecoration(labelText: 'Email'),
          validator: (v) => (v == null || !v.contains('@')) ? 'Enter a valid email' : null,
        ),
        const SizedBox(height: 12),
        TextFormField(
          controller: _password,
          obscureText: true,
          decoration: const InputDecoration(labelText: 'Password'),
          validator: (v) => (v == null || v.isEmpty) ? 'Enter your password' : null,
          onFieldSubmitted: (_) => _submitPassword(),
        ),
        const SizedBox(height: 20),
        FilledButton(
          onPressed: _submitting ? null : _submitPassword,
          child: _submitting
              ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
              : const Text('Sign in'),
        ),
      ],
    );
  }

  Widget _buildOtpForm() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        TextFormField(
          controller: _phone,
          keyboardType: TextInputType.phone,
          decoration: const InputDecoration(
            labelText: 'Phone',
            prefixText: '+91 ',
          ),
          validator: (v) => (v == null || v.length != 10 || !RegExp(r'^\d{10}$').hasMatch(v))
              ? 'Enter a valid 10-digit number'
              : null,
        ),
        const SizedBox(height: 12),
        if (_otpSent) ...[
          TextFormField(
            controller: _otp,
            keyboardType: TextInputType.number,
            maxLength: 6,
            decoration: InputDecoration(
              labelText: 'OTP',
              errorText: _otpError,
            ),
          ),
          const SizedBox(height: 12),
          FilledButton(
            onPressed: _submitting ? null : _verifyOtp,
            child: _submitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Verify & sign in'),
          ),
          TextButton(
            onPressed: _submitting ? null : _sendOtp,
            child: const Text('Resend OTP'),
          ),
        ] else ...[
          FilledButton(
            onPressed: _submitting
                ? null
                : () {
                    if (_formKey.currentState?.validate() ?? false) _sendOtp();
                  },
            child: _submitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                : const Text('Send OTP'),
          ),
        ],
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
          Expanded(child: Text(message, style: AppTypography.bodySmall(size: 12.5, color: AppColors.error))),
        ],
      ),
    );
  }
}