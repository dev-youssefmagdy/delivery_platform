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
import '../widgets/confirm_sheet.dart';
import '../widgets/job_texts.dart';

/// Cancel a job: the reasons the policy allows, the server's preview of what
/// it costs (shown before anything is written), then the confirmation.
class CancelJobScreen extends ConsumerStatefulWidget {
  const CancelJobScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  ConsumerState<CancelJobScreen> createState() => _CancelJobScreenState();
}

class _CancelJobScreenState extends ConsumerState<CancelJobScreen> {
  DriverCancelReason? _reason;
  final _note = TextEditingController();
  CancellationPreview? _preview;
  bool _previewing = false;
  bool _busy = false;
  Object? _error;
  int _previewToken = 0;

  @override
  void dispose() {
    _note.dispose();
    super.dispose();
  }

  bool get _needsNote => _reason == DriverCancelReason.other;

  bool get _noteOk => !_needsNote || _note.text.trim().isNotEmpty;

  Future<void> _loadPreview() async {
    final reason = _reason;
    if (reason == null) return;
    final token = ++_previewToken;
    setState(() {
      _previewing = true;
      _preview = null;
      _error = null;
    });
    try {
      final preview = await ref
          .read(jobActionsProvider)
          .previewCancel(
            widget.bookingId,
            reason,
            note: _note.text.trim().isEmpty ? null : _note.text.trim(),
          );
      if (mounted && token == _previewToken) {
        setState(() => _preview = preview);
      }
    } on Object catch (e) {
      if (mounted && token == _previewToken) setState(() => _error = e);
    } finally {
      if (mounted && token == _previewToken) {
        setState(() => _previewing = false);
      }
    }
  }

  Future<void> _confirm(Job job) async {
    final reason = _reason;
    if (_busy || reason == null || _preview == null || !_noteOk) return;
    final l10n = AppLocalizations.of(context);
    final ok = await showJobConfirm(
      context,
      title: l10n.jbCancelConfirmTitle,
      body: job.terms.redispatch
          ? l10n.jbCancelConfirmRedispatch
          : l10n.jbCancelConfirmBody,
      confirmLabel: l10n.jbCancelConfirmAction,
      destructive: true,
    );
    if (!ok || !mounted) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      final outcome = await ref
          .read(jobActionsProvider)
          .cancel(
            widget.bookingId,
            reason,
            note: _note.text.trim().isEmpty ? null : _note.text.trim(),
          );
      if (!mounted) return;
      final messenger = ScaffoldMessenger.of(context);
      messenger.showSnackBar(
        SnackBar(
          content: Text(
            outcome.redispatched
                ? l10n.jbCancelledRedispatch
                : l10n.jbCancelledDone,
          ),
        ),
      );
      if (outcome.redispatched || outcome.job == null) {
        context.go(Routes.providerOnboarding);
      } else {
        context.pop();
      }
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() => _error = e);
      if (e.kind == ApiFailureKind.conflict) {
        await ref.read(jobTrackerProvider(widget.bookingId).notifier).refresh();
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
    final job = ref.watch(jobTrackerProvider(widget.bookingId));

    return Scaffold(
      appBar: AppBar(title: Text(l10n.jbCancelTitle)),
      body: SafeArea(
        child: AsyncStateView<Job>(
          value: job,
          onRetry: () => ref
              .read(jobTrackerProvider(widget.bookingId).notifier)
              .reload(),
          onSignIn: () => ref.read(authActionsProvider).logout(),
          builder: (context, data) {
            if (!data.can(JobAction.cancel)) {
              return EmptyState(
                key: const Key('cancel-not-allowed'),
                icon: Icons.block,
                title: l10n.jbErrNotCancellable,
                actionLabel: l10n.actionBack,
                onAction: () => context.pop(),
              );
            }
            final reasons = data.terms.reasons
                .where((r) => r != DriverCancelReason.unknown)
                .toList();
            final choices = reasons.isEmpty
                ? DriverCancelReason.choices
                : reasons;
            final preview = _preview;
            return ListView(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              children: [
                Text(l10n.jbCancelPrompt, style: theme.textTheme.titleMedium),
                const SizedBox(height: AppSpacing.sm),
                RadioGroup<DriverCancelReason>(
                  groupValue: _reason,
                  onChanged: (v) {
                    if (_busy) return;
                    setState(() => _reason = v);
                    if (v != DriverCancelReason.other) _loadPreview();
                  },
                  child: Column(
                    children: [
                      for (final r in choices)
                        RadioListTile<DriverCancelReason>(
                          key: Key('cancel-reason-${r.value}'),
                          value: r,
                          title: Text(
                            driverCancelReasonText(l10n, r),
                            style: theme.textTheme.titleMedium,
                          ),
                        ),
                    ],
                  ),
                ),
                if (_needsNote) ...[
                  TextField(
                    key: const Key('cancel-note'),
                    controller: _note,
                    maxLines: 3,
                    maxLength: 300,
                    onChanged: (_) => setState(() {}),
                    decoration: InputDecoration(labelText: l10n.jbCancelNote),
                  ),
                  AppButton(
                    key: const Key('cancel-preview'),
                    label: l10n.jbCancelShowCost,
                    secondary: true,
                    onPressed: _noteOk && !_previewing ? _loadPreview : null,
                  ),
                ],
                const SizedBox(height: AppSpacing.md),
                if (_previewing)
                  LoadingView(semanticLabel: l10n.loadingLabel)
                else if (preview != null)
                  Card(
                    key: const Key('cancel-preview-card'),
                    color: preview.free
                        ? theme.colorScheme.surfaceContainerHighest
                        : theme.colorScheme.errorContainer,
                    child: Padding(
                      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            preview.free || preview.fee == null
                                ? l10n.jbCancelFree
                                : l10n.jbCancelFee(formatter.format(preview.fee!)),
                            style: theme.textTheme.titleMedium,
                          ),
                          if (preview.message != null &&
                              preview.message!.isNotEmpty)
                            Padding(
                              padding: const EdgeInsetsDirectional.only(
                                top: AppSpacing.xs,
                              ),
                              child: Text(preview.message!),
                            ),
                          if (data.terms.redispatch)
                            Padding(
                              padding: const EdgeInsetsDirectional.only(
                                top: AppSpacing.xs,
                              ),
                              child: Text(l10n.jbCancelRedispatchNote),
                            ),
                        ],
                      ),
                    ),
                  ),
                if (_error != null)
                  Padding(
                    padding: const EdgeInsetsDirectional.only(
                      top: AppSpacing.sm,
                    ),
                    child: Text(
                      jobErrorText(l10n, _error!),
                      key: const Key('cancel-error'),
                      style: TextStyle(color: theme.colorScheme.error),
                    ),
                  ),
                const SizedBox(height: AppSpacing.md),
                AppButton(
                  key: const Key('cancel-confirm'),
                  label: l10n.jbCancelConfirmAction,
                  loading: _busy,
                  onPressed: preview != null && _noteOk
                      ? () => _confirm(data)
                      : null,
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}
