import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../providers/auth_provider.dart';
import '../../theme/app_colors.dart';
import '../../theme/app_typography.dart';

class ForgotPasswordScreen extends StatefulWidget {
  const ForgotPasswordScreen({super.key});

  @override
  State<ForgotPasswordScreen> createState() => _ForgotPasswordScreenState();
}

class _ForgotPasswordScreenState extends State<ForgotPasswordScreen> {
  final _email = TextEditingController();
  bool _submitting = false;
  bool _sent = false;
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    setState(() {
      _submitting = true;
      _error = null;
    });
    final auth = context.read<AuthProvider>();
    final err = await auth.forgotPassword(_email.text.trim());
    if (!mounted) return;
    setState(() {
      _submitting = false;
      if (err == null) {
        _sent = true;
      } else {
        _error = err;
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Reset password')),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text('No worries', style: AppTypography.scriptAccent(size: 30)),
              Text('Reset your password', style: AppTypography.sectionTitle(size: 19)),
              const SizedBox(height: 14),
              Text(
                _sent
                    ? 'If an account exists for that email, we have sent a reset link. Check your inbox.'
                    : 'Enter the email on your account and we will send you a password reset link.',
                style: AppTypography.body(size: 13.5, color: AppColors.muted),
              ),
              const SizedBox(height: 18),
              if (_error != null)
                Padding(
                  padding: const EdgeInsets.only(bottom: 10),
                  child: Text(_error!, style: AppTypography.bodySmall(size: 12.5, color: AppColors.error)),
                ),
              if (!_sent) ...[
                TextField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(labelText: 'Email'),
                ),
                const SizedBox(height: 16),
                FilledButton(
                  onPressed: _submitting ? null : _submit,
                  child: _submitting
                      ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))
                      : const Text('Send reset link'),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}