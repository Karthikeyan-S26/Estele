import 'dart:async';

import 'package:flutter/material.dart';

import '../theme/app_colors.dart';
import '../theme/app_typography.dart';

/// Branded cold-start splash: a clean white screen with the Estele gold
/// wordmark centered, held briefly, then replaced by the Home shell.
///
/// The Android launch window (`drawable/launch_background` + `values-v31`)
/// already shows the same white + gold mark while the engine boots, so the
/// transition into this screen is seamless. `/` routes here; after [hold] the
/// route is *replaced* with `/home` (the `RootScreen` shell) so the back
/// button can never return to the splash and a relaunch never resumes a
/// previously-viewed screen.
class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  /// How long the branded screen stays before Home loads.
  static const Duration hold = Duration(milliseconds: 1400);

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer(SplashScreen.hold, _goHome);
  }

  Future<void> _goHome() async {
    if (!mounted) return;
    await Navigator.of(context).pushReplacementNamed('/home');
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.paper,
      body: Center(
        child: AppTypography.goldLeafLogo(fontSize: 40),
      ),
    );
  }
}