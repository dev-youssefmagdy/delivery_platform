import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../booking/domain/entities/booking_entities.dart';
import '../../../booking/presentation/providers/booking_providers.dart';
import '../../../booking/presentation/widgets/booking_texts.dart';
import '../../../catalog/presentation/providers/catalog_providers.dart';
import '../../../catalog/presentation/widgets/service_picker.dart';
import '../../../map/presentation/providers/map_providers.dart';
import '../../../map/presentation/widgets/trip_location_panel.dart';

/// Home: map with pickup and destination, the service catalog, the request
/// button, and the persistent banner of an active booking.
class HomeTab extends ConsumerWidget {
  const HomeTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final name = ref.watch(meProvider).value?.name;
    final endpoints = ref.watch(tripEndpointsProvider);
    final category = ref.watch(selectedCategoryProvider);
    final active = ref.watch(activeBookingProvider).value;
    final ready =
        endpoints.pickup != null &&
        endpoints.destination != null &&
        category != null;
    final hint = active != null
        ? l10n.homeRequestHintActive
        : endpoints.pickup == null
        ? l10n.homeRequestHintPickup
        : endpoints.destination == null
        ? l10n.homeRequestHintDestination
        : category == null
        ? l10n.homeRequestHintService
        : null;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.homeTitle)),
      body: RefreshIndicator(
        onRefresh: () async {
          ref.invalidate(catalogProvider);
          await ref.read(activeBookingProvider.notifier).refresh();
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          children: [
            if (active != null) ActiveBookingBanner(summary: active),
            if (name != null) ListTile(title: Text(l10n.homeGreeting(name))),
            const TripLocationPanel(),
            const ServicePicker(),
            Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  if (hint != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        bottom: AppSpacing.sm,
                      ),
                      child: Text(
                        hint,
                        key: const Key('home-request-hint'),
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                    ),
                  AppButton(
                    key: const Key('home-request'),
                    label: l10n.homeRequest,
                    onPressed: ready && active == null
                        ? () => context.push(Routes.bookingNew)
                        : null,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

/// Persistent banner of the booking in flight; opens it.
class ActiveBookingBanner extends ConsumerWidget {
  const ActiveBookingBanner({super.key, required this.summary});

  final BookingSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final scheme = Theme.of(context).colorScheme;
    return Material(
      key: const Key('active-booking-banner'),
      color: scheme.primaryContainer,
      child: InkWell(
        onTap: () => context.push(Routes.booking(summary.id)),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Row(
            children: [
              Icon(Icons.directions_car, color: scheme.onPrimaryContainer),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.homeActiveBooking,
                      style: Theme.of(context).textTheme.titleSmall,
                    ),
                    Text(
                      bookingStatusText(
                        l10n,
                        summary.status,
                        fallback: summary.statusLabel,
                      ),
                    ),
                  ],
                ),
              ),
              Text(l10n.homeActiveBookingOpen),
              const Icon(Icons.chevron_right),
            ],
          ),
        ),
      ),
    );
  }
}
