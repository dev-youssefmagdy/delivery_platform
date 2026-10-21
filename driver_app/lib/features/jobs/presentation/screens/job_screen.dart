import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import '../widgets/job_view.dart';

/// One job by id: the active job while it runs, the read-only detail from the
/// history afterwards. When the server says the driver no longer holds the
/// booking (cancelled and re-dispatched, reassigned), the job is dropped.
class JobScreen extends ConsumerWidget {
  const JobScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final job = ref.watch(jobTrackerProvider(bookingId));
    final error = job.error;
    final lost =
        error is ApiException &&
        (error.kind == ApiFailureKind.notFound ||
            error.kind == ApiFailureKind.forbidden);

    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.jbJobTitle),
        leading: BackButton(
          onPressed: () => context.canPop()
              ? context.pop()
              : context.go(Routes.providerOnboarding),
        ),
        actions: [
          IconButton(
            key: const Key('job-refresh'),
            tooltip: l10n.actionRefresh,
            icon: const Icon(Icons.refresh),
            onPressed: () =>
                ref.read(jobTrackerProvider(bookingId).notifier).refresh(),
          ),
        ],
      ),
      body: SafeArea(
        child: lost
            ? EmptyState(
                key: const Key('job-lost'),
                icon: Icons.link_off,
                title: l10n.jbJobLostTitle,
                message: l10n.jbJobLostBody,
                actionLabel: l10n.jbBackHome,
                onAction: () => context.go(Routes.providerOnboarding),
              )
            : AsyncStateView<Job>(
                value: job,
                onRetry: () =>
                    ref.read(jobTrackerProvider(bookingId).notifier).reload(),
                onSignIn: () => ref.read(authActionsProvider).logout(),
                builder: (context, data) => JobView(job: data),
              ),
      ),
    );
  }
}
