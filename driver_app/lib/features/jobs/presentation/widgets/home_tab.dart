import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../earnings/presentation/providers/earnings_providers.dart';
import '../../../earnings/presentation/widgets/cash_debt_banner.dart';
import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../availability/presentation/widgets/availability_card.dart';
import '../providers/job_providers.dart';

/// The driver home: the online switch with its eligibility reasons, vehicle
/// selector, tracking line and connection indicator, the request alert
/// preferences and the way to the application / documents. Earnings are not
/// shown: no server feature flag enables them yet (P3.8).
class HomeTab extends ConsumerWidget {
  const HomeTab({super.key, required this.vehicles});

  final List<ProviderVehicleInfo> vehicles;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final prefs = ref.watch(requestAlertPrefsProvider);
    final notifier = ref.read(requestAlertPrefsProvider.notifier);
    return ListView(
      key: const Key('home-tab'),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        if (ref.watch(walletPositionProvider).value case final position?) ...[
          CashDebtBanner(position: position),
          if (position.hasCashDebt) const SizedBox(height: AppSpacing.md),
        ],
        AvailabilityCard(vehicles: vehicles),
        const SizedBox(height: AppSpacing.md),
        Card(
          child: Column(
            children: [
              SwitchListTile.adaptive(
                key: const Key('alert-sound'),
                value: prefs.sound,
                onChanged: notifier.setSound,
                secondary: const Icon(Icons.volume_up_outlined),
                title: Text(l10n.jbAlertSound),
              ),
              SwitchListTile.adaptive(
                key: const Key('alert-vibration'),
                value: prefs.vibration,
                onChanged: notifier.setVibration,
                secondary: const Icon(Icons.vibration),
                title: Text(l10n.jbAlertVibration),
              ),
            ],
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Card(
          child: ListTile(
            key: const Key('home-application'),
            minTileHeight: 64,
            leading: const Icon(Icons.assignment_outlined),
            title: Text(l10n.jbApplicationEntry),
            subtitle: Text(l10n.jbApplicationEntryBody),
            trailing: const Icon(Icons.chevron_right),
            onTap: () => context.push(Routes.applicationStatus),
          ),
        ),
      ],
    );
  }
}
