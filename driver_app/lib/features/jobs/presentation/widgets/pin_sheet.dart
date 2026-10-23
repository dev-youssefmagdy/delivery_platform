import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/job_providers.dart';
import 'job_texts.dart';
import 'keypad.dart';

/// Opens the PIN pad. Returns true once the server verified the PIN.
Future<bool> showPinSheet(BuildContext context, Job job) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    showDragHandle: true,
    builder: (_) => PinSheet(job: job),
  );
  return result ?? false;
}

/// The customer reads the trip PIN out; the driver types it on a large
/// keypad. Wrong entries show the attempts left; at the limit the start is
/// locked and the driver is pointed to support.
class PinSheet extends ConsumerStatefulWidget {
  const PinSheet({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<PinSheet> createState() => _PinSheetState();
}

class _PinSheetState extends ConsumerState<PinSheet> {
  static const _minLength = 4;
  static const _maxLength = 8;

  String _pin = '';
  bool _busy = false;
  Object? _error;
  late bool _locked = widget.job.trip.pinLocked;
  late int? _attemptsLeft = widget.job.trip.pinAttemptsRemaining;

  void _digit(String d) {
    if (_busy || _locked || _pin.length >= _maxLength) return;
    setState(() {
      _pin += d;
      _error = null;
    });
  }

  void _back() {
    if (_busy || _pin.isEmpty) return;
    setState(() {
      _pin = _pin.substring(0, _pin.length - 1);
      _error = null;
    });
  }

  Future<void> _submit() async {
    if (_busy || _locked || _pin.length < _minLength) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(jobActionsProvider).verifyPin(widget.job.id, _pin);
      if (mounted) Navigator.of(context).pop(true);
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = e;
        if (e.code == 'trip_pin_locked') _locked = true;
        if (e.code == 'trip_pin_invalid') {
          final left = e.meta['attempts_remaining'];
          if (left is num) _attemptsLeft = left.toInt();
          _pin = '';
        }
      });
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = false;
          _error = e;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final String? message = _locked
        ? l10n.jbErrPinLocked
        : (_error != null ? jobErrorText(l10n, _error!) : null);
    return Padding(
      padding: const EdgeInsetsDirectional.fromSTEB(
        AppSpacing.md,
        0,
        AppSpacing.md,
        AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              l10n.jbPinTitle,
              textAlign: TextAlign.center,
              style: theme.textTheme.titleLarge,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.jbPinHint, textAlign: TextAlign.center),
            const SizedBox(height: AppSpacing.md),
            Container(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: theme.colorScheme.surfaceContainerHighest,
                borderRadius: BorderRadius.circular(AppRadius.md),
              ),
              child: IsolatedText(
                _pin.isEmpty ? '' : _pin.split('').join(' '),
                key: const Key('pin-display'),
                textAlign: TextAlign.center,
                style: theme.textTheme.displaySmall?.copyWith(
                  letterSpacing: 4,
                  fontWeight: FontWeight.w700,
                )),
            ),
            ConstrainedBox(
              constraints: const BoxConstraints(minHeight: 56),
              child: Center(
                child: message != null
                    ? Text(
                        message,
                        key: const Key('pin-error'),
                        textAlign: TextAlign.center,
                        style: TextStyle(color: theme.colorScheme.error),
                      )
                    : (_attemptsLeft != null
                          ? Text(
                              l10n.jbPinAttemptsLeft(_attemptsLeft!),
                              key: const Key('pin-attempts'),
                            )
                          : null),
              ),
            ),
            if (_locked)
              AppButton(
                key: const Key('pin-locked-close'),
                label: l10n.actionClose,
                onPressed: () => Navigator.of(context).pop(false),
              )
            else ...[
              BigKeypad(
                onDigit: _digit,
                onBackspace: _back,
                backspaceLabel: l10n.jbKeypadBackspace,
              ),
              const SizedBox(height: AppSpacing.sm),
              AppButton(
                key: const Key('pin-submit'),
                label: l10n.jbPinConfirm,
                loading: _busy,
                onPressed: _pin.length >= _minLength ? _submit : null,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
