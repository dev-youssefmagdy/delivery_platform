import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../booking/presentation/widgets/booking_sections.dart'
    show formatDateTime;
import '../providers/payments_providers.dart';

/// The Offers tab: automatic offers the customer can use now (applied by the
/// server when booking) and their recent coupon uses. Coupon codes are typed
/// in the booking form, never listed here.
class OffersScreen extends ConsumerWidget {
  const OffersScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final coupons = ref.watch(myCouponsProvider);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.cpnTitle)),
      body: SafeArea(
        child: AsyncStateView<MyCoupons>(
          value: coupons,
          onRetry: () => ref.invalidate(myCouponsProvider),
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(
            onRefresh: () async {
              ref.invalidate(myCouponsProvider);
              await ref.read(myCouponsProvider.future);
            },
            child: _OffersBody(data: data),
          ),
        ),
      ),
    );
  }
}

class _OffersBody extends ConsumerWidget {
  const _OffersBody({required this.data});

  final MyCoupons data;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final formatter = ref.watch(moneyFormatterProvider);
    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(l10n.cpnAvailable, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (data.available.isEmpty)
          Text(l10n.cpnNoneAvailable, key: const Key('offers-none'))
        else
          for (final c in data.available)
            Card(
              key: Key('offer-${c.id}'),
              margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
              child: ExpansionTile(
                leading: const Icon(Icons.local_offer_outlined),
                title: Text(c.title.resolve(language)),
                subtitle: Text(
                  [
                    if (c.summary != null) c.summary!,
                    if (c.endsAt != null)
                      l10n.cpnValidUntil(formatDateTime(context, c.endsAt)),
                  ].join(' - '),
                ),
                childrenPadding: const EdgeInsetsDirectional.all(AppSpacing.md),
                expandedCrossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (!c.description.isEmpty)
                    Text(c.description.resolve(language)),
                  if (!c.terms.isEmpty) ...[
                    const SizedBox(height: AppSpacing.sm),
                    Text(l10n.cpnTerms, style: theme.textTheme.labelLarge),
                    Text(
                      c.terms.resolve(language),
                      style: theme.textTheme.bodySmall,
                    ),
                  ],
                  const SizedBox(height: AppSpacing.sm),
                  Text(l10n.cpnAutoNote, style: theme.textTheme.bodySmall),
                ],
              ),
            ),
        const SizedBox(height: AppSpacing.md),
        Text(l10n.cpnHistory, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (data.history.isEmpty)
          Text(l10n.cpnNoHistory, key: const Key('offers-no-history'))
        else
          for (final u in data.history)
            ListTile(
              key: Key('usage-${u.id}'),
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.confirmation_number_outlined),
              title: Text(
                u.title.resolve(language).isEmpty
                    ? (u.code ?? '')
                    : u.title.resolve(language),
              ),
              subtitle: Text(formatDateTime(context, u.reservedAt)),
              trailing: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  IsolatedText(formatter.format(u.discount)),
                  StatusChip(
                    label: usageStatusText(l10n, u.status),
                    tone: _usageTone(u.status),
                  ),
                ],
              ),
            ),
      ],
    );
  }
}

ChipTone _usageTone(String status) => switch (status) {
  'committed' => ChipTone.success,
  'reserved' => ChipTone.info,
  'reversed' => ChipTone.danger,
  _ => ChipTone.neutral,
};

String usageStatusText(AppLocalizations l10n, String status) =>
    switch (status) {
      'committed' => l10n.cpnUsedStatus,
      'reserved' => l10n.cpnReservedStatus,
      'released' => l10n.cpnReleasedStatus,
      'reversed' => l10n.cpnReversedStatus,
      _ => l10n.cpnUnknownStatus,
    };
