import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../core/providers.dart';
import '../features/app_config/presentation/providers/app_config_providers.dart';
import '../features/app_config/presentation/widgets/app_gate.dart';
import '../features/auth/presentation/providers/auth_providers.dart';
import '../features/notifications/presentation/providers/notification_providers.dart';
import '../features/notifications/presentation/widgets/push_status_card.dart';
import 'l10n/generated/app_localizations.dart';
import 'router.dart';
import 'theme.dart';

class App extends ConsumerStatefulWidget {
  const App({super.key});

  @override
  ConsumerState<App> createState() => _AppState();
}

class _AppState extends ConsumerState<App> with WidgetsBindingObserver {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    ref.listenManual(authStageProvider, (_, stage) => _syncPush(stage), fireImmediately: true);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Re-read the server config whenever the app returns to the foreground, so
  /// maintenance mode and forced updates take effect without a restart.
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      ref.invalidate(appConfigProvider);
    }
  }

  /// Push runs only for a fully set-up, signed-in account. The OS permission is
  /// requested once, after an explanation; it can be changed in notification settings.
  Future<void> _syncPush(AuthStage stage) async {
    final coordinator = ref.read(pushCoordinatorProvider);
    if (stage == AuthStage.signedOut) {
      await coordinator.stop();
      return;
    }
    if (stage != AuthStage.ready) return;
    await coordinator.start();
    final prefs = ref.read(sharedPreferencesProvider);
    if (coordinator.status.value != PushStatus.permissionNeeded ||
        (prefs.getBool('push_prompted') ?? false)) {
      return;
    }
    await prefs.setBool('push_prompted', true);
    final context = ref.read(routerProvider).routerDelegate.navigatorKey.currentContext;
    if (context != null && context.mounted && await showPushRationale(context)) {
      await coordinator.enable();
    }
  }

  @override
  Widget build(BuildContext context) {
    final locale = ref.watch(localeProvider);
    final router = ref.watch(routerProvider);
    final online = ref.watch(onlineProvider).value ?? true;
    return MaterialApp.router(
      onGenerateTitle: (ctx) => AppLocalizations.of(ctx).appName,
      theme: buildAppTheme(),
      locale: locale,
      supportedLocales: AppLocalizations.supportedLocales,
      localizationsDelegates: const [
        AppLocalizations.delegate,
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      routerConfig: router,
      debugShowCheckedModeBanner: false,
      builder: (context, child) => AppGate(
        child: Column(
          children: [
            if (!online)
              OfflineBanner(
                message: AppLocalizations.of(context).offlineBanner,
              ),
            Expanded(child: child ?? const SizedBox.shrink()),
          ],
        ),
      ),
    );
  }
}
