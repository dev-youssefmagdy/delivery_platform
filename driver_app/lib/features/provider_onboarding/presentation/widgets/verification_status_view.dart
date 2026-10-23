import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/provider_onboarding_providers.dart';
import 'onboarding_labels.dart';

/// Where the application stands: a headline per status, the reviewer's reason,
/// the tasks still open, document and vehicle states with expiry dates and
/// rejection reasons, what blocks working (computed by the server) and how to
/// reach support.
class VerificationStatusView extends ConsumerWidget {
  const VerificationStatusView({
    super.key,
    required this.application,
    required this.onContinue,
    this.header,
  });

  final ProviderApplication application;

  /// Shown above the headline: the driver home's availability card once the
  /// application is approved (P2.2).
  final Widget? header;

  /// Opens the wizard to fix and resubmit (or continue a draft).
  final VoidCallback onContinue;

  (IconData, String, String, ChipTone) _headline(AppLocalizations l10n) =>
      switch (application.status) {
        ProviderStatus.underReview => (
          Icons.hourglass_top,
          l10n.vsUnderReviewTitle,
          l10n.vsUnderReviewBody,
          ChipTone.info,
        ),
        ProviderStatus.moreInformationRequired => (
          Icons.edit_note,
          l10n.vsMoreInfoTitle,
          l10n.vsMoreInfoBody,
          ChipTone.warning,
        ),
        ProviderStatus.rejected => (
          Icons.cancel_outlined,
          l10n.vsRejectedTitle,
          l10n.vsRejectedBody,
          ChipTone.danger,
        ),
        ProviderStatus.approved => (
          Icons.verified_outlined,
          l10n.vsApprovedTitle,
          l10n.vsApprovedBody,
          ChipTone.success,
        ),
        ProviderStatus.temporarilySuspended => (
          Icons.pause_circle_outline,
          l10n.vsSuspendedTitle,
          l10n.vsSuspendedBody,
          ChipTone.warning,
        ),
        ProviderStatus.permanentlySuspended => (
          Icons.block,
          l10n.vsPermanentTitle,
          l10n.vsPermanentBody,
          ChipTone.danger,
        ),
        ProviderStatus.deletionPending => (
          Icons.delete_outline,
          l10n.vsDeletionTitle,
          l10n.vsDeletionBody,
          ChipTone.neutral,
        ),
        _ => (
          Icons.assignment_outlined,
          l10n.vsDraftTitle,
          l10n.vsDraftBody,
          ChipTone.neutral,
        ),
      };

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final (icon, title, body, tone) = _headline(l10n);
    final app = application;
    final canFix =
        app.status == ProviderStatus.moreInformationRequired ||
        app.status == ProviderStatus.rejected ||
        !app.started ||
        app.editable && app.status != ProviderStatus.underReview;
    final tasks = [
      for (final t in app.tasks)
        if (t.task != OnboardingTask.submitApplication || canFix) t,
    ];
    final eligibility = app.status == ProviderStatus.approved
        ? ref.watch(eligibilityProvider)
        : null;

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        if (header != null) ...[header!, const SizedBox(height: AppSpacing.md)],
        Icon(icon, size: 56, color: Theme.of(context).colorScheme.primary),
        const SizedBox(height: AppSpacing.md),
        Center(
          child: StatusChip(label: title, tone: tone),
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(
          title,
          key: const Key('verification-headline'),
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.headlineSmall,
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(body, textAlign: TextAlign.center),
        if (app.submittedAt != null &&
            app.status == ProviderStatus.underReview) ...[
          const SizedBox(height: AppSpacing.xs),
          Text(
            l10n.vsSubmittedOn(formatDay(context, app.submittedAt!.toLocal())),
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ],
        if (app.statusReason != null && app.statusReason!.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.md),
          Card(
            color: Theme.of(context).colorScheme.errorContainer,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.vsReviewerReason,
                    style: Theme.of(context).textTheme.labelLarge,
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    app.statusReason!,
                    key: const Key('verification-reason'),
                  ),
                ],
              ),
            ),
          ),
        ],
        if (tasks.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsTasksTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final t in tasks)
            ListTile(
              key: Key('status-task-${t.task.value}'),
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.radio_button_unchecked),
              title: Text(taskLabel(l10n, t, app, language)),
            ),
        ],
        if (app.providerDocuments.isNotEmpty || app.vehicles.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsDocumentsTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final d in app.requiredDocuments)
            _DocumentRow(
              item: d,
              language: language,
              vehicleTitle: d.vehicleId == null
                  ? null
                  : app.vehicleById(d.vehicleId!)?.title,
            ),
        ],
        if (app.vehicles.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsVehiclesTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final v in app.vehicles)
            ListTile(
              key: Key('status-vehicle-${v.id}'),
              contentPadding: EdgeInsets.zero,
              title: Text(v.title),
              subtitle: Text(
                v.rejectionReason == null
                    ? ltrIsolate(v.plate)
                    : l10n.obVehicleRejectedReason(v.rejectionReason!)),
              trailing: StatusChip(
                label: v.status.label(l10n),
                tone: v.status.tone,
              ),
            ),
        ],
        if (app.expiringDocuments.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsExpiringTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          for (final d in app.expiringDocuments)
            ListTile(
              key: Key('expiring-${d.type.code}'),
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.event_busy_outlined),
              title: Text(d.type.name.resolve(language)),
              subtitle: d.expiresAt == null
                  ? null
                  : Text(l10n.obDocExpires(formatDay(context, d.expiresAt!))),
            ),
        ],
        if (eligibility != null) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsEligibilityTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          eligibility.when(
            loading: () => const LinearProgressIndicator(),
            error: (e, _) => Text(onboardingErrorText(l10n, e)),
            data: (info) => info.eligible
                ? Text(l10n.vsEligible, key: const Key('eligibility-ok'))
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        l10n.vsNotEligible,
                        key: const Key('eligibility-blocked'),
                      ),
                      for (final r in info.reasons)
                        ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: const Icon(Icons.error_outline),
                          title: Text(eligibilityReasonLabel(l10n, r)),
                        ),
                    ],
                  ),
          ),
        ],
        if (canFix) ...[
          const SizedBox(height: AppSpacing.lg),
          AppButton(
            key: const Key('verification-continue'),
            label: app.started ? l10n.vsUpdate : l10n.vsContinue,
            onPressed: onContinue,
          ),
        ] else if (app.status == ProviderStatus.approved &&
            (app.tasks.isNotEmpty || app.services.isNotEmpty)) ...[
          const SizedBox(height: AppSpacing.lg),
          AppButton(
            key: const Key('verification-update'),
            label: l10n.vsUpdate,
            secondary: true,
            onPressed: onContinue,
          ),
        ],
        if (!app.support.isEmpty) ...[
          const SizedBox(height: AppSpacing.lg),
          Text(
            l10n.vsSupportTitle,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          if (app.support.phone != null)
            Text(
              l10n.vsSupportPhone(ltrIsolate(app.support.phone!))),
          if (app.support.email != null)
            Text(
              l10n.vsSupportEmail(ltrIsolate(app.support.email!))),
        ],
      ],
    );
  }
}

class _DocumentRow extends StatelessWidget {
  const _DocumentRow({
    required this.item,
    required this.language,
    this.vehicleTitle,
  });

  final RequiredDocument item;
  final String language;
  final String? vehicleTitle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final reason = item.state == DocumentStatus.rejected
        ? item.document?.rejectionReason
        : null;
    final expires = item.expiresAt ?? item.document?.expiresAt;
    final lines = [
      ?vehicleTitle,
      if (expires != null) l10n.obDocExpires(formatDay(context, expires)),
      if (reason != null && reason.isNotEmpty) l10n.obDocReason(reason),
    ];

    return ListTile(
      key: Key(
        'status-document-${item.vehicleId ?? 'provider'}-${item.type.code}',
      ),
      contentPadding: EdgeInsets.zero,
      title: Text(item.type.name.resolve(language)),
      subtitle: lines.isEmpty ? null : Text(lines.join('\n')),
      trailing: StatusChip(
        label: documentStateLabel(l10n, item.state),
        tone: documentStateTone(item.state),
      ),
    );
  }
}
