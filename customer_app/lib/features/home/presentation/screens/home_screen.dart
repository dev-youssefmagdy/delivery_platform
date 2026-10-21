import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../account/presentation/screens/account_screen.dart';
import '../../../trips/presentation/screens/trips_screen.dart';
import '../widgets/home_tab.dart';

/// One bottom-navigation destination.
class ShellTabSpec {
  const ShellTabSpec({
    required this.key,
    required this.icon,
    required this.selectedIcon,
    required this.label,
    required this.builder,
    this.featureKey,
  });

  final String key;
  final IconData icon;
  final IconData selectedIcon;
  final String Function(AppLocalizations l10n) label;
  final WidgetBuilder builder;

  /// Server feature flag (`GET /config/app` `features`) that must be on for
  /// the tab to exist. Wallet and Offers join here with their flags in P3.8;
  /// until a flag is on the tab is not shown (no placeholder screens).
  final String? featureKey;
}

final shellTabSpecs = <ShellTabSpec>[
  ShellTabSpec(
    key: 'home',
    icon: Icons.home_outlined,
    selectedIcon: Icons.home,
    label: (l) => l.navHome,
    builder: (_) => const HomeTab(),
  ),
  ShellTabSpec(
    key: 'trips',
    icon: Icons.history,
    selectedIcon: Icons.history,
    label: (l) => l.navTrips,
    builder: (_) => const TripsScreen(),
  ),
  ShellTabSpec(
    key: 'account',
    icon: Icons.person_outline,
    selectedIcon: Icons.person,
    label: (l) => l.navAccount,
    builder: (_) => const AccountScreen(),
  ),
];

class ShellTabNotifier extends Notifier<String> {
  @override
  String build() => 'home';

  void select(String key) => state = key;
}

final shellTabProvider = NotifierProvider<ShellTabNotifier, String>(
  ShellTabNotifier.new,
);

/// Post-login shell (router guard: signed in, profile complete, consents
/// current): bottom navigation over Home, My Trips and Account.
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key, this.enabledFeatures});

  /// Feature flags override for tests; the app reads them from the config.
  final bool Function(String featureKey)? enabledFeatures;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final tabs = [
      for (final t in shellTabSpecs)
        if (t.featureKey == null || (enabledFeatures?.call(t.featureKey!) ?? false))
          t,
    ];
    final selectedKey = ref.watch(shellTabProvider);
    final index = tabs.indexWhere((t) => t.key == selectedKey).clamp(0, tabs.length - 1);
    return Scaffold(
      body: IndexedStack(
        index: index,
        children: [for (final t in tabs) t.builder(context)],
      ),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (i) =>
            ref.read(shellTabProvider.notifier).select(tabs[i].key),
        destinations: [
          for (final t in tabs)
            NavigationDestination(
              key: Key('nav-${t.key}'),
              icon: Icon(t.icon),
              selectedIcon: Icon(t.selectedIcon),
              label: t.label(l10n),
            ),
        ],
      ),
    );
  }
}
