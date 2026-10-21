import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../providers/job_providers.dart';
import 'job_texts.dart';

/// Shown above every tab while the driver holds a job, so it can always be
/// reopened with one large tap (also after the app was restored from the
/// background: the job comes from `GET /provider/bookings/active`).
class ActiveJobBanner extends ConsumerWidget {
  const ActiveJobBanner({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final job = ref.watch(activeJobProvider).value;
    if (job == null || job.isFinished) return const SizedBox.shrink();
    final theme = Theme.of(context);
    return Material(
      key: const Key('active-job-banner'),
      color: theme.colorScheme.primaryContainer,
      child: InkWell(
        onTap: () => context.push('${Routes.job}/${job.id}'),
        child: Padding(
          padding: const EdgeInsetsDirectional.symmetric(
            horizontal: AppSpacing.md,
            vertical: AppSpacing.sm,
          ),
          child: Row(
            children: [
              const Icon(Icons.directions_car),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      l10n.jbActiveJob,
                      style: theme.textTheme.titleMedium,
                    ),
                    Text(
                      '${job.code} · ${jobStatusText(l10n, job.status)}',
                      key: const Key('active-job-status'),
                    ),
                  ],
                ),
              ),
              FilledButton(
                key: const Key('active-job-open'),
                onPressed: () => context.push('${Routes.job}/${job.id}'),
                child: Text(l10n.jbOpenJob),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
