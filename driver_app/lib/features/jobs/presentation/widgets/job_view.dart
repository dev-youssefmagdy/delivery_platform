import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import '../../../earnings/presentation/widgets/job_money_widgets.dart';
import 'completion_summary.dart';
import 'confirm_sheet.dart';
import 'countdown.dart';
import 'delivery_section.dart';
import 'job_texts.dart';
import 'navigation.dart';
import 'pin_sheet.dart';
import '../../../safety/presentation/widgets/safety_sheet.dart';

/// The active job (or a past one, read only): the status, what to do next as
/// big buttons drawn from the server's `allowed_actions`, the route, the
/// customer and the trip facts. A finished job shows the summary instead.
class JobView extends ConsumerStatefulWidget {
  const JobView({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<JobView> createState() => _JobViewState();
}

class _JobViewState extends ConsumerState<JobView> {
  bool _busy = false;

  Future<void> _act(Future<Object?> Function() action) async {
    if (_busy) return;
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final tracker = ref.read(jobTrackerProvider(widget.job.id).notifier);
    setState(() => _busy = true);
    try {
      await action();
    } on ApiException catch (e) {
      messenger
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(jobErrorText(l10n, e))));
      // A conflict means the server moved on: show its state.
      if (e.kind == ApiFailureKind.conflict ||
          e.kind == ApiFailureKind.validation) {
        await tracker.refresh();
      }
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(jobErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _complete() async {
    final l10n = AppLocalizations.of(context);
    final job = widget.job;
    final formatter = ref.read(moneyFormatterProvider);
    final fare = job.agreedFare;
    final ok = await showJobConfirm(
      context,
      title: l10n.jbCompleteConfirmTitle,
      body: job.collectsCash && fare != null
          ? l10n.jbCompleteConfirmCash(formatter.format(fare))
          : l10n.jbCompleteConfirmBody,
      confirmLabel: l10n.jbComplete,
    );
    if (!ok || !mounted) return;
    await _act(() => ref.read(jobActionsProvider).complete(job.id));
  }

  Future<void> _pin() async {
    final verified = await showPinSheet(context, widget.job);
    if (verified && mounted) {
      await ref.read(jobTrackerProvider(widget.job.id).notifier).refresh();
    }
  }

  @override
  Widget build(BuildContext context) {
    final job = widget.job;
    if (job.isFinished) return CompletionSummary(job: job);

    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final language = Localizations.localeOf(context).languageCode;
    final actions = ref.read(jobActionsProvider);
    final target = job.navigationTarget;
    final trip = job.trip;

    final primary = <Widget>[];
    var first = true;
    Widget big(String key, String label, IconData icon, VoidCallback? onTap) {
      final w = AppButton(
        key: Key(key),
        label: label,
        icon: icon,
        secondary: !first,
        loading: _busy && first,
        onPressed: _busy ? null : onTap,
      );
      first = false;
      return Padding(
        padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
        child: w,
      );
    }

    if (job.can(JobAction.acknowledge)) {
      primary.add(
        big(
          'job-acknowledge',
          l10n.jbActionAcknowledge,
          Icons.directions_car,
          () => _act(() => actions.acknowledge(job.id)),
        ),
      );
    }
    if (job.can(JobAction.arrived)) {
      primary.add(
        big(
          'job-arrived',
          l10n.jbActionArrived,
          Icons.place,
          () => _act(() => actions.arrived(job.id)),
        ),
      );
    }
    if (job.can(JobAction.verifyStartPin) && !trip.pinVerified) {
      primary.add(
        big(
          'job-pin',
          trip.pinLocked ? l10n.jbPinLockedButton : l10n.jbActionPin,
          trip.pinLocked ? Icons.lock : Icons.pin,
          trip.pinLocked ? null : _pin,
        ),
      );
    }
    if (job.can(JobAction.start)) {
      primary.add(
        big(
          'job-start',
          l10n.jbActionStart,
          Icons.play_arrow,
          () => _act(() => actions.start(job.id)),
        ),
      );
    }
    if (job.can(JobAction.complete)) {
      primary.add(
        big('job-complete', l10n.jbComplete, Icons.flag, _complete),
      );
    }

    return Column(
      children: [
        Expanded(
          child: ListView(
            key: const Key('job-view'),
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      '${job.code} · ${job.service.resolve(language)}',
                      style: theme.textTheme.titleMedium,
                    ),
                  ),
                  StatusChip(
                    key: const Key('job-status'),
                    label: jobStatusText(l10n, job.status),
                    tone: jobStatusTone(job.status),
                  ),
                ],
              ),
              if (job.agreedFare != null) ...[
                const SizedBox(height: AppSpacing.sm),
                Text(
                  formatter.format(job.agreedFare!),
                  key: const Key('job-fare'),
                  style: theme.textTheme.displaySmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                Text(
                  job.collectsCash
                      ? l10n.jbCollectCash
                      : paymentMethodText(l10n, job.paymentMethod),
                  key: const Key('job-payment'),
                  style: theme.textTheme.titleMedium,
                ),
              ],
              if (trip.supportFlagged)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: _Banner(
                    key: const Key('job-support-flagged'),
                    icon: Icons.support_agent,
                    text: l10n.jbSupportFlagged,
                  ),
                ),
              if (job.status == BookingStatus.providerArrived &&
                  trip.freeWaitEndsAt != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: Countdown(
                    until: trip.freeWaitEndsAt!,
                    builder: (context, left, over) => _Banner(
                      key: const Key('job-free-wait'),
                      icon: Icons.timer_outlined,
                      text: over
                          ? l10n.jbFreeWaitOver
                          : l10n.jbFreeWaitLeft(formatClock(context, left)),
                    ),
                  ),
                ),
              if (trip.pinRequired &&
                  !trip.pinVerified &&
                  job.status == BookingStatus.providerArrived)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: _Banner(
                    key: const Key('job-pin-needed'),
                    icon: Icons.pin_outlined,
                    text: trip.pinLocked
                        ? l10n.jbErrPinLocked
                        : l10n.jbPinNeeded,
                  ),
                ),
              if (trip.pinVerified && !job.can(JobAction.complete))
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                  child: _Banner(
                    key: const Key('job-pin-verified'),
                    icon: Icons.verified_outlined,
                    text: l10n.jbPinVerified,
                  ),
                ),
              const SizedBox(height: AppSpacing.md),
              _Section(
                title: l10n.jbRouteTitle,
                children: [
                  _PlaceRow(
                    icon: Icons.my_location,
                    label: l10n.jbPickup,
                    place: job.pickup,
                  ),
                  if (job.dropoff != null)
                    _PlaceRow(
                      icon: Icons.flag_outlined,
                      label: l10n.jbDropoff,
                      place: job.dropoff!,
                    ),
                  if (job.distanceMeters != null || job.durationSeconds != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xs,
                      ),
                      child: Text(
                        [
                          if (job.distanceMeters != null)
                            formatKm(context, job.distanceMeters!),
                          if (job.durationSeconds != null)
                            formatMinutes(context, job.durationSeconds!),
                        ].join(' · '),
                      ),
                    ),
                ],
              ),
              _Section(
                title: l10n.jbCustomerTitle,
                children: [
                  Text(
                    job.customer.name ?? l10n.jbCustomerUnknown,
                    style: theme.textTheme.titleMedium,
                  ),
                  if (job.customer.ratingAverage != null &&
                      job.customer.ratingCount > 0)
                    Text(
                      l10n.jbCustomerRating(
                        job.customer.ratingAverage!.toStringAsFixed(1),
                        job.customer.ratingCount,
                      ),
                    ),
                  if (job.customer.phone != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.sm,
                      ),
                      child: AppButton(
                        key: const Key('job-call'),
                        label: l10n.jbCallCustomer,
                        icon: Icons.call,
                        secondary: true,
                        onPressed: () =>
                            callCustomer(context, job.customer.phone!),
                      ),
                    )
                  else if (job.customer.phoneMasked != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xs,
                      ),
                      child: Text(
                        l10n.jbContactMasked,
                        key: const Key('job-contact-masked'),
                        style: theme.textTheme.bodySmall,
                      ),
                    ),
                ],
              ),
              JobContactsSection(job: job),
              JobStopsList(job: job),
              JobScopeSection(job: job),
              JobLoadSection(job: job),
              JobDeliverySection(job: job),
              if (_notes(job).isNotEmpty)
                _Section(
                  title: l10n.jbInstructionsTitle,
                  children: [
                    for (final e in _notes(job))
                      Padding(
                        padding: const EdgeInsetsDirectional.only(bottom: 2),
                        child: Text('${e.key}: ${e.value}'),
                      ),
                  ],
                ),
              if (job.passengers != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(
                    bottom: AppSpacing.sm,
                  ),
                  child: Text(l10n.jbPassengers(job.passengers!)),
                ),
              JobAdjustmentsSection(job: job),
              const SizedBox(height: AppSpacing.sm),
              if (!job.isFinished)
                Padding(
                  padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
                  child: AppButton(
                    key: const Key('job-safety'),
                    label: l10n.sfTitle,
                    icon: Icons.shield_outlined,
                    secondary: true,
                    onPressed: () => showSafetySheet(context, bookingId: job.id),
                  ),
                ),
              Row(
                children: [
                  if (job.can(JobAction.cancel))
                    Expanded(
                      child: AppButton(
                        key: const Key('job-cancel'),
                        label: l10n.jbCancelJob,
                        icon: Icons.close,
                        secondary: true,
                        onPressed: _busy
                            ? null
                            : () => context.push(
                                '${Routes.job}/${job.id}/cancel',
                              ),
                      ),
                    ),
                  if (job.can(JobAction.cancel) && job.can(JobAction.report))
                    const SizedBox(width: AppSpacing.sm),
                  if (job.can(JobAction.report))
                    Expanded(
                      child: AppButton(
                        key: const Key('job-report'),
                        label: l10n.jbReportIssue,
                        icon: Icons.report_outlined,
                        secondary: true,
                        onPressed: () =>
                            context.push('${Routes.job}/${job.id}/report'),
                      ),
                    ),
                ],
              ),
              const SizedBox(height: AppSpacing.sm),
              Row(
                children: [
                  if (job.can(JobAction.chat))
                    Expanded(
                      child: AppButton(
                        key: const Key('job-chat'),
                        label: l10n.supportOpenChat,
                        icon: Icons.chat_bubble_outline,
                        secondary: true,
                        onPressed: () => context.push(Routes.jobChat(job.id)),
                      ),
                    ),
                  if (job.can(JobAction.chat)) const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: AppButton(
                      key: const Key('job-help'),
                      label: l10n.supportHelpAboutTrip,
                      icon: Icons.support_agent_outlined,
                      secondary: true,
                      onPressed: () => context.push(Routes.jobSupport(job.id, job.code)),
                    ),
                  ),
                ],
              ),
              if (job.can(JobAction.appeal)) ...[
                const SizedBox(height: AppSpacing.sm),
                AppButton(
                  key: const Key('job-appeal'),
                  label: l10n.supportAppealButton,
                  icon: Icons.gavel_outlined,
                  secondary: true,
                  onPressed: () => context.push(Routes.jobAppeal(job.id)),
                ),
              ],
            ],
          ),
        ),
        if (target != null || primary.isNotEmpty)
          Material(
            elevation: 8,
            child: SafeArea(
              top: false,
              child: Padding(
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    ...primary,
                    if (target != null)
                      AppButton(
                        key: const Key('job-navigate'),
                        label: job.status == BookingStatus.inProgress
                            ? l10n.jbNavigateDropoff
                            : l10n.jbNavigatePickup,
                        icon: Icons.navigation_outlined,
                        secondary: primary.isNotEmpty,
                        onPressed: () => openNavigation(context, target),
                      ),
                  ],
                ),
              ),
            ),
          ),
      ],
    );
  }

  static List<MapEntry<String, String>> _notes(Job job) => [
    for (final e in job.serviceDetails.entries)
      if (e.value is String && (e.value as String).trim().isNotEmpty ||
          e.value is num ||
          e.value is bool)
        MapEntry(e.key.replaceAll('_', ' '), '${e.value}'),
  ];
}

class _Banner extends StatelessWidget {
  const _Banner({super.key, required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Container(
      padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
      decoration: BoxDecoration(
        color: theme.colorScheme.secondaryContainer,
        borderRadius: BorderRadius.circular(AppRadius.sm),
      ),
      child: Row(
        children: [
          Icon(icon),
          const SizedBox(width: AppSpacing.sm),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}

class _Section extends StatelessWidget {
  const _Section({required this.title, required this.children});

  final String title;
  final List<Widget> children;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: Theme.of(context).textTheme.labelLarge),
          const SizedBox(height: AppSpacing.xs),
          ...children,
        ],
      ),
    ),
  );
}

class _PlaceRow extends StatelessWidget {
  const _PlaceRow({
    required this.icon,
    required this.label,
    required this.place,
  });

  final IconData icon;
  final String label;
  final JobPlace place;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsetsDirectional.symmetric(vertical: 4),
    child: Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 22),
        const SizedBox(width: AppSpacing.sm),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: Theme.of(context).textTheme.bodySmall),
              Text(
                place.label ?? '${place.lat}, ${place.lng}',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              if (place.title != null &&
                  place.title!.isNotEmpty &&
                  place.line != null &&
                  place.line!.isNotEmpty)
                Text(place.line!),
            ],
          ),
        ),
      ],
    ),
  );
}
