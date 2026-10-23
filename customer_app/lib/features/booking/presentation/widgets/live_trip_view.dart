import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';
import 'package:share_plus/share_plus.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../map/presentation/widgets/booking_map.dart';
import '../../../payments/presentation/widgets/adjustments_section.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/logic/polyline.dart';
import '../providers/booking_providers.dart';
import 'booking_sections.dart';
import 'booking_texts.dart';
import 'delivery_cards.dart';
import '../../../safety/presentation/widgets/safety_sheet.dart';

/// Seconds after which a location fix is shown as stale.
const staleLocationAfter = Duration(seconds: 45);

/// Live trip: provider and vehicle, agreed fare, PIN, status timeline, map
/// with the smoothed provider marker, last-updated and stale warning, share,
/// safety and cancel. Buttons come from the server's `allowed_actions`.
class LiveTripView extends ConsumerStatefulWidget {
  const LiveTripView({super.key, required this.tracked});

  final TrackedBooking tracked;

  @override
  ConsumerState<LiveTripView> createState() => _LiveTripViewState();
}

class _LiveTripViewState extends ConsumerState<LiveTripView> {
  bool _sharing = false;
  bool _shared = false;

  Future<void> _share(Booking booking) async {
    final l10n = AppLocalizations.of(context);
    setState(() => _sharing = true);
    try {
      final link = await ref.read(bookingActionsProvider).startShare(booking.id);
      if (!mounted) return;
      setState(() => _shared = true);
      await SharePlus.instance.share(
        ShareParams(text: l10n.bkShareMessage(link.url)),
      );
    } on Object catch (e) {
      if (mounted) showBookingError(context, e);
    } finally {
      if (mounted) setState(() => _sharing = false);
    }
  }

  Future<void> _stopShare(Booking booking) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    try {
      await ref.read(bookingActionsProvider).stopShare(booking.id);
      if (!mounted) return;
      setState(() => _shared = false);
      messenger.showSnackBar(SnackBar(content: Text(l10n.bkShareStopped)));
    } on Object catch (e) {
      if (mounted) showBookingError(context, e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final booking = widget.tracked.booking;
    final fix = widget.tracked.fix;
    final language = Localizations.localeOf(context).languageCode;
    final formatter = ref.watch(moneyFormatterProvider);
    final trip = booking.trip;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        SizedBox(
          height: 240,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppRadius.md),
            child: BookingMap(
              pickup: booking.pickup.toLatLng(),
              destination: booking.dropoff?.toLatLng(),
              provider: trip.providerLocationVisible ? fix?.point : null,
              route: booking.polyline == null
                  ? const []
                  : decodePolyline(booking.polyline!),
              semanticLabel: l10n.bkLiveMapLabel,
            ),
          ),
        ),
        if (trip.providerLocationVisible)
          _LocationFreshness(fix: fix),
        const SizedBox(height: AppSpacing.sm),
        if (trip.supportFlagged)
          _Notice(
            key: const Key('support-flagged'),
            icon: Icons.support_agent,
            text: l10n.bkSupportFlagged,
          ),
        BookingSummaryCard(
          booking: booking,
          formatter: formatter,
          languageCode: language,
        ),
        const SizedBox(height: AppSpacing.md),
        if (booking.provider != null)
          _ProviderCard(booking: booking, formatter: formatter),
        if (booking.tripPin != null) ...[
          const SizedBox(height: AppSpacing.md),
          _PinCard(pin: booking.tripPin!),
        ] else if (trip.pinLocked) ...[
          const SizedBox(height: AppSpacing.md),
          _Notice(icon: Icons.lock_outline, text: l10n.bkPinLocked),
        ],
        if (booking.status == BookingStatus.providerArrived &&
            trip.freeWaitEndsAt != null) ...[
          const SizedBox(height: AppSpacing.md),
          Countdown(
            until: trip.freeWaitEndsAt!,
            builder: (context, left) => Text(
              left == Duration.zero
                  ? l10n.bkFreeWaitOver
                  : l10n.bkFreeWaitLeft(formatClock(left)),
              key: const Key('free-wait'),
              style: theme.textTheme.titleSmall,
            ),
          ),
        ],
        if (booking.scope.isEmpty == false) ...[
          const SizedBox(height: AppSpacing.md),
          BookingScopeCard(booking: booking),
        ],
        if (booking.load != null) ...[
          const SizedBox(height: AppSpacing.md),
          BookingLoadCard(booking: booking),
        ],
        if (booking.delivery != null) ...[
          const SizedBox(height: AppSpacing.md),
          DeliveryPanel(booking: booking),
        ],
        if (booking.isAssigned) ...[
          const SizedBox(height: AppSpacing.md),
          AdjustmentsSection(bookingId: booking.id, scoped: booking.scope.agreed != null, canRequest: booking.can(BookingAction.requestAdjustment)),
        ],
        const SizedBox(height: AppSpacing.md),
        _StatusTimeline(booking: booking),
        const SizedBox(height: AppSpacing.md),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            if (booking.can(BookingAction.share))
              OutlinedButton.icon(
                key: const Key('share-trip'),
                icon: _sharing
                    ? const SizedBox.square(
                        dimension: 16,
                        child: CircularProgressIndicator(strokeWidth: 2),
                      )
                    : const Icon(Icons.ios_share),
                label: Text(l10n.bkShareTrip),
                onPressed: _sharing ? null : () => _share(booking),
              ),
            if (_shared && booking.can(BookingAction.share))
              TextButton(
                key: const Key('stop-share'),
                onPressed: () => _stopShare(booking),
                child: Text(l10n.bkShareStop),
              ),
            OutlinedButton.icon(
              key: const Key('safety-button'),
              icon: const Icon(Icons.shield_outlined),
              label: Text(l10n.bkSafety),
              onPressed: () => showSafetySheet(context, bookingId: booking.id),
            ),
            if (booking.can(BookingAction.chat))
              FilledButton.tonalIcon(
                key: const Key('chat-button'),
                icon: const Icon(Icons.chat_bubble_outline),
                label: Text(l10n.supportOpenChat),
                onPressed: () => context.push(Routes.bookingChat(booking.id)),
              ),
            if (booking.can(BookingAction.report))
              OutlinedButton.icon(
                key: const Key('report-button'),
                icon: const Icon(Icons.flag_outlined),
                label: Text(l10n.bkReportIssue),
                onPressed: () => context.push(Routes.bookingReport(booking.id)),
              ),
            if (booking.can(BookingAction.cancel))
              OutlinedButton.icon(
                key: const Key('cancel-booking-button'),
                icon: Icon(Icons.close, color: theme.colorScheme.error),
                label: Text(
                  l10n.bkCancelTrip,
                  style: TextStyle(color: theme.colorScheme.error),
                ),
                onPressed: () => context.push(Routes.bookingCancel(booking.id)),
              ),
          ],
        ),
      ],
    );
  }
}

/// "Updated 5 s ago" and the stale warning once the last fix is old.
class _LocationFreshness extends StatelessWidget {
  const _LocationFreshness({required this.fix});

  final ProviderFix? fix;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return SecondTicker(
      builder: (context, now) {
        final f = fix;
        if (f == null) {
          return Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
            child: Text(
              l10n.bkLocationWaiting,
              key: const Key('location-waiting'),
              style: Theme.of(context).textTheme.bodySmall,
            ),
          );
        }
        final age = now.difference(f.recordedAt);
        final stale = age >= staleLocationAfter;
        return Padding(
          padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
          child: stale
              ? _Notice(
                  key: const Key('stale-location-banner'),
                  icon: Icons.signal_cellular_connected_no_internet_0_bar,
                  text: l10n.bkLocationStale(age.inMinutes < 1 ? 1 : age.inMinutes),
                )
              : Text(
                  l10n.bkLocationUpdated(age.inSeconds < 0 ? 0 : age.inSeconds),
                  key: const Key('location-updated'),
                  style: Theme.of(context).textTheme.bodySmall,
                ),
        );
      },
    );
  }
}

class _Notice extends StatelessWidget {
  const _Notice({super.key, required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Padding(
      padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
      child: DecoratedBox(
        decoration: BoxDecoration(
          color: scheme.tertiaryContainer,
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
          child: Row(
            children: [
              Icon(icon, color: scheme.onTertiaryContainer),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(
                  text,
                  style: TextStyle(color: scheme.onTertiaryContainer),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _ProviderCard extends StatelessWidget {
  const _ProviderCard({required this.booking, required this.formatter});

  final Booking booking;
  final MoneyFormatter formatter;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final provider = booking.provider!;
    final vehicle = booking.vehicle;
    final theme = Theme.of(context);
    final vehicleLine = [
      if (vehicle != null && vehicle.title.isNotEmpty) vehicle.title,
      if (vehicle?.color != null) vehicle!.color!,
    ].join(' · ');
    return Card(
      key: const Key('provider-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Row(
          children: [
            CircleAvatar(
              radius: 26,
              backgroundImage: provider.photoUrl == null
                  ? null
                  : NetworkImage(provider.photoUrl!),
              child: provider.photoUrl == null
                  ? const Icon(Icons.person_outline)
                  : null,
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    provider.displayName ?? l10n.bkOfferProviderFallback,
                    style: theme.textTheme.titleMedium,
                  ),
                  if (provider.ratingCount > 0)
                    Text(
                      l10n.bkOfferRating(
                        formatter.number(provider.ratingAverage, fractionDigits: 1),
                        provider.ratingCount,
                      ),
                      style: theme.textTheme.bodySmall,
                    ),
                  if (vehicleLine.isNotEmpty) Text(vehicleLine),
                  if (vehicle?.category.isEmpty == false)
                    Text(
                      vehicle!.category.resolve(language),
                      style: theme.textTheme.bodySmall,
                    ),
                ],
              ),
            ),
            if (vehicle?.plate != null)
              DecoratedBox(
                decoration: BoxDecoration(
                  border: Border.all(color: theme.colorScheme.outline),
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                child: Padding(
                  padding: const EdgeInsetsDirectional.symmetric(
                    horizontal: AppSpacing.sm,
                    vertical: AppSpacing.xs,
                  ),
                  child: Text(
                    vehicle!.plate!,
                    key: const Key('vehicle-plate'),
                    style: theme.textTheme.titleSmall,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _PinCard extends StatelessWidget {
  const _PinCard({required this.pin});

  final String pin;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final scheme = Theme.of(context).colorScheme;
    return Card(
      key: const Key('pin-card'),
      margin: EdgeInsets.zero,
      color: scheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          children: [
            Text(l10n.bkPinTitle, style: Theme.of(context).textTheme.titleSmall),
            const SizedBox(height: AppSpacing.xs),
            Directionality(
              textDirection: TextDirection.ltr,
              child: Text(
                pin,
                key: const Key('trip-pin'),
                style: Theme.of(context).textTheme.displayMedium?.copyWith(
                  letterSpacing: 8,
                  fontWeight: FontWeight.w700,
                ),
              ),
            ),
            Text(
              l10n.bkPinHint,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

class _StatusTimeline extends StatelessWidget {
  const _StatusTimeline({required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final trip = booking.trip;
    final steps = [
      (l10n.bkStepSelected, trip.selectedAt),
      (l10n.bkStepHeading, trip.headingAt),
      (l10n.bkStepArrived, trip.arrivedAt),
      (l10n.bkStepStarted, trip.startedAt),
    ];
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.bkTimelineTitle, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        Timeline(
          steps: [
            for (final (title, at) in steps)
              TimelineStep(
                title: title,
                subtitle: at == null ? null : formatDateTime(context, at),
                done: at != null,
              ),
          ],
        ),
      ],
    );
  }
}
