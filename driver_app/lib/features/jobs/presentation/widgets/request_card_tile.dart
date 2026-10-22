import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'countdown.dart';
import 'counter_offer_sheet.dart';
import 'job_texts.dart';

/// One masked request: category, area names, distances, the customer's fare,
/// a summary of the load, the expiry timer and the three actions (accept the
/// fare, counter-offer, skip). Never an address, name or phone.
class RequestCardTile extends ConsumerStatefulWidget {
  const RequestCardTile({super.key, required this.card, this.offer});

  final RequestCard card;

  /// The driver's open offer on this request, when there is one.
  final MyOffer? offer;

  @override
  ConsumerState<RequestCardTile> createState() => _RequestCardTileState();
}

class _RequestCardTileState extends ConsumerState<RequestCardTile> {
  bool _busy = false;

  MyOffer? get _offer => widget.offer ?? widget.card.myOffer;

  Future<void> _run(Future<void> Function() action, {String? success}) async {
    if (_busy) return;
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    try {
      await action();
      if (success != null) {
        messenger
          ..hideCurrentSnackBar()
          ..showSnackBar(SnackBar(content: Text(success)));
      }
    } on Object catch (e) {
      messenger
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(jobErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _counter() async {
    final offer = await showCounterOfferSheet(
      context,
      card: widget.card,
      current: _offer,
    );
    if (offer != null && mounted) {
      final l10n = AppLocalizations.of(context);
      ScaffoldMessenger.of(context)
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(l10n.jbOfferSent)));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    final card = widget.card;
    final offer = _offer;
    final actions = ref.read(jobActionsProvider);
    final expiry = card.expiresAt;

    Widget body(bool expired) {
      final disabled = _busy || expired;
      return Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  card.service.resolve(language),
                  style: theme.textTheme.titleMedium,
                ),
              ),
              if (expiry != null)
                Countdown(
                  until: expiry,
                  builder: (context, left, over) => StatusChip(
                    key: const Key('request-expiry'),
                    label: over
                        ? l10n.jbRequestExpired
                        : l10n.jbExpiresIn(formatClock(context, left)),
                    tone: over
                        ? ChipTone.danger
                        : (left.inSeconds < 15
                              ? ChipTone.warning
                              : ChipTone.info),
                  ),
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            formatter.format(card.proposedFare),
            key: const Key('request-fare'),
            style: theme.textTheme.displaySmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          Text(l10n.jbCustomerFare, style: theme.textTheme.bodySmall),
          const SizedBox(height: AppSpacing.sm),
          _InfoRow(
            icon: Icons.my_location,
            text: l10n.jbPickupArea(card.pickup.name ?? l10n.jbAreaUnknown),
            trailing: card.distanceToPickupMeters == null
                ? null
                : l10n.jbAwayKm(formatKm(context, card.distanceToPickupMeters!)),
          ),
          if (card.dropoff != null)
            _InfoRow(
              icon: Icons.flag_outlined,
              text: l10n.jbDropoffArea(
                '${card.dropoff!.lat.toStringAsFixed(2)}, '
                '${card.dropoff!.lng.toStringAsFixed(2)}',
              ),
            ),
          if (card.tripDistanceMeters != null ||
              card.tripDurationSeconds != null)
            _InfoRow(
              icon: Icons.route_outlined,
              text: [
                if (card.tripDistanceMeters != null)
                  formatKm(context, card.tripDistanceMeters!),
                if (card.tripDurationSeconds != null)
                  formatMinutes(context, card.tripDurationSeconds!),
              ].join(' · '),
            ),
          const SizedBox(height: AppSpacing.xs),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.xs,
            children: [
              StatusChip(
                label: paymentMethodText(l10n, card.paymentMethod),
                tone: ChipTone.neutral,
              ),
              if (card.passengers != null)
                StatusChip(
                  label: l10n.jbPassengers(card.passengers!),
                  tone: ChipTone.neutral,
                ),
              if (card.stops > 0)
                StatusChip(
                  label: l10n.jbStops(card.stops),
                  tone: ChipTone.neutral,
                ),
              if ((card.luggageCount ?? 0) > 0)
                StatusChip(
                  label: l10n.jbLuggage(card.luggageCount!),
                  tone: ChipTone.neutral,
                ),
              if (card.cargoWeightKg != null)
                StatusChip(
                  label: l10n.jbWeightKg('${card.cargoWeightKg}'),
                  tone: ChipTone.neutral,
                ),
              if (card.scheduledAt != null)
                StatusChip(
                  label: l10n.jbScheduledAt(
                    formatDateTime(context, card.scheduledAt!),
                  ),
                  tone: ChipTone.warning,
                ),
              if (card.restrictedPickup)
                StatusChip(label: l10n.jbRestrictedPickup, tone: ChipTone.warning),
              if (card.load?.volumeM3 != null)
                StatusChip(
                  key: const Key('request-volume'),
                  label: l10n.jbVolumeM3('${card.load!.volumeM3}'),
                  tone: ChipTone.neutral,
                ),
            ],
          ),
          if (card.load?.windowStart != null)
            _InfoRow(
              icon: Icons.event_available_outlined,
              text: l10n.jbPickupWindow(
                formatDateTime(context, card.load!.windowStart!),
                card.load!.windowEnd == null ? '' : formatDateTime(context, card.load!.windowEnd!),
              ),
            ),
          if (card.requestedScopeSummary.isNotEmpty)
            ExpansionTile(
              key: const Key('request-scope'),
              tilePadding: EdgeInsets.zero,
              title: Text(l10n.jbRequestedScope),
              children: [
                ScopeSummaryList(
                  lines: card.requestedScopeSummary,
                  scope: card.requestedScope,
                  strings: scopeStrings(l10n),
                  showTitle: false,
                  dense: true,
                ),
              ],
            ),
          const SizedBox(height: AppSpacing.md),
          if (offer != null && offer.isPending) ...[
            Container(
              key: const Key('request-my-offer'),
              padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: theme.colorScheme.secondaryContainer,
                borderRadius: BorderRadius.circular(AppRadius.sm),
              ),
              child: Text(
                offer.amount == null
                    ? l10n.jbOfferSent
                    : l10n.jbMyOfferAmount(formatter.format(offer.amount!)),
                style: theme.textTheme.titleMedium,
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                if (card.counter.allowed)
                  Expanded(
                    child: AppButton(
                      key: const Key('request-revise'),
                      label: l10n.jbReviseOffer,
                      secondary: true,
                      onPressed: disabled ? null : _counter,
                    ),
                  ),
                if (card.counter.allowed) const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: AppButton(
                    key: const Key('request-withdraw'),
                    label: l10n.jbWithdraw,
                    secondary: true,
                    loading: _busy,
                    onPressed: expired
                        ? null
                        : () => _run(
                            () => actions.withdraw(offer.id),
                            success: l10n.jbWithdrawn,
                          ),
                  ),
                ),
              ],
            ),
          ] else ...[
            if (card.scopeRequired)
              Padding(
                padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.xs),
                child: Text(
                  l10n.jbAcceptScopeNote,
                  key: const Key('request-accept-note'),
                  style: theme.textTheme.bodySmall,
                ),
              ),
            AppButton(
              key: const Key('request-accept'),
              label: l10n.jbAcceptFare,
              icon: Icons.check_circle_outline,
              loading: _busy,
              onPressed: disabled
                  ? null
                  : () => _run(
                      () => actions.acceptCustomerFare(card.id),
                      success: l10n.jbOfferSent,
                    ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              children: [
                if (card.counter.allowed)
                  Expanded(
                    child: AppButton(
                      key: const Key('request-counter'),
                      label: l10n.jbCounterOffer,
                      secondary: true,
                      onPressed: disabled ? null : _counter,
                    ),
                  ),
                if (card.counter.allowed) const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: AppButton(
                    key: const Key('request-skip'),
                    label: l10n.jbSkip,
                    secondary: true,
                    onPressed: disabled
                        ? null
                        : () => _run(() => actions.skip(card.id)),
                  ),
                ),
              ],
            ),
          ],
        ],
      );
    }

    return Card(
      key: Key('request-${card.id}'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: expiry == null
            ? body(false)
            : Countdown(
                until: expiry,
                builder: (context, left, expired) => body(expired),
              ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.text, this.trailing});

  final IconData icon;
  final String text;
  final String? trailing;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsetsDirectional.symmetric(vertical: 2),
    child: Row(
      children: [
        Icon(icon, size: 20),
        const SizedBox(width: AppSpacing.sm),
        Expanded(child: Text(text)),
        if (trailing != null)
          Text(trailing!, style: Theme.of(context).textTheme.labelLarge),
      ],
    ),
  );
}
