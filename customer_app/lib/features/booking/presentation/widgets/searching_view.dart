import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../map/presentation/widgets/booking_map.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/logic/polyline.dart';
import '../providers/booking_providers.dart';
import 'booking_sections.dart';
import 'booking_texts.dart';
import 'edit_fare_sheet.dart';
import 'offer_tile.dart';

/// Finding offers: search countdown, offer cards (compare, accept, reject),
/// edit fare and the no-offers state.
class SearchingView extends ConsumerStatefulWidget {
  const SearchingView({super.key, required this.booking});

  final Booking booking;

  @override
  ConsumerState<SearchingView> createState() => _SearchingViewState();
}

class _SearchingViewState extends ConsumerState<SearchingView> {
  String? _busyOffer;

  Future<void> _accept(Offer offer) async {
    final l10n = AppLocalizations.of(context);
    final formatter = ref.read(moneyFormatterProvider);
    final confirmed = await showConfirmSheet(
      context,
      title: l10n.bkAcceptTitle,
      message: offer.amount == null
          ? l10n.bkAcceptBody
          : l10n.bkAcceptBodyPrice(
              formatter.format(offer.amount!),
              offer.provider?.displayName ?? l10n.bkOfferProviderFallback,
            ),
      confirmLabel: l10n.bkOfferAccept,
      cancelLabel: l10n.actionCancel,
    );
    if (!confirmed || !mounted) return;
    setState(() => _busyOffer = offer.id);
    try {
      await ref
          .read(bookingActionsProvider)
          .acceptOffer(widget.booking.id, offer.id);
    } on Object catch (e) {
      if (mounted) showBookingError(context, e);
    } finally {
      if (mounted) setState(() => _busyOffer = null);
    }
  }

  Future<void> _reject(Offer offer) async {
    setState(() => _busyOffer = offer.id);
    try {
      await ref
          .read(bookingActionsProvider)
          .rejectOffer(widget.booking.id, offer.id);
    } on Object catch (e) {
      if (mounted) showBookingError(context, e);
    } finally {
      if (mounted) setState(() => _busyOffer = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final booking = widget.booking;
    final language = Localizations.localeOf(context).languageCode;
    final formatter = ref.watch(moneyFormatterProvider);
    final offers = ref.watch(offersProvider(booking.id));
    final sort = ref.watch(offerSortProvider);

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        SizedBox(
          height: 160,
          child: ClipRRect(
            borderRadius: BorderRadius.circular(AppRadius.md),
            child: BookingMap(
              pickup: booking.pickup.toLatLng(),
              destination: booking.dropoff?.toLatLng(),
              route: booking.polyline == null
                  ? const []
                  : decodePolyline(booking.polyline!),
              semanticLabel: l10n.bkFormRouteLabel,
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        BookingSummaryCard(
          booking: booking,
          formatter: formatter,
          languageCode: language,
        ),
        const SizedBox(height: AppSpacing.md),
        _SearchBanner(booking: booking),
        const SizedBox(height: AppSpacing.sm),
        Wrap(
          spacing: AppSpacing.sm,
          children: [
            if (booking.can(BookingAction.updateRequest))
              OutlinedButton.icon(
                key: const Key('edit-fare-button'),
                icon: const Icon(Icons.edit_outlined),
                label: Text(l10n.bkEditFare),
                onPressed: () => showEditFareSheet(context, booking),
              ),
            if (booking.can(BookingAction.cancel))
              OutlinedButton.icon(
                key: const Key('cancel-booking-button'),
                icon: const Icon(Icons.close),
                label: Text(l10n.bkCancelBooking),
                onPressed: () => context.push(Routes.bookingCancel(booking.id)),
              ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.bkOffersTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        AsyncStateView<List<Offer>>(
          value: offers,
          onRetry: () => ref.invalidate(offersProvider(booking.id)),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, list) {
            final active = [
              for (final o in list)
                if (o.status == OfferStatus.submitted ||
                    o.status == OfferStatus.counterOffered)
                  o,
            ];
            if (active.isEmpty) return _NoOffers(booking: booking);
            final sorted = sortOffers(active, sort);
            return Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                if (active.length > 1)
                  Wrap(
                    spacing: AppSpacing.sm,
                    children: [
                      for (final s in OfferSort.values)
                        ChoiceChip(
                          key: Key('offer-sort-${s.name}'),
                          label: Text(switch (s) {
                            OfferSort.price => l10n.bkSortPrice,
                            OfferSort.eta => l10n.bkSortEta,
                            OfferSort.rating => l10n.bkSortRating,
                          }),
                          selected: sort == s,
                          onSelected: (_) =>
                              ref.read(offerSortProvider.notifier).select(s),
                        ),
                    ],
                  ),
                for (final offer in sorted)
                  OfferTile(
                    offer: offer,
                    formatter: formatter,
                    languageCode: language,
                    busy: _busyOffer == offer.id,
                    customerFare: booking.proposedFare,
                    onAccept: () => _accept(offer),
                    onReject: () => _reject(offer),
                  ),
              ],
            );
          },
        ),
      ],
    );
  }
}

class _SearchBanner extends StatelessWidget {
  const _SearchBanner({required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final until = booking.searchExpiresAt;
    return Card(
      key: const Key('search-banner'),
      margin: EdgeInsets.zero,
      color: Theme.of(context).colorScheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Row(
          children: [
            const SizedBox.square(
              dimension: 20,
              child: CircularProgressIndicator(strokeWidth: 2.5),
            ),
            const SizedBox(width: AppSpacing.md),
            Expanded(
              child: until == null
                  ? Text(l10n.bkSearching)
                  : Countdown(
                      until: until,
                      builder: (context, left) => Text(
                        left == Duration.zero
                            ? l10n.bkSearchEnding
                            : l10n.bkSearchingLeft(formatClock(left)),
                        key: const Key('search-countdown'),
                      ),
                    ),
            ),
          ],
        ),
      ),
    );
  }
}

/// No active offers: waiting while the window is open, otherwise the options
/// (edit price, cancel) without promising that a higher fare gets offers.
class _NoOffers extends ConsumerWidget {
  const _NoOffers({required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final until = booking.searchExpiresAt;
    return Countdown(
      until: until ?? DateTime.now().add(const Duration(days: 1)),
      builder: (context, left) {
        final ended = until != null && left == Duration.zero;
        return Card(
          key: const Key('no-offers'),
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  ended ? l10n.bkNoOffersTitle : l10n.bkWaitingOffersTitle,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(ended ? l10n.bkNoOffersBody : l10n.bkWaitingOffersBody),
                if (ended) ...[
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    l10n.bkNoOffersNoGuarantee,
                    style: Theme.of(context).textTheme.bodySmall,
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }
}
