import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/booking_entities.dart';
import 'booking_texts.dart';
import 'delivery_texts.dart' show scopeStrings;

/// One provider offer (SRS 6.5): price, kind (accepts your fare or counter),
/// provider and vehicle, ETA, note and a live expiry countdown.
class OfferTile extends StatelessWidget {
  const OfferTile({
    super.key,
    required this.offer,
    required this.formatter,
    required this.languageCode,
    required this.onAccept,
    required this.onReject,
    this.busy = false,
    this.customerFare,
  });

  final Offer offer;
  final MoneyFormatter formatter;
  final String languageCode;
  final VoidCallback onAccept;
  final VoidCallback onReject;
  final bool busy;

  /// The fare the customer proposed, to show the difference for counters.
  final Money? customerFare;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final provider = offer.provider;
    final vehicle = offer.vehicle;
    final eta = offer.etaSeconds;
    final facts = <String>[
      if (eta != null) l10n.bkOfferEta((eta / 60).ceil()),
      if (offer.distanceMeters != null)
        l10n.bkOfferDistance(
          formatter.number(offer.distanceMeters! / 1000, fractionDigits: 1),
        ),
      if (provider != null && provider.completedJobs > 0)
        l10n.bkOfferTrips(provider.completedJobs),
    ];
    final vehicleLine = [
      if (vehicle != null && vehicle.title.isNotEmpty) vehicle.title,
      if (vehicle?.color != null) vehicle!.color!,
      if (vehicle?.plate != null) vehicle!.plate!,
    ].join(' · ');

    return Countdown(
      until: offer.expiresAt,
      builder: (context, left) {
        final expired = left == Duration.zero;
        return Opacity(
          opacity: expired ? 0.6 : 1,
          child: OfferCard(
            leading: CircleAvatar(
              radius: 24,
              backgroundImage: provider?.photoUrl == null
                  ? null
                  : NetworkImage(provider!.photoUrl!),
              child: provider?.photoUrl == null
                  ? const Icon(Icons.person_outline)
                  : null,
            ),
            title: provider?.displayName ?? l10n.bkOfferProviderFallback,
            subtitle: vehicleLine.isEmpty ? null : vehicleLine,
            price: Column(
              crossAxisAlignment: CrossAxisAlignment.end,
              children: [
                if (offer.amount != null)
                  MoneyText(money: offer.amount!, formatter: formatter),
                StatusChip(
                  label: offer.isCounterOffer
                      ? l10n.bkOfferCounter
                      : l10n.bkOfferAcceptsFare,
                  tone: offer.isCounterOffer
                      ? ChipTone.warning
                      : ChipTone.success,
                ),
              ],
            ),
            actions: [
              AppButton(
                key: Key('offer-accept-${offer.id}'),
                label: l10n.bkOfferAccept,
                loading: busy,
                onPressed: expired ? null : onAccept,
              ),
              AppButton(
                key: Key('offer-reject-${offer.id}'),
                label: l10n.bkOfferReject,
                secondary: true,
                onPressed: busy || expired ? null : onReject,
              ),
            ],
          ),
        ).withDetails(context, [
          if (provider != null && provider.ratingCount > 0)
            Row(
              children: [
                const Icon(
                  Icons.star_rounded,
                  size: 18,
                  color: AppColors.accent,
                ),
                const SizedBox(width: AppSpacing.xs),
                Text(
                  provider.ratingProvisional
                      ? l10n.bkOfferRatingNew(
                          formatter.number(
                            provider.ratingAverage,
                            fractionDigits: 1,
                          ),
                          provider.ratingCount,
                        )
                      : l10n.bkOfferRating(
                          formatter.number(
                            provider.ratingAverage,
                            fractionDigits: 1,
                          ),
                          provider.ratingCount,
                        ),
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          if (facts.isNotEmpty)
            Text(facts.join(' · '), style: theme.textTheme.bodySmall),
          if (offer.scopeSummary.isNotEmpty)
            Container(
              key: Key('offer-scope-${offer.id}'),
              padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerLow,
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: ScopeSummaryList(
                lines: offer.scopeSummary,
                scope: offer.scope,
                strings: scopeStrings(l10n),
                dense: true,
              ),
            ),
          if (offer.note != null && offer.note!.isNotEmpty)
            Text(offer.note!, style: theme.textTheme.bodyMedium),
          Text(
            expired
                ? l10n.bkOfferExpired
                : l10n.bkOfferExpiresIn(formatClock(left)),
            key: Key('offer-expiry-${offer.id}'),
            style: theme.textTheme.labelMedium?.copyWith(
              color: expired || left.inSeconds < 15
                  ? theme.colorScheme.error
                  : null,
            ),
          ),
        ]);
      },
    );
  }
}

extension on Widget {
  /// The card plus the detail lines under it, inside one visual block.
  Widget withDetails(BuildContext context, List<Widget> details) => Column(
    crossAxisAlignment: CrossAxisAlignment.stretch,
    children: [
      this,
      Padding(
        padding: const EdgeInsetsDirectional.only(
          start: AppSpacing.md,
          end: AppSpacing.md,
          bottom: AppSpacing.sm,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            for (final d in details) ...[
              d,
              const SizedBox(height: AppSpacing.xs),
            ],
          ],
        ),
      ),
    ],
  );
}
