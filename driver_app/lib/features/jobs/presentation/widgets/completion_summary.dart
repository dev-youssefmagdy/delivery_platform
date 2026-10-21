import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'job_texts.dart';

/// The end of a job: the amount, how it is paid (cash to collect), the net
/// earning preview when the server computes one, the rating of the customer
/// and a way to report. For cancelled or failed jobs, what happened.
class CompletionSummary extends ConsumerWidget {
  const CompletionSummary({super.key, required this.job});

  final Job job;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    final amount = job.finalFare ?? job.agreedFare;
    final preview = job.earningsPreview;

    return ListView(
      key: const Key('job-summary'),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Icon(
          job.isCompleted ? Icons.check_circle : Icons.info_outline,
          size: 72,
          color: job.isCompleted
              ? AppColors.success
              : theme.colorScheme.onSurfaceVariant,
        ),
        const SizedBox(height: AppSpacing.sm),
        Center(
          child: StatusChip(
            label: jobStatusText(l10n, job.status),
            tone: jobStatusTone(job.status),
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(
          job.isCompleted ? l10n.jbSummaryDone : l10n.jbSummaryEnded,
          textAlign: TextAlign.center,
          style: theme.textTheme.headlineSmall,
        ),
        Text(
          '${job.code} · ${job.service.resolve(language)}',
          textAlign: TextAlign.center,
        ),
        if (job.isCompleted && amount != null) ...[
          const SizedBox(height: AppSpacing.md),
          Card(
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                children: [
                  Text(l10n.jbSummaryAmount),
                  Text(
                    formatter.format(amount),
                    key: const Key('summary-amount'),
                    style: theme.textTheme.displaySmall?.copyWith(
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    paymentMethodText(l10n, job.paymentMethod),
                    key: const Key('summary-payment'),
                  ),
                  if (job.collectsCash) ...[
                    const SizedBox(height: AppSpacing.sm),
                    Text(
                      l10n.jbSummaryCollectCash(formatter.format(amount)),
                      key: const Key('summary-cash'),
                      textAlign: TextAlign.center,
                      style: theme.textTheme.titleMedium,
                    ),
                  ],
                  if (preview != null) ...[
                    const Divider(),
                    _Row(
                      l10n.jbPreviewCommission,
                      formatter.format(
                        Money(
                          minor: preview.commissionMinor,
                          currency: amount.currency,
                          exponent: amount.exponent,
                        ),
                      ),
                    ),
                    _Row(
                      l10n.jbPreviewNet,
                      formatter.format(
                        Money(
                          minor: preview.netMinor,
                          currency: amount.currency,
                          exponent: amount.exponent,
                        ),
                      ),
                      strong: true,
                    ),
                  ],
                ],
              ),
            ),
          ),
        ],
        if (job.can(JobAction.rateCustomer)) ...[
          const SizedBox(height: AppSpacing.md),
          RateCustomerCard(job: job),
        ],
        const SizedBox(height: AppSpacing.md),
        if (job.can(JobAction.report))
          AppButton(
            key: const Key('summary-report'),
            label: l10n.jbReportIssue,
            icon: Icons.report_outlined,
            secondary: true,
            onPressed: () => context.push('${Routes.job}/${job.id}/report'),
          ),
        const SizedBox(height: AppSpacing.sm),
        AppButton(
          key: const Key('summary-home'),
          label: l10n.jbBackHome,
          onPressed: () => context.go(Routes.providerOnboarding),
        ),
      ],
    );
  }
}

class _Row extends StatelessWidget {
  const _Row(this.label, this.value, {this.strong = false});

  final String label;
  final String value;
  final bool strong;

  @override
  Widget build(BuildContext context) {
    final style = strong
        ? Theme.of(context).textTheme.titleMedium
        : Theme.of(context).textTheme.bodyLarge;
    return Padding(
      padding: const EdgeInsetsDirectional.symmetric(vertical: 2),
      child: Row(
        children: [
          Expanded(child: Text(label, style: style)),
          Text(value, style: style),
        ],
      ),
    );
  }
}

/// Rates the customer once: big stars, optional tags, optional comment.
class RateCustomerCard extends ConsumerStatefulWidget {
  const RateCustomerCard({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<RateCustomerCard> createState() => _RateCustomerCardState();
}

class _RateCustomerCardState extends ConsumerState<RateCustomerCard> {
  int _stars = 0;
  final _tags = <CustomerRatingTag>{};
  final _comment = TextEditingController();
  bool _busy = false;
  bool _done = false;
  Object? _error;

  @override
  void dispose() {
    _comment.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (_busy || _stars == 0) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(jobActionsProvider)
          .rateCustomer(
            widget.job.id,
            stars: _stars,
            tags: _tags.toList(),
            comment: _comment.text.trim(),
          );
      if (mounted) setState(() => _done = true);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e;
        // Already rated or closed: nothing more to do here.
        if (e.code == 'rating_already_submitted') _done = true;
      });
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    if (_done) {
      return Card(
        key: const Key('rating-done'),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Text(
            l10n.jbRatingThanks,
            textAlign: TextAlign.center,
            style: theme.textTheme.titleMedium,
          ),
        ),
      );
    }
    return Card(
      key: const Key('rating-card'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.jbRateTitle, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.sm),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                for (var i = 1; i <= 5; i++)
                  IconButton(
                    key: Key('rating-star-$i'),
                    iconSize: 40,
                    constraints: const BoxConstraints(
                      minWidth: 56,
                      minHeight: 56,
                    ),
                    tooltip: l10n.jbStarsLabel(i),
                    onPressed: _busy ? null : () => setState(() => _stars = i),
                    icon: Icon(
                      i <= _stars ? Icons.star : Icons.star_border,
                      color: AppColors.accent,
                    ),
                  ),
              ],
            ),
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                for (final tag in CustomerRatingTag.values)
                  FilterChip(
                    key: Key('rating-tag-${tag.value}'),
                    label: Text(customerTagText(l10n, tag)),
                    selected: _tags.contains(tag),
                    onSelected: _busy
                        ? null
                        : (on) => setState(
                            () => on ? _tags.add(tag) : _tags.remove(tag),
                          ),
                  ),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            TextField(
              controller: _comment,
              maxLines: 2,
              maxLength: 300,
              decoration: InputDecoration(labelText: l10n.jbRateComment),
            ),
            if (_error != null)
              Text(
                jobErrorText(l10n, _error!),
                key: const Key('rating-error'),
                style: TextStyle(color: theme.colorScheme.error),
              ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('rating-submit'),
              label: l10n.jbRateSubmit,
              loading: _busy,
              onPressed: _stars == 0 ? null : _submit,
            ),
          ],
        ),
      ),
    );
  }
}
