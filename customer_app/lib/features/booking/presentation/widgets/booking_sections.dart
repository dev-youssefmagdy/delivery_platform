import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../catalog/presentation/providers/catalog_providers.dart';
import '../../../map/domain/place_selection.dart';
import '../../../payments/presentation/screens/receipt_screen.dart' show receiptPaymentStatusText;
import '../../../map/presentation/providers/map_providers.dart';
import '../../domain/entities/booking_entities.dart';
import '../providers/booking_providers.dart';
import 'booking_texts.dart';

/// "12 Oct, 7:30 PM" in the active locale, without depending on intl's
/// per-locale date data being loaded.
String formatDateTime(BuildContext context, DateTime? value) {
  if (value == null) return '';
  final local = value.toLocal();
  final m = MaterialLocalizations.of(context);
  return '${m.formatMediumDate(local)} ${m.formatTimeOfDay(TimeOfDay.fromDateTime(local))}';
}

/// Route, service and fare of a booking.
class BookingSummaryCard extends StatelessWidget {
  const BookingSummaryCard({
    super.key,
    required this.booking,
    required this.formatter,
    required this.languageCode,
  });

  final Booking booking;
  final MoneyFormatter formatter;
  final String languageCode;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Card(
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    booking.serviceName.resolve(languageCode),
                    style: theme.textTheme.titleMedium,
                  ),
                ),
                StatusChip(
                  key: const Key('booking-status-chip'),
                  label: bookingStatusText(
                    l10n,
                    booking.status,
                    fallback: booking.statusLabel,
                  ),
                  tone: bookingStatusTone(booking.status),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            _Line(
              icon: Icons.trip_origin,
              text: booking.pickup.label ?? l10n.mapPinnedLocation,
            ),
            if (booking.dropoff != null)
              _Line(
                icon: Icons.location_on,
                color: theme.colorScheme.error,
                text: booking.dropoff!.label ?? l10n.mapPinnedLocation,
              ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                Text(
                  booking.agreedFare == null
                      ? l10n.bkYourFare
                      : l10n.bkAgreedFare,
                  style: theme.textTheme.bodyMedium,
                ),
                const Spacer(),
                MoneyText(
                  money: booking.agreedFare ?? booking.proposedFare,
                  formatter: formatter,
                ),
              ],
            ),
            Text(
              l10n.bkSummaryPayment(l10n.bkPaymentCash),
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

class _Line extends StatelessWidget {
  const _Line({required this.icon, required this.text, this.color});

  final IconData icon;
  final String text;
  final Color? color;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
    child: Row(
      children: [
        Icon(icon, size: 18, color: color),
        const SizedBox(width: AppSpacing.sm),
        Expanded(child: Text(text)),
      ],
    ),
  );
}

/// Status history from the server timeline (notes are skipped).
class BookingTimelineSection extends StatelessWidget {
  const BookingTimelineSection({super.key, required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final entries = [
      for (final e in booking.timeline)
        if (!e.isNote) e,
    ];
    if (entries.isEmpty) return const SizedBox.shrink();
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(l10n.bkTimelineTitle, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        Timeline(
          steps: [
            for (final e in entries)
              TimelineStep(
                title: bookingStatusText(l10n, e.status),
                subtitle: formatDateTime(context, e.at),
                done: true,
              ),
          ],
        ),
      ],
    );
  }
}

class ReceiptSection extends StatelessWidget {
  const ReceiptSection({
    super.key,
    required this.receipt,
    required this.formatter,
    this.onOpenFull,
  });

  final Receipt receipt;
  final MoneyFormatter formatter;

  /// Opens the full receipt with every line of the breakdown.
  final VoidCallback? onOpenFull;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    Widget row(String label, Widget value) => Padding(
      padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
      child: Row(children: [Expanded(child: Text(label)), value]),
    );
    return Card(
      key: const Key('receipt-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.bkReceiptTitle, style: Theme.of(context).textTheme.titleMedium),
            row(l10n.bkReceiptCode, Text(receipt.bookingCode)),
            if (receipt.agreedFare != null)
              row(
                l10n.bkAgreedFare,
                MoneyText(
                  money: receipt.agreedFare!,
                  formatter: formatter,
                  style: Theme.of(context).textTheme.bodyLarge,
                ),
              ),
            if (receipt.finalFare != null)
              row(
                l10n.bkReceiptTotal,
                MoneyText(money: receipt.finalFare!, formatter: formatter),
              ),
            if (receipt.couponApplied && receipt.discount != null && !receipt.discount!.isZero)
              row(
                l10n.rcpDiscount,
                IsolatedText('-${formatter.format(receipt.discount!)}', key: const Key('receipt-discount')),
              ),
            if (receipt.customerPayable != null)
              row(
                l10n.rcpTotal,
                MoneyText(money: receipt.customerPayable!, formatter: formatter),
              ),
            row(l10n.bkReceiptPayment, Text(_method(l10n, receipt.paymentMethod))),
            row(l10n.bkReceiptPaymentStatus, Text(_payment(l10n, receipt.paymentStatus))),
            if (receipt.durationSeconds != null)
              row(
                l10n.bkReceiptDuration,
                Text(l10n.bkMinutes((receipt.durationSeconds! / 60).ceil())),
              ),
            if (receipt.plannedDistanceMeters != null)
              row(
                l10n.bkReceiptDistance,
                Text(
                  l10n.bkKilometres(
                    formatter.number(receipt.plannedDistanceMeters! / 1000, fractionDigits: 1),
                  ),
                ),
              ),
            if (onOpenFull != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: OutlinedButton.icon(
                  key: const Key('receipt-open-full'),
                  icon: const Icon(Icons.receipt_long_outlined),
                  label: Text(l10n.rcpViewFull),
                  onPressed: onOpenFull,
                ),
              ),
          ],
        ),
      ),
    );
  }

  String _method(AppLocalizations l10n, PaymentMethodType t) =>
      switch (t) {
        PaymentMethodType.cash => l10n.bkPaymentCash,
        PaymentMethodType.card => l10n.pmCard,
        PaymentMethodType.mobileWallet => l10n.pmWallet,
        _ => l10n.bkPaymentOther,
      };

  String _payment(AppLocalizations l10n, PaymentStatus s) => receiptPaymentStatusText(l10n, s);
}

/// Shows a failure of a user action as a snackbar (ARB text).
void showBookingError(BuildContext context, Object error) {
  final l10n = AppLocalizations.of(context);
  ScaffoldMessenger.of(context)
    ..hideCurrentSnackBar()
    ..showSnackBar(SnackBar(content: Text(bookingErrorText(l10n, error))));
}

/// Loads a finished/ended booking into the form for another request (rebook,
/// or edit the price and try again).
void rebookFrom(BuildContext context, WidgetRef ref, Booking booking) {
  PlaceSelection select(PlaceInfo p) => PlaceSelection(
    point: LatLng(p.lat, p.lng),
    title: p.title,
    address: p.line,
  );
  final endpoints = ref.read(tripEndpointsProvider.notifier);
  endpoints.setPickup(select(booking.pickup));
  if (booking.dropoff != null) endpoints.setDestination(select(booking.dropoff!));
  ref.read(selectedCategoryIdProvider.notifier).select(booking.serviceId);
  ref.read(bookingDraftProvider.notifier)
    ..reset()
    ..setFare(booking.proposedFare.minor);
  context.push(Routes.bookingNew);
}

String ratingTone(AppLocalizations l10n, int stars) => switch (stars) {
  1 => l10n.bkRating1,
  2 => l10n.bkRating2,
  3 => l10n.bkRating3,
  4 => l10n.bkRating4,
  _ => l10n.bkRating5,
};
