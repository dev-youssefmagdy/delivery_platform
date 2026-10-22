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

/// Job history from `GET /provider/bookings`: the jobs the driver held, newest
/// first; tapping one opens the read-only detail (the same screen as the active
/// job, with whatever actions the server still allows).
class HistoryTab extends ConsumerWidget {
  const HistoryTab({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final history = ref.watch(jobHistoryProvider);
    return AsyncStateView<JobHistoryState>(
      value: history,
      onRetry: () => ref.invalidate(jobHistoryProvider),
      onSignIn: () => ref.read(authActionsProvider).logout(),
      builder: (context, data) => RefreshIndicator(
        onRefresh: () async => ref.refresh(jobHistoryProvider.future),
        child: data.items.isEmpty
            ? ListView(
                physics: const AlwaysScrollableScrollPhysics(),
                children: [
                  Padding(
                    padding: const EdgeInsetsDirectional.only(top: AppSpacing.xl),
                    child: EmptyState(
                      key: const Key('history-empty'),
                      icon: Icons.history,
                      title: l10n.jbNoHistoryTitle,
                      message: l10n.jbNoHistoryBody,
                    ),
                  ),
                ],
              )
            : NotificationListener<ScrollNotification>(
                onNotification: (n) {
                  if (n.metrics.pixels >= n.metrics.maxScrollExtent - 200) {
                    ref.read(jobHistoryProvider.notifier).loadMore();
                  }
                  return false;
                },
                child: ListView.builder(
                  key: const Key('history-list'),
                  physics: const AlwaysScrollableScrollPhysics(),
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  itemCount: data.items.length + (data.loadingMore ? 1 : 0),
                  itemBuilder: (_, i) => i >= data.items.length
                      ? const Center(child: CircularProgressIndicator())
                      : HistoryRow(entry: data.items[i]),
                ),
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
    final theme = Theme.of(context);
    return Card(
      key: Key('history-${entry.bookingId}'),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: () => context.push('${Routes.job}/${entry.bookingId}'),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${entry.code} · ${entry.service.resolve(language)}',
                      style: theme.textTheme.titleMedium,
                    ),
                    if (entry.when != null)
                      Text(formatDateTime(context, entry.when!), style: theme.textTheme.bodySmall),
                    const SizedBox(height: AppSpacing.xs),
                    StatusChip(
                      label: jobStatusText(l10n, entry.status),
                      tone: jobStatusTone(entry.status),
                    ),
                  ],
                ),
              ),
              if (entry.amount != null)
                Text(formatter.format(entry.amount!), style: theme.textTheme.titleLarge),
            ],
          ),
        ),
      ),
    );
  }
}
