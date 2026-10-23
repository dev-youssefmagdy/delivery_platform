import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/booking_entities.dart';
import '../providers/delivery_providers.dart';
import 'booking_sections.dart' show formatDateTime;
import 'booking_texts.dart';
import 'delivery_texts.dart';

/// What the booking agreed (or, before a provider is chosen, what the
/// customer asked for): the server's summary lines with included / excluded
/// icons. The same lines the provider sees.
class BookingScopeCard extends StatelessWidget {
  const BookingScopeCard({super.key, required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context) {
    final scope = booking.scope;
    final agreed = scope.agreedSummary.isNotEmpty;
    final lines = agreed ? scope.agreedSummary : scope.requestedSummary;
    if (lines.isEmpty) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    return Card(
      key: Key(agreed ? 'agreed-scope' : 'requested-scope'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              agreed ? l10n.scAgreedTitle : l10n.scRequestedTitle,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.xs),
            ScopeSummaryList(
              lines: lines,
              scope: agreed ? scope.agreed : scope.requested,
              strings: scopeStrings(l10n),
              showTitle: false,
            ),
          ],
        ),
      ),
    );
  }
}

/// Items, weight, volume, pickup window and the customer's own files.
class BookingLoadCard extends ConsumerWidget {
  const BookingLoadCard({super.key, required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final load = booking.load;
    if (load == null) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final currency = booking.proposedFare.currency;
    return Card(
      key: const Key('booking-load'),
      margin: EdgeInsets.zero,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(l10n.lfLoadTitle, style: theme.textTheme.titleMedium),
            const SizedBox(height: AppSpacing.xs),
            if (load.weightKg != null)
              Text(l10n.lfLoadWeight('${load.weightKg}')),
            if (load.volumeM3 != null)
              Text(l10n.lfLoadVolume('${load.volumeM3}')),
            if (load.declaredValueMinor != null)
              Text(
                l10n.lfLoadValue(
                  formatter.format(
                    Money(
                      minor: load.declaredValueMinor!,
                      currency: currency,
                      exponent: booking.proposedFare.exponent,
                    ),
                  ),
                ),
              ),
            if (load.windowStart != null)
              Text(
                l10n.lfLoadWindow(
                  formatDateTime(context, load.windowStart),
                  load.windowEnd == null
                      ? ''
                      : formatDateTime(context, load.windowEnd),
                ),
              ),
            for (final item in load.items)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(
                  '${item.quantity} × ${item.label}${item.note == null ? '' : ' · ${item.note}'}',
                ),
              ),
            if (load.hasFiles) _Files(bookingId: booking.id),
          ],
        ),
      ),
    );
  }
}

class _Files extends ConsumerWidget {
  const _Files({required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final files = ref.watch(bookingAttachmentsProvider(bookingId));
    return Padding(
      padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
      child: files.when(
        loading: () => const Skeleton(height: 72),
        error: (e, _) => Row(
          children: [
            Expanded(child: Text(bookingErrorText(l10n, e))),
            TextButton(
              onPressed: () =>
                  ref.invalidate(bookingAttachmentsProvider(bookingId)),
              child: Text(l10n.actionRetry),
            ),
          ],
        ),
        data: (list) => Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            for (final f in list)
              if (f.isImage && f.url != null)
                SecureImage(
                  cacheKey: 'att-${f.id}',
                  load: () =>
                      ref.read(deliveryRepositoryProvider).fileBytes(f.url!),
                  errorLabel: l10n.dcProofLoadError,
                  size: 88,
                )
              else
                Chip(
                  avatar: Icon(
                    f.kind == 'video'
                        ? Icons.videocam_outlined
                        : Icons.description_outlined,
                  ),
                  label: Text(
                    f.kind == 'video' ? l10n.lfFileVideo : l10n.lfFileDocument,
                  ),
                ),
          ],
        ),
      ),
    );
  }
}

/// Parcel / moving / freight tracking: custody timeline with the moments of
/// each step, the recipient code status (how the code travels), contacts, the
/// proof viewer, failed attempts and the retry / return choice. Buttons come
/// from the server's `allowed_actions`.
class DeliveryPanel extends ConsumerStatefulWidget {
  const DeliveryPanel({super.key, required this.booking});

  final Booking booking;

  @override
  ConsumerState<DeliveryPanel> createState() => _DeliveryPanelState();
}

class _DeliveryPanelState extends ConsumerState<DeliveryPanel> {
  AttemptResolution? _busy;
  Object? _error;
  bool _showProofs = false;

  Future<void> _resolve(
    DeliveryAttempt attempt,
    AttemptResolution resolution,
  ) async {
    if (_busy != null) return;
    setState(() {
      _busy = resolution;
      _error = null;
    });
    try {
      await ref
          .read(customerDeliveryActionsProvider)
          .resolveAttempt(widget.booking.id, attempt.id, resolution);
    } on Object catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  Map<CustodyStatus, DateTime> get _times => {
    for (final e in widget.booking.timeline)
      if (e.custody != null && e.at != null) e.custody!: e.at!,
  };

  @override
  Widget build(BuildContext context) {
    final booking = widget.booking;
    final delivery = booking.delivery;
    if (delivery == null) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final strings = deliveryStrings(l10n);
    final pending = delivery.attempts.pending;
    final canResolve =
        booking.can(BookingAction.resolveDeliveryAttempt) && pending != null;

    return Column(
      key: const Key('delivery-panel'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: delivery.custody.isKnown
                ? CustodyTimelineView(
                    delivery: delivery,
                    strings: strings,
                    times: _times,
                  )
                : Text(l10n.dcCustodyNotStarted),
          ),
        ),
        if (delivery.recipientOtp != null) ...[
          const SizedBox(height: AppSpacing.md),
          RecipientOtpCard(
            state: delivery.recipientOtp!,
            strings: strings,
            showShareHint: true,
          ),
        ],
        if (booking.contacts.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.md),
          Card(
            key: const Key('delivery-contacts'),
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.dcContactsTitle,
                    style: theme.textTheme.titleMedium,
                  ),
                  for (final c in booking.contacts)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.xs,
                      ),
                      child: Text(
                        '${contactRoleText(l10n, c.role)}: ${c.name ?? ''}'
                        '${c.phoneMasked == null ? '' : ' · ${c.phoneMasked}'}',
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
        if (canResolve) ...[
          const SizedBox(height: AppSpacing.md),
          Card(
            key: const Key('attempt-resolution'),
            margin: EdgeInsets.zero,
            color: theme.colorScheme.errorContainer,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(l10n.dcFailedTitle, style: theme.textTheme.titleMedium),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    attemptOutcomeText(l10n, pending.outcome),
                    key: const Key('attempt-outcome'),
                  ),
                  if (pending.reason != null && pending.reason!.isNotEmpty)
                    Text(pending.reason!),
                  const SizedBox(height: AppSpacing.xs),
                  Text(l10n.dcFailedChoose, style: theme.textTheme.bodyMedium),
                  const SizedBox(height: AppSpacing.sm),
                  if (delivery.attempts.retryAvailable)
                    AppButton(
                      key: const Key('attempt-retry'),
                      label: l10n.dcResRetry,
                      loading: _busy == AttemptResolution.retry,
                      onPressed: _busy != null
                          ? null
                          : () => _resolve(pending, AttemptResolution.retry),
                    )
                  else
                    Text(
                      l10n.dcErrAttemptsExhausted,
                      key: const Key('attempt-exhausted'),
                    ),
                  const SizedBox(height: AppSpacing.sm),
                  AppButton(
                    key: const Key('attempt-return'),
                    label: l10n.dcResReturn,
                    secondary: true,
                    loading: _busy == AttemptResolution.returnToSender,
                    onPressed: _busy != null
                        ? null
                        : () => _resolve(
                            pending,
                            AttemptResolution.returnToSender,
                          ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  AppButton(
                    key: const Key('attempt-operations'),
                    label: l10n.dcResOperations,
                    secondary: true,
                    loading: _busy == AttemptResolution.operations,
                    onPressed: _busy != null
                        ? null
                        : () => _resolve(pending, AttemptResolution.operations),
                  ),
                  if (_error != null)
                    Padding(
                      padding: const EdgeInsetsDirectional.only(
                        top: AppSpacing.sm,
                      ),
                      child: Text(
                        bookingErrorText(l10n, _error!),
                        key: const Key('attempt-resolve-error'),
                        style: TextStyle(color: theme.colorScheme.error),
                      ),
                    ),
                ],
              ),
            ),
          ),
        ],
        if (delivery.attempts.items.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.md),
          AttemptsList(attempts: delivery.attempts, strings: strings),
        ],
        if (booking.can(BookingAction.viewProofs)) ...[
          const SizedBox(height: AppSpacing.sm),
          AppButton(
            key: const Key('toggle-proofs'),
            label: _showProofs ? l10n.dcHideProofs : l10n.dcShowProofs,
            icon: Icons.photo_library_outlined,
            secondary: true,
            onPressed: () => setState(() => _showProofs = !_showProofs),
          ),
          if (_showProofs)
            _ProofViewer(bookingId: booking.id, strings: strings),
        ],
      ],
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
    final proofs = ref.watch(bookingProofsProvider(bookingId));
    return Padding(
      padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
      child: proofs.when(
        loading: () => const Skeleton(height: 120),
        error: (e, _) => Row(
          key: const Key('proofs-error'),
          children: [
            Expanded(child: Text(bookingErrorText(l10n, e))),
            TextButton(
              onPressed: () => ref.invalidate(bookingProofsProvider(bookingId)),
              child: Text(l10n.actionRetry),
            ),
          ],
        ),
        data: (list) => ProofGallery(
          proofs: list,
          strings: strings,
          loadBytes: (url) =>
              ref.read(deliveryRepositoryProvider).fileBytes(url),
        ),
      ),
    );
  }
}
