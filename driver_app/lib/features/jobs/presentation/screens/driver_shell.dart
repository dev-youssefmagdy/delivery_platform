import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../availability/presentation/providers/availability_providers.dart';
import '../../../notifications/presentation/providers/notification_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/delivery_providers.dart';
import '../providers/job_providers.dart';
import '../../../earnings/presentation/widgets/earnings_tab.dart';
import '../widgets/active_job_banner.dart';
import '../widgets/history_tab.dart';
import '../widgets/home_tab.dart';
import '../widgets/offers_tab.dart';
import '../widgets/requests_tab.dart';

/// The approved driver's home: Home, Requests, Offers and History tabs with
/// the active-job banner above them. Tabs stay mounted so requests and alerts
/// keep arriving whichever tab is open. Earnings, payouts and incentives are
/// not tabs: they appear only once a server feature flag exists (P3.8).
class DriverShell extends ConsumerStatefulWidget {
  const DriverShell({super.key, required this.vehicles});

  final List<ProviderVehicleInfo> vehicles;

  @override
  ConsumerState<DriverShell> createState() => _DriverShellState();
}

class _DriverShellState extends ConsumerState<DriverShell>
    with WidgetsBindingObserver {
  int _index = 0;
  final _opened = <String>{};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
    // The live connection and tracking follow the availability.
    ref.read(trackingControllerProvider);
    ref.read(realtimeControllerProvider);
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    super.dispose();
  }

  /// Back from the background: restore the active job from the server and
  /// re-read the lists (events may have been missed).
  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state != AppLifecycleState.resumed) return;
    unawaited(ref.read(activeJobProvider.notifier).refresh());
    unawaited(ref.read(availabilityProvider.notifier).refresh());
    unawaited(ref.read(requestsProvider.notifier).refresh());
    unawaited(ref.read(myOffersProvider.notifier).refresh());
  }

  void _maybeOpen(Job? job) {
    if (job == null || job.isFinished || !_opened.add(job.id)) return;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) context.push('${Routes.job}/${job.id}');
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    ref.listen(activeJobProvider, (_, next) => _maybeOpen(next.value));
    // Keeps the offline proof queue alive: proofs captured earlier are sent as soon as the app runs.
    ref.watch(proofQueueProvider);
    final current = ref.read(activeJobProvider).value;
    if (current != null) _maybeOpen(current);
    final requestCount =
        ref.watch(requestsProvider.select((s) => s.value?.requests.length)) ??
        0;

    final titles = [
      l10n.jbTabHome,
      l10n.jbTabRequests,
      l10n.jbTabOffers,
      l10n.jbTabHistory,
      l10n.ernTab,
    ];

    return Scaffold(
      appBar: AppBar(
        title: Text(titles[_index]),
        actions: [
          IconButton(
            key: const Key('driver-notifications'),
            tooltip: l10n.notificationsTitle,
            icon: Badge(
              isLabelVisible: ref.watch(unreadCountProvider) > 0,
              label: Text('${ref.watch(unreadCountProvider)}'),
              child: const Icon(Icons.inbox_outlined),
            ),
            onPressed: () => context.push(Routes.notifications),
          ),
          IconButton(
            key: const Key('onboarding-refresh'),
            tooltip: l10n.actionRefresh,
            icon: const Icon(Icons.refresh),
            onPressed: () {
              unawaited(ref.read(activeJobProvider.notifier).refresh());
              unawaited(ref.read(availabilityProvider.notifier).refresh());
              unawaited(ref.read(requestsProvider.notifier).refresh());
              unawaited(ref.read(myOffersProvider.notifier).refresh());
            },
          ),
          PopupMenuButton<String>(
            key: const Key('onboarding-menu'),
            onSelected: (value) {
              if (value == 'language') {
                context.go('${Routes.language}?from=settings');
              } else if (value == 'safety') {
                context.push(Routes.safetyContacts);
              } else if (value == 'support') {
                context.push(Routes.support);
              } else if (value == 'logout') {
                ref.read(authActionsProvider).logout();
              }
            },
            itemBuilder: (_) => [
              PopupMenuItem(
                key: const Key('driver-safety'),
                value: 'safety',
                child: Text(l10n.sfSettingsTile),
              ),
              PopupMenuItem(
                key: const Key('driver-support'),
                value: 'support',
                child: Text(l10n.supportHelp),
              ),
              PopupMenuItem(
                value: 'language',
                child: Text(l10n.homeChangeLanguage),
              ),
              PopupMenuItem(
                key: const Key('onboarding-logout'),
                value: 'logout',
                child: Text(l10n.homeLogout),
              ),
            ],
          ),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            const ActiveJobBanner(),
            Expanded(
              child: IndexedStack(
                index: _index,
                children: [
                  HomeTab(vehicles: widget.vehicles),
                  RequestsTab(onGoOnline: () => setState(() => _index = 0)),
                  const OffersTab(),
                  const HistoryTab(),
                  const EarningsTab(),
                ],
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: NavigationBar(
        key: const Key('driver-nav'),
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: [
          NavigationDestination(
            icon: const Icon(Icons.home_outlined),
            selectedIcon: const Icon(Icons.home),
            label: l10n.jbTabHome,
          ),
          NavigationDestination(
            icon: Badge(
              isLabelVisible: requestCount > 0,
              label: Text('$requestCount'),
              child: const Icon(Icons.notifications_active_outlined),
            ),
            selectedIcon: const Icon(Icons.notifications_active),
            label: l10n.jbTabRequests,
          ),
          NavigationDestination(
            icon: const Icon(Icons.local_offer_outlined),
            selectedIcon: const Icon(Icons.local_offer),
            label: l10n.jbTabOffers,
          ),
          NavigationDestination(
            icon: const Icon(Icons.history),
            label: l10n.jbTabHistory,
          ),
          NavigationDestination(
            key: const Key('nav-earnings'),
            icon: const Icon(Icons.account_balance_wallet_outlined),
            selectedIcon: const Icon(Icons.account_balance_wallet),
            label: l10n.ernTab,
          ),
        ],
      ),
    );
  }
}
