package com.estele.estele

import android.content.Intent
import io.flutter.embedding.android.FlutterActivity
import io.flutter.embedding.engine.FlutterEngine
import io.flutter.plugin.common.MethodChannel

class MainActivity : FlutterActivity() {
    private var launcherChannel: MethodChannel? = null

    override fun configureFlutterEngine(flutterEngine: FlutterEngine) {
        super.configureFlutterEngine(flutterEngine)
        launcherChannel = MethodChannel(
            flutterEngine.dartExecutor.binaryMessenger,
            "estele/launcher",
        )
    }

    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        setIntent(intent)
        // Estele is a single-Activity Flutter app, so a product page is a
        // Navigator route, not a separate Activity — android:clearTaskOnLaunch
        // cannot clear it. When the app is reopened from the launcher icon the
        // OS delivers ACTION_MAIN + CATEGORY_LAUNCHER to this existing instance
        // (launchMode=singleTop), so tell Dart to reset its Navigator to the
        // branded splash -> Home instead of resuming the last viewed screen.
        if (intent.action == Intent.ACTION_MAIN &&
            intent.hasCategory(Intent.CATEGORY_LAUNCHER)
        ) {
            launcherChannel?.invokeMethod("resetToHome", null)
        }
    }
}
