import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'countdown.dart';
import 'job_texts.dart';

/// Offers sorted for display: open ones first (soonest expiry), then the
/// closed ones, newest first.
List<MyOffer> sortMyOffers(List<MyOffer> offers) {
  final pending = [
    for (final o in offers)
      if (o.isPending) o,
  ]..sort((a, b) => a.expiresAt.compareTo(b.expiresAt));
  final closed = [
    for (final o in offers)
      if (!o.isPending) o,
  ]..sort((a, b) => (b.closedAt ?? b.expiresAt).compareTo(a.closedAt ?? a.expiresAt));
  return [...pending, ...closed];
}

/// "My offers": pending, selected, rejected, expired and invalidated offers
/// with the reason, and withdraw for the open ones.
class OffersTab extends ConsumerWidget {
  const OffersTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final offers = ref.watch(myOffersProvider);
    return AsyncStateView<List<MyOffer>>(
      value: offers,
      onRetry: () => ref.read(myOffersProvider.notifier).reload(),
      onSignIn: () => ref.read(authActionsProvider).logout(),
      builder: (context, data) {
        final sorted = sortMyOffers(data);
        return RefreshIndicator(
          onRefresh: () => ref.read(myOffersProvider.notifier).refresh(),
          child: sorted.isEmpty
              ? ListView(
                  physics: const AlwaysScrollableScrollPhysics(),
                  children: [
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xl,
                      ),
                      child: EmptyState(
                        key: const Key('offers-empty'),
                        icon: Icons.local_offer_outlined,
                        title: l10n.jbNoOffersTitle,
                        message: l10n.jbNoOffersBody,
                      ),
                    ),
                  ],
                )
              : ListView.builder(
                  key: const Key('offers-list'),
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  itemCount: sorted.length,
                  itemBuilder: (_, i) => OfferTile(offer: sorted[i]),
                ),
        );
      },
    );
  }
}

class OfferTile extends ConsumerStatefulWidget {
  const OfferTile({super.key, required this.offer});

  final MyOffer offer;

  @override
  ConsumerState<OfferTile> createState() => _OfferTileState();
}

class _OfferTileState extends ConsumerState<OfferTile> {
  bool _busy = false;

  Future<void> _withdraw() async {
    if (_busy) return;
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = true);
    try {
      await ref.read(jobActionsProvider).withdraw(widget.offer.id);
      messenger.showSnackBar(SnackBar(content: Text(l10n.jbWithdrawn)));
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(jobErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final offer = widget.offer;
    final reason = offerReasonText(l10n, offer);
    return Card(
      key: Key('offer-${offer.id}'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    offer.amount == null ? '-' : formatter.format(offer.amount!),
                    style: theme.textTheme.headlineSmall,
                  ),
                ),
                StatusChip(
                  label: offerStateText(l10n, offer.state),
                  tone: offerStateTone(offer.state),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              offer.kind == OfferKind.counterOffer
                  ? l10n.jbKindCounter
                  : l10n.jbKindFare,
              style: theme.textTheme.bodySmall,
            ),
            if (offer.scopeSummary.isNotEmpty)
              ExpansionTile(
                key: Key('offer-scope-${offer.id}'),
                tilePadding: EdgeInsets.zero,
                title: Text(l10n.scSummaryTitle),
                children: [
                  ScopeSummaryList(
                    lines: offer.scopeSummary,
                    scope: offer.scope,
                    strings: scopeStrings(l10n),
                    showTitle: false,
                    dense: true,
                  ),
                ],
              ),
            if (reason != null) ...[
              const SizedBox(height: AppSpacing.xs),
              Text(reason, key: const Key('offer-reason')),
            ],
            if (offer.isPending)
              Countdown(
                until: offer.expiresAt,
                builder: (context, left, expired) => Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                  child: Text(
                    expired
                        ? l10n.jbRequestExpired
                        : l10n.jbExpiresIn(formatClock(context, left)),
                  ),
                ),
              ),
            const SizedBox(height: AppSpacing.sm),
            if (offer.isPending)
              AppButton(
                key: const Key('offer-withdraw'),
                label: l10n.jbWithdraw,
                secondary: true,
                loading: _busy,
                onPressed: _withdraw,
              ),
            if (offer.state == OfferState.selected)
              AppButton(
                key: const Key('offer-open-job'),
                label: l10n.jbOpenJob,
                icon: Icons.directions_car_outlined,
                onPressed: () =>
                    context.push('${Routes.job}/${offer.bookingId}'),
              ),
          ],
        ),
      ),
    );
  }
}
