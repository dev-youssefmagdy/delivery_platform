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
import 'job_texts.dart';

/// Job history: the bookings the driver was selected for; tapping one opens
/// the read-only detail (the same screen as the active job, with whatever
/// actions the server still allows).
class HistoryTab extends ConsumerWidget {
  const HistoryTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final history = ref.watch(jobHistoryProvider);
    return AsyncStateView<List<JobHistoryEntry>>(
      value: history,
      onRetry: () => ref.invalidate(jobHistoryProvider),
      onSignIn: () => ref.read(authActionsProvider).logout(),
      builder: (context, data) => RefreshIndicator(
        onRefresh: () async => ref.refresh(jobHistoryProvider.future),
        child: data.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  Padding(
                    padding: const EdgeInsetsDirectional.only(
                      top: AppSpacing.xl,
                    ),
                    child: EmptyState(
                      key: const Key('history-empty'),
                      icon: Icons.history,
                      title: l10n.jbNoHistoryTitle,
                      message: l10n.jbNoHistoryBody,
                    ),
                  ),
                ],
              )
            : ListView.builder(
                key: const Key('history-list'),
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                itemCount: data.length,
                itemBuilder: (_, i) => HistoryRow(entry: data[i]),
              ),
      ),
    );
  }
}

class HistoryRow extends ConsumerWidget {
  const HistoryRow({super.key, required this.entry});

  final JobHistoryEntry entry;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    final job = ref.watch(jobDetailProvider(entry.bookingId));
    final when = entry.closedAt ?? entry.expiresAt;
    return Card(
      key: Key('history-${entry.bookingId}'),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: () => context.push('${Routes.job}/${entry.bookingId}'),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: job.when(
            loading: () => const Skeleton(height: 56),
            error: (_, _) => _row(
              context,
              title: l10n.jbHistoryJob,
              subtitle: formatDateTime(context, when),
              amount: entry.amount == null
                  ? null
                  : formatter.format(entry.amount!),
              chip: null,
            ),
            data: (j) {
              final money = j.finalFare ?? j.agreedFare ?? entry.amount;
              return _row(
                context,
                title: '${j.code} · ${j.service.resolve(language)}',
                subtitle:
                    '${formatDateTime(context, when)}\n${j.pickup.label ?? ''}',
                amount: money == null ? null : formatter.format(money),
                chip: StatusChip(
                  label: jobStatusText(l10n, j.status),
                  tone: jobStatusTone(j.status),
                ),
              );
            },
          ),
        ),
      ),
    );
  }

  Widget _row(
    BuildContext context, {
    required String title,
    required String subtitle,
    required String? amount,
    required Widget? chip,
  }) {
    final theme = Theme.of(context);
    return Row(
      children: [
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: theme.textTheme.titleMedium),
              Text(subtitle, style: theme.textTheme.bodySmall),
              if (chip != null) ...[const SizedBox(height: AppSpacing.xs), chip],
            ],
          ),
        ),
        if (amount != null) Text(amount, style: theme.textTheme.titleLarge),
      ],
    );
  }
}
