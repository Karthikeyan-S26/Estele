import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../data/api_client.dart';
import '../data/repositories/content_repository.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// "Get the Glow" newsletter email-capture — the native equivalent of the
/// website's emailer popup (same title, subtitle and 5%-off intent), served
/// by the existing `POST /api/newsletter/subscribe` endpoint. Shown on Home
/// and Product Detail only, at most once per install: subscribing records
/// [subscribedKey], dismissing records [dismissedKey], and [maybeShow]
/// no-ops once either is set, so rebuilds and navigation never re-trigger
/// it. No new dependencies, no WebView.
class NewsletterPopup {
  static const subscribedKey = 'newsletter_subscribed_v1';
  static const dismissedKey = 'newsletter_dismissed_v1';

  /// Shows the dialog unless already subscribed/dismissed. Safe to call
  /// from post-frame callbacks — re-entrancy guarded by [ModalRoute].
  static Future<void> maybeShow(BuildContext context) async {
    final prefs = await SharedPreferences.getInstance();
    if (prefs.getBool(subscribedKey) == true ||
        prefs.getBool(dismissedKey) == true) {
      return;
    }
    if (!context.mounted) return;
    final route = ModalRoute.of(context);
    if (route == null || !route.isCurrent) return;

    await showDialog<void>(
      context: context,
      barrierDismissible: true,
      builder: (_) => const _NewsletterDialog(),
    );
    // Any close path (X, barrier tap, back button, Done) counts as
    // dismissed so the popup never nags.
    await prefs.setBool(dismissedKey, true);
  }

  static Future<void> _markSubscribed() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool(subscribedKey, true);
  }
}

class _NewsletterDialog extends StatefulWidget {
  const _NewsletterDialog();

  @override
  State<_NewsletterDialog> createState() => _NewsletterDialogState();
}

class _NewsletterDialogState extends State<_NewsletterDialog> {
  final _email = TextEditingController();
  bool _sending = false;
  String? _error;
  String? _doneMessage;

  @override
  void dispose() {
    _email.dispose();
    super.dispose();
  }

  static final _emailPattern =
      RegExp(r'^[^@\s]+@[^@\s]+\.[^@\s]+$');

  Future<void> _subscribe() async {
    final email = _email.text.trim();
    if (!_emailPattern.hasMatch(email)) {
      setState(() => _error = 'Enter a valid email address.');
      return;
    }
    setState(() {
      _sending = true;
      _error = null;
    });
    try {
      final message = await ContentRepository.subscribeNewsletter(email);
      await NewsletterPopup._markSubscribed();
      if (!mounted) return;
      setState(() {
        _sending = false;
        _doneMessage = message;
      });
    } on ApiException catch (e) {
      // Server validation (422) carries the human message; anything else
      // (network, 5xx, throttle) falls back to a generic, non-raw line.
      if (!mounted) return;
      setState(() {
        _sending = false;
        _error = e.message.isNotEmpty
            ? e.message
            : 'Could not subscribe right now. Please try again.';
      });
    } catch (_) {
      if (!mounted) return;
      setState(() {
        _sending = false;
        _error = 'Could not subscribe right now. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      backgroundColor: AppColors.paper,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      contentPadding: EdgeInsets.zero,
      insetPadding: const EdgeInsets.symmetric(horizontal: 28),
      content: SingleChildScrollView(
        padding: EdgeInsets.only(
          left: 24,
          right: 24,
          top: 12,
          bottom: 24 + MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Align(
              alignment: Alignment.centerRight,
              child: IconButton(
                onPressed: () => Navigator.of(context).pop(),
                icon: const Icon(Icons.close_rounded, size: 20),
                color: AppColors.muted,
                padding: EdgeInsets.zero,
                constraints: const BoxConstraints.tightFor(
                  width: 36,
                  height: 36,
                ),
              ),
            ),
            if (_doneMessage == null) ...[
              Text(
                'Get the Glow',
                textAlign: TextAlign.center,
                style: AppTypography.scriptAccent(size: 28),
              ),
              const SizedBox(height: 6),
              Text(
                'Subscribe to our emailer and get 5% off your first purchase.',
                textAlign: TextAlign.center,
                style: AppTypography.bodySmall(size: 13),
              ),
              const SizedBox(height: 18),
              TextField(
                controller: _email,
                enabled: !_sending,
                keyboardType: TextInputType.emailAddress,
                textInputAction: TextInputAction.done,
                autocorrect: false,
                onSubmitted: (_) => _subscribe(),
                decoration: InputDecoration(
                  hintText: 'Email address',
                  hintStyle: AppTypography.bodySmall(size: 14),
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(
                    horizontal: 16,
                    vertical: 14,
                  ),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(8),
                    borderSide:
                        const BorderSide(color: AppColors.lineStrong),
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
              if (_error != null) ...[
                const SizedBox(height: 10),
                Text(
                  _error!,
                  textAlign: TextAlign.center,
                  style: AppTypography.bodySmall(
                    size: 12.5,
                    color: AppColors.error,
                  ),
                ),
              ],
              const SizedBox(height: 16),
              FilledButton(
                onPressed: _sending ? null : _subscribe,
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.accentDark,
                  disabledBackgroundColor: AppColors.accentDark.withValues(
                    alpha: 0.6,
                  ),
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: _sending
                    ? const SizedBox(
                        width: 18,
                        height: 18,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          color: Colors.white,
                        ),
                      )
                    : Text(
                        'Subscribe',
                        style: AppTypography.button(
                          size: 13,
                          letterSpacing: 0.6,
                        ),
                      ),
              ),
            ] else ...[
              const SizedBox(height: 8),
              const Icon(
                Icons.check_circle_rounded,
                size: 44,
                color: AppColors.success,
              ),
              const SizedBox(height: 12),
              Text(
                _doneMessage!,
                textAlign: TextAlign.center,
                style: AppTypography.bodyMedium(size: 14),
              ),
              const SizedBox(height: 18),
              FilledButton(
                onPressed: () => Navigator.of(context).pop(),
                style: FilledButton.styleFrom(
                  backgroundColor: AppColors.accentDark,
                  minimumSize: const Size.fromHeight(48),
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(8),
                  ),
                ),
                child: Text(
                  'Done',
                  style: AppTypography.button(size: 13, letterSpacing: 0.6),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
