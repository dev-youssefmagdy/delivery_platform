import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/async_state_view.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../availability/presentation/providers/availability_providers.dart';
import '../../../availability/presentation/widgets/realtime_indicator.dart';
import '../../../provider_onboarding/presentation/widgets/onboarding_labels.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'request_card_tile.dart';

/// The incoming requests feed: realtime plus pull to refresh, every async
/// state, the reason the list is empty (offline, not eligible, nothing yet).
class RequestsTab extends ConsumerWidget {
  const RequestsTab({super.key, required this.onGoOnline});

  /// Switches to the home tab, where the online switch lives.
  final VoidCallback onGoOnline;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final feed = ref.watch(requestsProvider);
    final online = ref.watch(
      availabilityProvider.select((s) => s.value?.availability.online ?? false),
    );
    final offers = ref.watch(myOffersProvider).value;

    return Column(
      children: [
        const Padding(
          padding: EdgeInsetsDirectional.fromSTEB(
            AppSpacing.md,
            AppSpacing.sm,
            AppSpacing.md,
            0,
          ),
          child: Align(
            alignment: AlignmentDirectional.centerStart,
            child: RealtimeIndicator(),
          ),
        ),
        Expanded(
          child: AsyncStateView<RequestFeed>(
            value: feed,
            onRetry: () => ref.read(requestsProvider.notifier).reload(),
            onSignIn: () => ref.read(authActionsProvider).logout(),
            builder: (context, data) => RefreshIndicator(
              onRefresh: () => ref.read(requestsProvider.notifier).refresh(),
              child: ListView(
                key: const Key('requests-list'),
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                children: [
                  if (!online)
                    _Notice(
                      key: const Key('requests-offline-notice'),
                      icon: Icons.power_settings_new,
                      title: l10n.jbGoOnlineTitle,
                      body: l10n.jbGoOnlineBody,
                      actionLabel: l10n.jbGoOnlineAction,
                      onAction: onGoOnline,
                    ),
                  if (!data.eligible)
                    _Notice(
                      key: const Key('requests-ineligible-notice'),
                      icon: Icons.block,
                      title: l10n.jbNotEligibleTitle,
                      body: data.eligibility.reasons
                          .map((r) => eligibilityReasonLabel(l10n, r))
                          .join('\n'),
                    ),
                  if (data.requests.isEmpty && data.eligible && online)
                    Padding(
                      padding: const EdgeInsetsDirectional.symmetric(
                        vertical: AppSpacing.xl,
                      ),
                      child: EmptyState(
                        key: const Key('requests-empty'),
                        icon: Icons.hourglass_empty,
                        title: l10n.jbNoRequestsTitle,
                        message: l10n.jbNoRequestsBody,
                      ),
                    ),
                  for (final card in data.requests)
                    RequestCardTile(
                      key: ValueKey(card.id),
                      card: card,
                      offer: pendingOfferFor(offers, card.id),
                    ),
                ],
              ),
            ),
          ),
        ),
      ],
    );
  }
}

class _Notice extends StatelessWidget {
  const _Notice({
    super.key,
    required this.icon,
    required this.title,
    required this.body,
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String title;
  final String body;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) => Card(
    color: Theme.of(context).colorScheme.surfaceContainerHighest,
    child: Padding(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Icon(icon),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(
                  title,
                  style: Theme.of(context).textTheme.titleMedium,
                ),
              ),
            ],
          ),
          if (body.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.xs),
            Text(body),
          ],
          if (actionLabel != null) ...[
            const SizedBox(height: AppSpacing.sm),
            AppButton(label: actionLabel!, onPressed: onAction),
          ],
        ],
      ),
    ),
  );
}
