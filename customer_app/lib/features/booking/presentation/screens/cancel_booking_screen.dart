import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/repositories/booking_repository.dart';
import '../providers/booking_providers.dart';
import '../widgets/booking_texts.dart';

/// Cancel flow: pick a reason, preview the server's fee decision, confirm.
/// The fee is only ever the server's: `preview=true` first (no key), then the
/// commit with `accepted_fee_minor` and an Idempotency-Key. A changed fee
/// (409 `cancellation_fee_changed`) shows the fresh decision to confirm again.
class CancelBookingScreen extends ConsumerStatefulWidget {
  const CancelBookingScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<CancelBookingScreen> createState() =>
      _CancelBookingScreenState();
}

class _CancelBookingScreenState extends ConsumerState<CancelBookingScreen> {
  final _note = TextEditingController();
  CancelReason? _reason;
  CancellationPreview? _preview;
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  Future<void> _previewFee() async {
    final l10n = AppLocalizations.of(context);
    final reason = _reason;
    if (reason == null) {
      setState(() => _error = l10n.bkCancelPickReason);
      return;
    }
    if (reason == CancelReason.other && _note.text.trim().isEmpty) {
      setState(() => _error = l10n.bkErrCancelNote);
      return;
    }
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final preview = await ref
          .read(bookingActionsProvider)
          .previewCancel(widget.bookingId, reason, _note.text.trim());
      if (mounted) setState(() => _preview = preview);
    } on Object catch (e) {
      if (mounted) setState(() => _error = bookingErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _commit() async {
    final l10n = AppLocalizations.of(context);
    final preview = _preview;
    final reason = _reason;
    if (preview == null || reason == null) return;
    final router = GoRouter.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(bookingActionsProvider)
          .cancel(
            widget.bookingId,
            reason: reason,
            note: _note.text.trim(),
            acceptedFeeMinor: preview.fee.minor,
          );
      if (!mounted) return;
      router.pop();
    } on CancellationFeeChanged catch (e) {
      // The server decided a different fee in the meantime: confirm again.
      if (mounted) {
        setState(() {
          _preview = e.preview;
          _error = l10n.bkErrFeeChanged;
        });
      }
    } on Object catch (e) {
      if (!mounted) return;
      setState(() => _error = bookingErrorText(l10n, e));
      if (e is ApiException && e.code == 'booking_not_cancellable') {
        ref.read(bookingTrackerProvider(widget.bookingId).notifier).refresh();
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final formatter = ref.watch(moneyFormatterProvider);
    final preview = _preview;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.bkCancelTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(l10n.bkCancelReasonTitle, style: theme.textTheme.titleMedium),
            RadioGroup<CancelReason>(
              groupValue: _reason,
              onChanged: (v) {
                if (_busy || preview != null) return;
                setState(() => _reason = v);
              },
              child: Column(
                children: [
                  for (final r in CancelReason.choices)
                    RadioListTile<CancelReason>(
                      key: Key('cancel-reason-${r.value}'),
                      contentPadding: EdgeInsets.zero,
                      value: r,
                      enabled: preview == null && !_busy,
                      title: Text(cancelReasonText(l10n, r)),
                    ),
                ],
              ),
            ),
            TextField(
              key: const Key('cancel-note'),
              controller: _note,
              enabled: preview == null && !_busy,
              maxLines: 2,
              decoration: InputDecoration(labelText: l10n.bkCancelNote),
            ),
            const SizedBox(height: AppSpacing.md),
            if (preview != null)
              Card(
                key: const Key('cancel-fee-card'),
                margin: EdgeInsets.zero,
                color: preview.free
                    ? theme.colorScheme.surfaceContainerLow
                    : theme.colorScheme.errorContainer,
                child: Padding(
                  padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        preview.free
                            ? l10n.bkCancelFree
                            : l10n.bkCancelFeeDue(
                                formatter.format(preview.fee),
                              ),
                        key: const Key('cancel-fee-text'),
                        style: theme.textTheme.titleMedium,
                      ),
                      if (preview.message != null) Text(preview.message!),
                    ],
                  ),
                ),
              ),
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Text(
                  _error!,
                  key: const Key('cancel-error'),
                  style: TextStyle(color: theme.colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.md),
            if (preview == null)
              AppButton(
                key: const Key('cancel-continue'),
                label: l10n.bkCancelContinue,
                loading: _busy,
                onPressed: _previewFee,
              )
            else ...[
              AppButton(
                key: const Key('cancel-confirm'),
                label: preview.free
                    ? l10n.bkCancelConfirm
                    : l10n.bkCancelConfirmFee(formatter.format(preview.fee)),
                loading: _busy,
                onPressed: _commit,
              ),
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                label: l10n.bkCancelKeep,
                secondary: true,
                onPressed: _busy ? null : () => context.pop(),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
