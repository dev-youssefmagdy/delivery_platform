import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/delivery_providers.dart';
import 'delivery_sheets.dart';
import 'delivery_texts.dart';
import 'job_texts.dart';
import 'navigation.dart';

/// Agreed scope of a moving / freight job: the server's summary lines, with
/// the included / excluded icons. Identical to what the customer sees.
class JobScopeSection extends StatelessWidget {
  const JobScopeSection({super.key, required this.job});

  final Job job;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final scope = job.scope;
    final lines = scope.agreedSummary.isNotEmpty
        ? scope.agreedSummary
        : scope.requestedSummary;
    if (lines.isEmpty) return const SizedBox.shrink();
    return Card(
      key: const Key('job-scope'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: ScopeSummaryList(
          lines: lines,
          scope: scope.agreedSummary.isNotEmpty
              ? scope.agreed
              : scope.requested,
          strings: scopeStrings(l10n),
        ),
      ),
    );
  }
}

/// The load of a moving / freight job: items, weight, volume, the pickup
/// window and the customer's photos (signed URLs, shown after selection only).
class JobLoadSection extends ConsumerWidget {
  const JobLoadSection({super.key, required this.job});

  final Job job;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final load = job.load;
    if (load == null) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Card(
      key: const Key('job-load'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.dlLoadTitle, style: theme.textTheme.labelLarge),
            const SizedBox(height: AppSpacing.xs),
            if (load.weightKg != null)
              Text(l10n.dlLoadWeight('${load.weightKg}')),
            if (load.volumeM3 != null)
              Text(l10n.dlLoadVolume('${load.volumeM3}')),
            if (load.windowStart != null)
              Text(
                l10n.jbPickupWindow(
                  formatDateTime(context, load.windowStart!),
                  load.windowEnd == null
                      ? ''
                      : formatDateTime(context, load.windowEnd!),
                ),
              ),
            for (final item in load.items)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(
                  [
                    '${item.quantity} × ${item.label}',
                    if (item.hasDimensions)
                      '${item.length}×${item.width}×${item.height} ${l10n.dlCm}',
                    if (item.weightKg != null) '${item.weightKg} ${l10n.dlKg}',
                    if (item.note != null && item.note!.isNotEmpty) item.note!,
                  ].join(' · '),
                  key: Key('load-item-${item.code ?? item.label}'),
                ),
              ),
            if (load.hasFiles) _Attachments(bookingId: job.id),
          ],
        ),
      ),
    );
  }
}

class _Attachments extends ConsumerWidget {
  const _Attachments({required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final files = ref.watch(jobAttachmentsProvider(bookingId));
    return Padding(
      padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
      child: files.when(
        loading: () => const Skeleton(height: 72),
        error: (e, _) => Row(
          children: [
            Expanded(child: Text(jobErrorText(l10n, e))),
            TextButton(
              onPressed: () =>
                  ref.invalidate(jobAttachmentsProvider(bookingId)),
              child: Text(l10n.actionRetry),
            ),
          ],
        ),
        data: (list) => Wrap(
          key: const Key('load-files'),
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final f in list)
              if (f.isImage && f.url != null)
                SecureImage(
                  cacheKey: 'att-${f.id}',
                  load: () =>
                      ref.read(deliveryRepositoryProvider).fileBytes(f.url!),
                  errorLabel: l10n.dlProofLoadError,
                  size: 96,
                )
              else
                Chip(
                  avatar: Icon(
                    f.kind == 'video'
                        ? Icons.videocam_outlined
                        : Icons.description_outlined,
                  ),
                  label: Text(
                    f.kind == 'video' ? l10n.dlFileVideo : l10n.dlFileDocument,
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

/// Sender, recipient and the other contacts the customer gave. A phone shows
/// as a call button only when the server exposes it (direct exposure, job in
/// flight); otherwise the name and the masked-contact note.
class JobContactsSection extends StatelessWidget {
  const JobContactsSection({super.key, required this.job});

  final Job job;

  String _role(AppLocalizations l10n, String role) => switch (role) {
    'sender' => l10n.dlRoleSender,
    'recipient' => l10n.dlRoleRecipient,
    'pickup_contact' => l10n.dlRolePickup,
    'dropoff_contact' => l10n.dlRoleDropoff,
    _ => role,
  };

  @override
  Widget build(BuildContext context) {
    if (job.contacts.isEmpty) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    return Card(
      key: const Key('job-contacts'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.dlContactsTitle, style: theme.textTheme.labelLarge),
            for (final c in job.contacts)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _role(l10n, c.role),
                            style: theme.textTheme.bodySmall,
                          ),
                          Text(
                            c.name ?? l10n.jbCustomerUnknown,
                            style: theme.textTheme.titleMedium,
                          ),
                          if (c.phone == null && c.phoneMasked != null)
                            Text(
                              l10n.jbContactMasked,
                              style: theme.textTheme.bodySmall,
                            ),
                        ],
                      ),
                    ),
                    if (c.phone != null)
                      IconButton.filledTonal(
                        key: Key('contact-call-${c.role}'),
                        iconSize: 28,
                        tooltip: l10n.jbCallCustomer,
                        onPressed: () => callCustomer(context, c.phone!),
                        icon: const Icon(Icons.call),
                      ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Multi-stop navigation list: pickup, every stop in order with its contact,
/// then the drop-off. Each row opens navigation; the row the driver heads to
/// next (per the job status) is marked.
class JobStopsList extends StatelessWidget {
  const JobStopsList({super.key, required this.job});

  final Job job;

  @override
  Widget build(BuildContext context) {
    if (job.stops.isEmpty) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final heading = job.status == BookingStatus.inProgress;
    final rows = <({String label, JobPlace place, IconData icon, bool next})>[
      (
        label: l10n.jbPickup,
        place: job.pickup,
        icon: Icons.my_location,
        next: !heading,
      ),
      for (var i = 0; i < job.stops.length; i++)
        (
          label: l10n.dlStopNumber(i + 1),
          place: job.stops[i],
          icon: Icons.more_vert,
          next: false,
        ),
      if (job.dropoff != null)
        (
          label: l10n.jbDropoff,
          place: job.dropoff!,
          icon: Icons.flag_outlined,
          next: heading,
        ),
    ];
    return Card(
      key: const Key('job-stops'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.dlStopsTitle, style: theme.textTheme.labelLarge),
            for (var i = 0; i < rows.length; i++)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      rows[i].icon,
                      color: rows[i].next ? theme.colorScheme.primary : null,
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            rows[i].next
                                ? '${rows[i].label} · ${l10n.dlNextStop}'
                                : rows[i].label,
                            style: theme.textTheme.bodySmall,
                          ),
                          Text(
                            rows[i].place.label ??
                                '${rows[i].place.lat}, ${rows[i].place.lng}',
                            style: theme.textTheme.titleMedium,
                          ),
                          if (rows[i].place.contact?.name != null)
                            Text(
                              [
                                rows[i].place.contact!.name!,
                                if (rows[i].place.contact!.note != null)
                                  rows[i].place.contact!.note!,
                              ].join(' · '),
                            ),
                        ],
                      ),
                    ),
                    Column(
                      children: [
                        IconButton.filledTonal(
                          key: Key('stop-navigate-$i'),
                          iconSize: 28,
                          tooltip: l10n.jbNavigatePickup,
                          onPressed: () =>
                              openNavigation(context, rows[i].place),
                          icon: const Icon(Icons.navigation_outlined),
                        ),
                        if (rows[i].place.contact?.phone != null)
                          IconButton.filledTonal(
                            key: Key('stop-call-$i'),
                            iconSize: 28,
                            tooltip: l10n.jbCallCustomer,
                            onPressed: () => callCustomer(
                              context,
                              rows[i].place.contact!.phone!,
                            ),
                            icon: const Icon(Icons.call),
                          ),
                      ],
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Custody, proofs, the recipient code and failed attempts of a parcel /
/// moving / freight job. Every button comes from the server's
/// `allowed_actions`; proofs go through the offline-safe queue.
class JobDeliverySection extends ConsumerStatefulWidget {
  const JobDeliverySection({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<JobDeliverySection> createState() => _JobDeliverySectionState();
}

class _JobDeliverySectionState extends ConsumerState<JobDeliverySection> {
  String? _busy;
  bool _showProofs = false;

  Future<void> _custody(CustodyStatus event) async {
    if (_busy != null) return;
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() => _busy = event.value);
    try {
      await ref.read(deliveryActionsProvider).custody(widget.job.id, event);
    } on Object catch (e) {
      messenger
        ..hideCurrentSnackBar()
        ..showSnackBar(SnackBar(content: Text(jobErrorText(l10n, e))));
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final job = widget.job;
    final delivery = job.delivery;
    if (delivery == null) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final strings = deliveryStrings(l10n);
    final pending = ref.watch(jobPendingProofsProvider(job.id));
    final missing = delivery.proofs.missingUploadable;
    final canUpload = job.can(JobAction.uploadProof);

    return Card(
      key: const Key('job-delivery'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            if (delivery.custody.isKnown)
              CustodyTimelineView(delivery: delivery, strings: strings),
            if (delivery.recipientOtp != null) ...[
              RecipientOtpCard(state: delivery.recipientOtp!, strings: strings),
              const SizedBox(height: AppSpacing.sm),
              if (job.can(JobAction.verifyRecipientOtp) &&
                  !delivery.recipientOtp!.verified)
                AppButton(
                  key: const Key('delivery-otp'),
                  label: delivery.recipientOtp!.locked
                      ? l10n.dlOtpLockedButton
                      : l10n.dlOtpEnter,
                  icon: delivery.recipientOtp!.locked
                      ? Icons.lock
                      : Icons.pin_outlined,
                  onPressed: delivery.recipientOtp!.locked
                      ? null
                      : () => showRecipientOtpSheet(context, job),
                ),
            ],
            if (canUpload || pending.isNotEmpty || missing.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(strings.proofsTitle, style: theme.textTheme.titleMedium),
              const SizedBox(height: AppSpacing.xs),
              if (delivery.proofs.currentStage != null)
                Text(
                  strings.proofStage(delivery.proofs.currentStage!),
                  style: theme.textTheme.bodySmall,
                ),
              for (final p in pending)
                _PendingProofTile(item: p, strings: strings),
              if (canUpload)
                for (final t in missing)
                  if (!pending.any((p) => p.type == t))
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.sm,
                      ),
                      child: AppButton(
                        key: Key('delivery-proof-${t.value}'),
                        label: l10n.dlAddProof(proofTypeText(l10n, t)),
                        icon: switch (t) {
                          ProofType.signature => Icons.draw_outlined,
                          ProofType.checklist => Icons.checklist,
                          ProofType.weight => Icons.scale_outlined,
                          _ => Icons.photo_camera_outlined,
                        },
                        secondary: true,
                        onPressed: () =>
                            showProofCaptureSheet(context, job.id, t),
                      ),
                    ),
            ],
            const SizedBox(height: AppSpacing.sm),
            if (job.can(JobAction.markInTransit))
              Padding(
                padding: const EdgeInsetsDirectional.only(
                  bottom: AppSpacing.sm,
                ),
                child: AppButton(
                  key: const Key('delivery-in-transit'),
                  label: l10n.dlMarkInTransit,
                  icon: Icons.local_shipping_outlined,
                  loading: _busy == CustodyStatus.inTransit.value,
                  onPressed: _busy != null
                      ? null
                      : () => _custody(CustodyStatus.inTransit),
                ),
              ),
            if (job.can(JobAction.arrivedDropoff))
              Padding(
                padding: const EdgeInsetsDirectional.only(
                  bottom: AppSpacing.sm,
                ),
                child: AppButton(
                  key: const Key('delivery-arrived-dropoff'),
                  label: l10n.dlArrivedDropoff,
                  icon: Icons.flag_outlined,
                  loading: _busy == CustodyStatus.arrivedDropoff.value,
                  onPressed: _busy != null
                      ? null
                      : () => _custody(CustodyStatus.arrivedDropoff),
                ),
              ),
            if (job.can(JobAction.reportDeliveryAttempt))
              AppButton(
                key: const Key('delivery-report-attempt'),
                label: l10n.dlReportAttempt,
                icon: Icons.report_gmailerrorred_outlined,
                secondary: true,
                onPressed: () => showAttemptSheet(context, job),
              ),
            if (job.can(JobAction.resolveDeliveryAttempt))
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: AppButton(
                  key: const Key('delivery-resolve'),
                  label: l10n.dlResolveButton,
                  icon: Icons.alt_route,
                  onPressed: () => showResolveSheet(context, job),
                ),
              ),
            if (delivery.attempts.items.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.sm),
              AttemptsList(attempts: delivery.attempts, strings: strings),
            ],
            if (delivery.proofs.present.values.any((v) => v.isNotEmpty)) ...[
              TextButton(
                key: const Key('delivery-toggle-proofs'),
                onPressed: () => setState(() => _showProofs = !_showProofs),
                child: Text(
                  _showProofs ? l10n.dlHideProofs : l10n.dlShowProofs,
                ),
              ),
              if (_showProofs)
                _ProofViewer(bookingId: job.id, strings: strings),
            ],
          ],
        ),
      ),
    );
  }
}

class _ProofViewer extends ConsumerWidget {
  const _ProofViewer({required this.bookingId, required this.strings});

  final String bookingId;
  final DeliveryStrings strings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final proofs = ref.watch(jobProofsProvider(bookingId));
    return proofs.when(
      loading: () => const Skeleton(height: 96),
      error: (e, _) => Row(
        children: [
          Expanded(child: Text(jobErrorText(l10n, e))),
          TextButton(
            onPressed: () => ref.invalidate(jobProofsProvider(bookingId)),
            child: Text(l10n.actionRetry),
          ),
        ],
      ),
      data: (list) => ProofGallery(
        proofs: list,
        strings: strings,
        loadBytes: (url) => ref.read(deliveryRepositoryProvider).fileBytes(url),
      ),
    );
  }
}

/// A captured proof that is not stored yet: waiting for a connection, or
/// refused (retry / discard).
class _PendingProofTile extends ConsumerWidget {
  const _PendingProofTile({required this.item, required this.strings});

  final PendingUpload item;
  final DeliveryStrings strings;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final failed = item.state == PendingUploadState.failed;
    final notifier = ref.read(proofQueueProvider.notifier);
    return Padding(
      padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
      child: Container(
        key: Key('pending-proof-${item.id}'),
        padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
        decoration: BoxDecoration(
          color: failed
              ? Theme.of(context).colorScheme.errorContainer
              : Theme.of(context).colorScheme.secondaryContainer,
          borderRadius: BorderRadius.circular(AppRadius.sm),
        ),
        child: Row(
          children: [
            Icon(failed ? Icons.error_outline : Icons.cloud_upload_outlined),
            const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: Text(
                failed
                    ? l10n.dlProofFailed(
                        strings.proofType(item.type),
                        jobErrorText(
                          l10n,
                          ApiException(
                            kind: ApiFailureKind.validation,
                            code: item.errorCode ?? '',
                            message: null,
                          ),
                        ),
                      )
                    : l10n.dlProofWaiting(strings.proofType(item.type)),
              ),
            ),
            if (failed) ...[
              IconButton(
                key: Key('pending-retry-${item.id}'),
                tooltip: l10n.actionRetry,
                onPressed: () => notifier.retry(item.id),
                icon: const Icon(Icons.refresh),
              ),
              IconButton(
                key: Key('pending-discard-${item.id}'),
                tooltip: l10n.dlDiscard,
                onPressed: () => notifier.discard(item.id),
                icon: const Icon(Icons.delete_outline),
              ),
            ] else
              IconButton(
                key: Key('pending-flush-${item.id}'),
                tooltip: l10n.dlSendNow,
                onPressed: notifier.flush,
                icon: const Icon(Icons.send_outlined),
              ),
          ],
        ),
      ),
    );
  }
}
