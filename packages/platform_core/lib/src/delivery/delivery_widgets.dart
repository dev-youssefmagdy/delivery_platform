import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

import '../enums/proof_type.dart';
import '../support/ui/secure_image.dart';
import '../ui/components.dart';
import '../ui/tokens.dart';
import 'delivery_models.dart';

/// Texts of the delivery views; each app fills them from its ARB files (the
/// package holds no literal strings).
class DeliveryStrings {
  const DeliveryStrings({
    required this.custodyTitle,
    required this.custody,
    required this.otpTitle,
    required this.otpWaitingForCode,
    required this.otpSent,
    required this.otpSmsFailed,
    required this.otpVerified,
    required this.otpLocked,
    required this.otpAttemptsLeft,
    required this.otpShareHint,
    required this.proofsTitle,
    required this.proofType,
    required this.proofStage,
    required this.noProofs,
    required this.proofLoadError,
    required this.checklistItem,
    required this.weightValue,
    required this.attemptsTitle,
    required this.attemptNumber,
    required this.outcome,
    required this.resolution,
    required this.attemptEvidence,
    required this.pending,
  });

  final String custodyTitle;
  final String Function(CustodyStatus status) custody;
  final String otpTitle;
  final String otpWaitingForCode;
  final String otpSent;
  final String otpSmsFailed;
  final String otpVerified;
  final String otpLocked;
  final String Function(int left) otpAttemptsLeft;

  /// Tells the customer how the code travels (SMS to the recipient, who reads
  /// it out to the courier).
  final String otpShareHint;
  final String proofsTitle;
  final String Function(ProofType type) proofType;
  final String Function(String stage) proofStage;
  final String noProofs;
  final String proofLoadError;
  final String Function(String code, bool checked) checklistItem;
  final String Function(String kg) weightValue;
  final String attemptsTitle;
  final String Function(int number) attemptNumber;
  final String Function(AttemptOutcome outcome) outcome;
  final String Function(AttemptResolution resolution) resolution;
  final String attemptEvidence;
  final String pending;
}

String _time(BuildContext context, DateTime? t) => t == null
    ? ''
    : DateFormat.MMMd(Localizations.localeOf(context).toString())
          .add_jm()
          .format(t.toLocal());

/// Where the goods are, as a timeline of custody steps. [times] carries the
/// moment of each step the booking timeline recorded.
class CustodyTimelineView extends StatelessWidget {
  const CustodyTimelineView({
    super.key,
    required this.delivery,
    required this.strings,
    this.times = const {},
  });

  final DeliveryState delivery;
  final DeliveryStrings strings;
  final Map<CustodyStatus, DateTime> times;

  List<CustodyStatus> get _path {
    final status = delivery.custody.status;
    final branch = const {
      CustodyStatus.attemptFailed,
      CustodyStatus.returning,
      CustodyStatus.returned,
    }.contains(status);
    if (!branch) return CustodyStatus.happyPath;
    return [
      CustodyStatus.pickedUp,
      CustodyStatus.inTransit,
      CustodyStatus.arrivedDropoff,
      CustodyStatus.attemptFailed,
      if (status != CustodyStatus.attemptFailed) CustodyStatus.returning,
      if (status == CustodyStatus.returned) CustodyStatus.returned,
    ];
  }

  @override
  Widget build(BuildContext context) {
    final path = _path;
    final current = path.indexOf(delivery.custody.status);
    return Column(
      key: const Key('custody-timeline'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          strings.custodyTitle,
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: AppSpacing.sm),
        Timeline(
          steps: [
            for (var i = 0; i < path.length; i++)
              TimelineStep(
                title: strings.custody(path[i]),
                subtitle: times[path[i]] == null
                    ? null
                    : _time(context, times[path[i]]),
                done: current >= 0 && i <= current,
              ),
          ],
        ),
      ],
    );
  }
}

/// The recipient code: its state only (the code itself is never sent to the
/// customer or the driver; the recipient gets it by SMS and reads it out).
class RecipientOtpCard extends StatelessWidget {
  const RecipientOtpCard({
    super.key,
    required this.state,
    required this.strings,
    this.showShareHint = false,
    this.now,
  });

  final RecipientOtpState state;
  final DeliveryStrings strings;

  /// The customer view explains how to pass the code on.
  final bool showShareHint;
  final DateTime? now;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final (icon, text, tone) = state.verified
        ? (Icons.verified_outlined, strings.otpVerified, ChipTone.success)
        : state.locked
        ? (Icons.lock_outline, strings.otpLocked, ChipTone.danger)
        : !state.issued
        ? (Icons.hourglass_empty, strings.otpWaitingForCode, ChipTone.neutral)
        : state.smsFailed
        ? (Icons.sms_failed_outlined, strings.otpSmsFailed, ChipTone.warning)
        : (Icons.sms_outlined, strings.otpSent, ChipTone.info);
    final left = state.attemptsRemaining;
    return Card(
      key: const Key('recipient-otp-card'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(icon),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Text(
                    strings.otpTitle,
                    style: theme.textTheme.titleMedium,
                  ),
                ),
                StatusChip(label: text, tone: tone),
              ],
            ),
            if (!state.verified &&
                !state.locked &&
                left != null &&
                state.issued)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(
                  strings.otpAttemptsLeft(left),
                  style: theme.textTheme.bodySmall,
                ),
              ),
            if (showShareHint && !state.verified)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(
                  strings.otpShareHint,
                  style: theme.textTheme.bodyMedium,
                ),
              ),
          ],
        ),
      ),
    );
  }
}

/// Proofs the job has: pictures through signed URLs (downloaded with the
/// app's auth), checklist and weight as text. Loading, empty and error states
/// are the caller's (it owns the provider); this renders a loaded list.
class ProofGallery extends StatelessWidget {
  const ProofGallery({
    super.key,
    required this.proofs,
    required this.strings,
    required this.loadBytes,
  });

  final List<BookingProof> proofs;
  final DeliveryStrings strings;

  /// Downloads the picture behind a signed URL.
  final Future<Uint8List> Function(String url) loadBytes;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (proofs.isEmpty) {
      return Text(
        strings.noProofs,
        key: const Key('proofs-empty'),
        style: theme.textTheme.bodyMedium,
      );
    }
    return Column(
      key: const Key('proof-gallery'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        for (final p in proofs)
          Padding(
            padding: const EdgeInsetsDirectional.only(bottom: AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        '${strings.proofType(p.type)} · ${strings.proofStage(p.stage)}',
                        style: theme.textTheme.titleSmall,
                      ),
                    ),
                    if (p.takenAt != null)
                      Text(
                        _time(context, p.takenAt),
                        style: theme.textTheme.bodySmall,
                      ),
                  ],
                ),
                const SizedBox(height: AppSpacing.xs),
                if (p.hasPicture)
                  SecureImage(
                    cacheKey: 'proof-${p.id}',
                    load: () => loadBytes(p.fileUrl!),
                    errorLabel: strings.proofLoadError,
                    size: 200,
                  )
                else if (p.type == ProofType.checklist)
                  for (final e in (p.details['items'] as List? ?? const []))
                    if (e is Map)
                      Text(
                        strings.checklistItem(
                          '${e['code']}',
                          e['checked'] == true,
                        ),
                      )
                    else if (p.type == ProofType.weight &&
                        p.details['weight_kg'] != null)
                      Text(strings.weightValue('${p.details['weight_kg']}')),
              ],
            ),
          ),
      ],
    );
  }
}

/// Failed delivery attempts, newest last, with what was decided.
class AttemptsList extends StatelessWidget {
  const AttemptsList({
    super.key,
    required this.attempts,
    required this.strings,
  });

  final AttemptsState attempts;
  final DeliveryStrings strings;

  @override
  Widget build(BuildContext context) {
    if (attempts.items.isEmpty) return const SizedBox.shrink();
    final theme = Theme.of(context);
    return Column(
      key: const Key('attempts-list'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(strings.attemptsTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.xs),
        for (final a in attempts.items)
          Card(
            margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${strings.attemptNumber(a.number)} · ${strings.outcome(a.outcome)}',
                          style: theme.textTheme.titleSmall,
                        ),
                      ),
                      StatusChip(
                        label: a.resolution == null
                            ? strings.pending
                            : strings.resolution(a.resolution!),
                        tone: a.resolution == null
                            ? ChipTone.warning
                            : ChipTone.neutral,
                      ),
                    ],
                  ),
                  if (a.reason != null && a.reason!.isNotEmpty)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xs,
                      ),
                      child: Text(a.reason!),
                    ),
                  if (a.hasEvidence)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xs,
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.photo_camera_outlined, size: 16),
                          const SizedBox(width: AppSpacing.xs),
                          Text(
                            strings.attemptEvidence,
                            style: theme.textTheme.bodySmall,
                          ),
                        ],
                      ),
                    ),
                ],
              ),
            ),
          ),
      ],
    );
  }
}
