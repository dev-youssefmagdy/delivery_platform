import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';
import '../providers/delivery_providers.dart';
import '../providers/job_providers.dart';
import 'countdown.dart';
import 'delivery_texts.dart';
import 'job_texts.dart';

/// The checklist items the server accepts for a checklist proof
/// (`config/courier.php`); the answers are sent as `{code, checked}`.
const checklistCodes = [
  'package_sealed',
  'package_undamaged',
  'label_matches',
  'recipient_checked',
];

Future<void> showProofCaptureSheet(BuildContext context, String bookingId, ProofType type) =>
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      useSafeArea: true,
      showDragHandle: true,
      builder: (_) => ProofCaptureSheet(bookingId: bookingId, type: type),
    );

/// Captures one proof and puts it in the offline-safe queue: a picture from
/// the camera, a signature, the checklist or the measured weight. Nothing is
/// sent from here; the queue sends it (and retries it) with a fixed
/// Idempotency-Key.
class ProofCaptureSheet extends ConsumerStatefulWidget {
  const ProofCaptureSheet({super.key, required this.bookingId, required this.type, this.camera = const CameraCapture()});

  final String bookingId;
  final ProofType type;
  final CameraCapture camera;

  @override
  ConsumerState<ProofCaptureSheet> createState() => _ProofCaptureSheetState();
}

class _ProofCaptureSheetState extends ConsumerState<ProofCaptureSheet> {
  String? _path;
  bool _busy = false;
  String? _error;
  final _checked = <String, bool>{for (final c in checklistCodes) c: false};
  final _weight = TextEditingController();
  final _note = TextEditingController();
  final _pad = SignatureController();

  @override
  void dispose() {
    _weight.dispose();
    _note.dispose();
    _pad.dispose();
    super.dispose();
  }

  Future<void> _take() async {
    final l10n = AppLocalizations.of(context);
    try {
      final path = await widget.camera.take();
      if (mounted && path != null) setState(() => _path = path);
    } on Object {
      if (mounted) setState(() => _error = l10n.dlErrCamera);
    }
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    final navigator = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    ProofInput? input;
    switch (widget.type) {
      case ProofType.photo:
        if (_path == null) {
          setState(() => _error = l10n.dlErrPhotoFirst);
          return;
        }
        input = ProofInput(type: ProofType.photo, filePath: _path, note: _note.text);
      case ProofType.signature:
        if (_pad.isEmpty) {
          setState(() => _error = l10n.dlErrSignFirst);
          return;
        }
        setState(() => _busy = true);
        final file = await _pad.exportPng();
        input = ProofInput(type: ProofType.signature, filePath: file);
      case ProofType.checklist:
        input = ProofInput(
          type: ProofType.checklist,
          checklist: [for (final e in _checked.entries) ChecklistAnswer(code: e.key, checked: e.value)],
        );
      case ProofType.weight:
        final kg = FormLogic.parseNumber(_weight.text);
        if (kg == null || kg <= 0) {
          setState(() => _error = l10n.dlErrWeight);
          return;
        }
        input = ProofInput(type: ProofType.weight, weightKg: kg.toDouble());
      default:
        return;
    }
    await ref.read(proofQueueProvider.notifier).capture(widget.bookingId, input);
    messenger.showSnackBar(SnackBar(content: Text(l10n.dlProofQueued)));
    if (navigator.mounted) navigator.pop();
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final body = switch (widget.type) {
      ProofType.photo => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          if (_path != null)
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.md),
              child: Image.file(File(_path!), height: 220, fit: BoxFit.cover, key: const Key('proof-preview')),
            ),
          const SizedBox(height: AppSpacing.sm),
          AppButton(
            key: const Key('proof-camera'),
            label: _path == null ? l10n.dlTakePhoto : l10n.dlRetakePhoto,
            icon: Icons.photo_camera_outlined,
            secondary: _path != null,
            onPressed: _busy ? null : _take,
          ),
          const SizedBox(height: AppSpacing.sm),
          TextField(
            key: const Key('proof-note'),
            controller: _note,
            maxLength: 200,
            decoration: InputDecoration(labelText: l10n.dlProofNote),
          ),
        ],
      ),
      ProofType.signature => Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l10n.dlSignHint, style: theme.textTheme.bodyMedium),
          const SizedBox(height: AppSpacing.sm),
          SignaturePad(key: const Key('signature-pad'), controller: _pad),
          TextButton(
            key: const Key('signature-clear'),
            onPressed: () => setState(_pad.clear),
            child: Text(l10n.dlSignClear),
          ),
        ],
      ),
      ProofType.checklist => Column(
        children: [
          for (final code in checklistCodes)
            CheckboxListTile(
              key: Key('check-$code'),
              contentPadding: EdgeInsets.zero,
              title: Text(checklistCodeText(l10n, code), style: theme.textTheme.titleMedium),
              value: _checked[code],
              onChanged: (v) => setState(() => _checked[code] = v ?? false),
            ),
        ],
      ),
      ProofType.weight => TextField(
        key: const Key('proof-weight'),
        controller: _weight,
        keyboardType: const TextInputType.numberWithOptions(decimal: true),
        inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩.,٫]'))],
        textDirection: TextDirection.ltr,
        style: theme.textTheme.headlineMedium,
        decoration: InputDecoration(labelText: l10n.dlWeightLabel, suffixText: l10n.dlKg),
      ),
      _ => const SizedBox.shrink(),
    };
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('proof-sheet'),
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.dlCaptureTitle(proofTypeText(l10n, widget.type)), style: theme.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            body,
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Text(_error!, key: const Key('proof-error'), style: TextStyle(color: theme.colorScheme.error)),
              ),
            const SizedBox(height: AppSpacing.md),
            AppButton(
              key: const Key('proof-save'),
              label: l10n.dlSaveProof,
              loading: _busy,
              onPressed: _busy ? null : _save,
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.dlQueueHint, style: theme.textTheme.bodySmall, textAlign: TextAlign.center),
          ],
        ),
      ),
    );
  }
}

/// Strokes of a signature and the export to a PNG file (at least 200 px per
/// side, as the server requires for a readable signature).
class SignatureController extends ChangeNotifier {
  static const exportWidth = 600.0;
  static const exportHeight = 300.0;

  final strokes = <List<Offset>>[];
  Size _size = const Size(exportWidth, exportHeight);

  bool get isEmpty => strokes.every((s) => s.length < 2);

  void begin(Offset p) {
    strokes.add([p]);
    notifyListeners();
  }

  void extend(Offset p) {
    if (strokes.isEmpty) return;
    strokes.last.add(p);
    notifyListeners();
  }

  void clear() {
    strokes.clear();
    notifyListeners();
  }

  void attach(Size size) => _size = size;

  /// Draws the strokes scaled to 600 x 300 on white and writes a PNG; returns
  /// its path.
  Future<String> exportPng() async {
    final recorder = ui.PictureRecorder();
    final canvas = Canvas(recorder);
    const target = Size(exportWidth, exportHeight);
    canvas.drawRect(Offset.zero & target, Paint()..color = const Color(0xFFFFFFFF));
    final scaleX = target.width / (_size.width == 0 ? target.width : _size.width);
    final scaleY = target.height / (_size.height == 0 ? target.height : _size.height);
    final paint = Paint()
      ..color = const Color(0xFF111111)
      ..strokeWidth = 4
      ..strokeCap = StrokeCap.round
      ..strokeJoin = StrokeJoin.round
      ..style = PaintingStyle.stroke;
    for (final stroke in strokes) {
      if (stroke.length < 2) continue;
      final path = Path()..moveTo(stroke.first.dx * scaleX, stroke.first.dy * scaleY);
      for (final p in stroke.skip(1)) {
        path.lineTo(p.dx * scaleX, p.dy * scaleY);
      }
      canvas.drawPath(path, paint);
    }
    final image = await recorder.endRecording().toImage(exportWidth.toInt(), exportHeight.toInt());
    final ByteData? data = await image.toByteData(format: ui.ImageByteFormat.png);
    final file = File('${Directory.systemTemp.path}/signature_${DateTime.now().microsecondsSinceEpoch}.png');
    await file.writeAsBytes(data!.buffer.asUint8List());
    return file.path;
  }
}

/// A drawing area for the recipient's signature.
class SignaturePad extends StatelessWidget {
  const SignaturePad({super.key, required this.controller});

  final SignatureController controller;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return AspectRatio(
      aspectRatio: 2,
      child: LayoutBuilder(
        builder: (context, box) {
          controller.attach(Size(box.maxWidth, box.maxHeight));
          return DecoratedBox(
            decoration: BoxDecoration(
              color: Colors.white,
              border: Border.all(color: theme.colorScheme.outline),
              borderRadius: BorderRadius.circular(AppRadius.md),
            ),
            child: GestureDetector(
              onPanStart: (d) => controller.begin(d.localPosition),
              onPanUpdate: (d) => controller.extend(d.localPosition),
              child: ListenableBuilder(
                listenable: controller,
                builder: (_, _) => CustomPaint(
                  painter: _SignaturePainter(controller.strokes),
                  size: Size.infinite,
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}

class _SignaturePainter extends CustomPainter {
  _SignaturePainter(this.strokes);

  final List<List<Offset>> strokes;

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = const Color(0xFF111111)
      ..strokeWidth = 3
      ..strokeCap = StrokeCap.round
      ..style = PaintingStyle.stroke;
    for (final stroke in strokes) {
      if (stroke.length < 2) continue;
      final path = Path()..moveTo(stroke.first.dx, stroke.first.dy);
      for (final p in stroke.skip(1)) {
        path.lineTo(p.dx, p.dy);
      }
      canvas.drawPath(path, paint);
    }
  }

  @override
  bool shouldRepaint(_SignaturePainter old) => true;
}

// ---------------------------------------------------------------------------
// Recipient code

Future<void> showRecipientOtpSheet(BuildContext context, Job job) => showModalBottomSheet<void>(
  context: context,
  isScrollControlled: true,
  useSafeArea: true,
  showDragHandle: true,
  builder: (_) => RecipientOtpSheet(job: job),
);

/// The recipient reads the SMS code out; the driver types it. The remaining
/// tries come from the server; a locked code only operations can lift.
class RecipientOtpSheet extends ConsumerStatefulWidget {
  const RecipientOtpSheet({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<RecipientOtpSheet> createState() => _RecipientOtpSheetState();
}

class _RecipientOtpSheetState extends ConsumerState<RecipientOtpSheet> {
  final _code = TextEditingController();
  bool _busy = false;
  bool _resending = false;
  Object? _error;

  @override
  void dispose() {
    _code.dispose();
    super.dispose();
  }

  Future<void> _verify() async {
    final navigator = Navigator.of(context);
    final digits = _code.text.trim();
    if (digits.length < 4 || _busy) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(deliveryActionsProvider).verifyOtp(widget.job.id, digits);
      if (navigator.mounted) navigator.pop();
    } on Object catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = e;
        if (e is ApiException && e.code == 'recipient_otp_invalid') _code.clear();
      });
    }
  }

  Future<void> _resend() async {
    if (_resending) return;
    setState(() {
      _resending = true;
      _error = null;
    });
    try {
      await ref.read(deliveryActionsProvider).resendOtp(widget.job.id);
    } on Object catch (e) {
      if (mounted) setState(() => _error = e);
    } finally {
      if (mounted) setState(() => _resending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final job = ref.watch(jobTrackerProvider(widget.job.id)).value ?? widget.job;
    final otp = job.delivery?.recipientOtp;
    final locked = otp?.locked ?? false;
    final resendAt = otp?.resendAvailableAt;
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('otp-sheet'),
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.dlOtpSheetTitle, style: theme.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.dlOtpAskRecipient, style: theme.textTheme.bodyMedium),
            if (otp != null && otp.attemptsRemaining != null && !locked)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                child: Text(l10n.dlOtpAttemptsLeft(otp.attemptsRemaining!), key: const Key('otp-attempts'), style: theme.textTheme.bodySmall),
              ),
            const SizedBox(height: AppSpacing.md),
            if (locked)
              Text(l10n.dlErrOtpLocked, key: const Key('otp-locked'), style: TextStyle(color: theme.colorScheme.error))
            else ...[
              TextField(
                key: const Key('otp-input'),
                controller: _code,
                autofocus: true,
                enabled: !_busy,
                keyboardType: TextInputType.number,
                textAlign: TextAlign.center,
                textDirection: TextDirection.ltr,
                maxLength: 12,
                inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩]'))],
                style: theme.textTheme.displaySmall?.copyWith(letterSpacing: 8, fontWeight: FontWeight.w700),
                decoration: InputDecoration(counterText: '', labelText: l10n.dlOtpField),
                onSubmitted: (_) => _verify(),
              ),
              if (_error != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
                  child: Text(jobErrorText(l10n, _error!), key: const Key('otp-error'), style: TextStyle(color: theme.colorScheme.error)),
                ),
              const SizedBox(height: AppSpacing.md),
              AppButton(key: const Key('otp-verify'), label: l10n.dlOtpVerify, loading: _busy, onPressed: _verify),
              const SizedBox(height: AppSpacing.sm),
              if (job.can(JobAction.resendRecipientOtp))
                resendAt == null
                    ? AppButton(key: const Key('otp-resend'), label: l10n.dlOtpResend, secondary: true, loading: _resending, onPressed: _resend)
                    : Countdown(
                        until: resendAt,
                        builder: (context, left, over) => AppButton(
                          key: const Key('otp-resend'),
                          label: over ? l10n.dlOtpResend : l10n.dlOtpResendIn(formatClock(context, left)),
                          secondary: true,
                          loading: _resending,
                          onPressed: over ? _resend : null,
                        ),
                      ),
            ],
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// Failed attempt

Future<void> showAttemptSheet(BuildContext context, Job job) => showModalBottomSheet<void>(
  context: context,
  isScrollControlled: true,
  useSafeArea: true,
  showDragHandle: true,
  builder: (_) => AttemptSheet(job: job),
);

/// "I could not deliver": the reason (a fixed list), a note where the server
/// requires one and an evidence picture. The same outcome, note and picture
/// keep one Idempotency-Key, so a retry after a timeout reports it once.
class AttemptSheet extends ConsumerStatefulWidget {
  const AttemptSheet({super.key, required this.job, this.camera = const CameraCapture()});

  final Job job;
  final CameraCapture camera;

  @override
  ConsumerState<AttemptSheet> createState() => _AttemptSheetState();
}

class _AttemptSheetState extends ConsumerState<AttemptSheet> {
  AttemptOutcome? _outcome;
  final _reason = TextEditingController();
  String? _evidence;
  bool _busy = false;
  Object? _error;
  String? _local;

  @override
  void dispose() {
    _reason.dispose();
    super.dispose();
  }

  Future<void> _photo() async {
    try {
      final path = await widget.camera.take();
      if (mounted && path != null) setState(() => _evidence = path);
    } on Object {
      if (mounted) setState(() => _local = AppLocalizations.of(context).dlErrCamera);
    }
  }

  Future<void> _send() async {
    final l10n = AppLocalizations.of(context);
    final outcome = _outcome;
    if (outcome == null) {
      setState(() => _local = l10n.dlErrOutcomeFirst);
      return;
    }
    if (outcome.needsReason && _reason.text.trim().length < 3) {
      setState(() => _local = l10n.dlErrReasonNeeded);
      return;
    }
    final navigator = Navigator.of(context);
    setState(() {
      _busy = true;
      _error = null;
      _local = null;
    });
    try {
      await ref.read(deliveryActionsProvider).reportAttempt(
        widget.job.id,
        outcome: outcome,
        reason: _reason.text.trim().isEmpty ? null : _reason.text.trim(),
        evidencePath: _evidence,
      );
      if (navigator.mounted) navigator.pop();
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
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.of(context).viewInsets.bottom + AppSpacing.md,
      ),
      child: SingleChildScrollView(
        child: Column(
          key: const Key('attempt-sheet'),
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.dlAttemptTitle, style: theme.textTheme.titleLarge),
            const SizedBox(height: AppSpacing.xs),
            Text(l10n.dlAttemptBody, style: theme.textTheme.bodyMedium),
            const SizedBox(height: AppSpacing.md),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final o in AttemptOutcome.choices)
                  ChoiceChip(
                    key: Key('attempt-${o.value}'),
                    label: Text(attemptOutcomeText(l10n, o), style: theme.textTheme.titleSmall),
                    padding: const EdgeInsetsDirectional.symmetric(horizontal: AppSpacing.sm, vertical: AppSpacing.sm),
                    selected: _outcome == o,
                    onSelected: _busy ? null : (_) => setState(() => _outcome = o),
                  ),
              ],
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              key: const Key('attempt-reason'),
              controller: _reason,
              maxLength: 500,
              minLines: 2,
              maxLines: 4,
              decoration: InputDecoration(
                labelText: (_outcome?.needsReason ?? false) ? l10n.dlReasonRequired : l10n.dlReasonOptional,
              ),
            ),
            if (_evidence != null)
              ClipRRect(
                borderRadius: BorderRadius.circular(AppRadius.md),
                child: Image.file(File(_evidence!), height: 160, fit: BoxFit.cover, key: const Key('attempt-evidence-preview')),
              ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('attempt-evidence'),
              label: _evidence == null ? l10n.dlAddEvidence : l10n.dlRetakePhoto,
              icon: Icons.photo_camera_outlined,
              secondary: true,
              onPressed: _busy ? null : _photo,
            ),
            if (_local != null || _error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Text(
                  _local ?? jobErrorText(l10n, _error!),
                  key: const Key('attempt-error'),
                  style: TextStyle(color: theme.colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.md),
            AppButton(key: const Key('attempt-send'), label: l10n.dlAttemptSend, loading: _busy, onPressed: _send),
          ],
        ),
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// After a failed attempt

Future<void> showResolveSheet(BuildContext context, Job job) => showModalBottomSheet<void>(
  context: context,
  isScrollControlled: true,
  useSafeArea: true,
  showDragHandle: true,
  builder: (_) => ResolveAttemptSheet(job: job),
);

/// What happens to the goods after a failed attempt: try again (while tries
/// are left), take them back to the sender, or hand the case to operations.
class ResolveAttemptSheet extends ConsumerStatefulWidget {
  const ResolveAttemptSheet({super.key, required this.job});

  final Job job;

  @override
  ConsumerState<ResolveAttemptSheet> createState() => _ResolveAttemptSheetState();
}

class _ResolveAttemptSheetState extends ConsumerState<ResolveAttemptSheet> {
  AttemptResolution? _busy;
  Object? _error;

  Future<void> _go(DeliveryAttempt attempt, AttemptResolution resolution) async {
    final navigator = Navigator.of(context);
    setState(() {
      _busy = resolution;
      _error = null;
    });
    try {
      await ref.read(deliveryActionsProvider).resolveAttempt(widget.job.id, attempt.id, resolution);
      if (navigator.mounted) navigator.pop();
    } on Object catch (e) {
      if (mounted) {
        setState(() {
          _busy = null;
          _error = e;
        });
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final job = ref.watch(jobTrackerProvider(widget.job.id)).value ?? widget.job;
    final attempts = job.delivery?.attempts;
    final pending = attempts?.pending;
    return Padding(
      padding: const EdgeInsetsDirectional.fromSTEB(AppSpacing.md, 0, AppSpacing.md, AppSpacing.md),
      child: Column(
        key: const Key('resolve-sheet'),
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(l10n.dlResolveTitle, style: theme.textTheme.titleLarge),
          const SizedBox(height: AppSpacing.xs),
          Text(l10n.dlResolveBody, style: theme.textTheme.bodyMedium),
          const SizedBox(height: AppSpacing.md),
          if (pending == null)
            Text(l10n.dlResolveNothing, key: const Key('resolve-nothing'))
          else ...[
            if (attempts!.retryAvailable)
              AppButton(
                key: const Key('resolve-retry'),
                label: l10n.dlResRetry,
                loading: _busy == AttemptResolution.retry,
                onPressed: _busy != null ? null : () => _go(pending, AttemptResolution.retry),
              )
            else
              Text(l10n.dlErrAttemptsExhausted, key: const Key('resolve-exhausted')),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('resolve-return'),
              label: l10n.dlResReturn,
              secondary: true,
              loading: _busy == AttemptResolution.returnToSender,
              onPressed: _busy != null ? null : () => _go(pending, AttemptResolution.returnToSender),
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              key: const Key('resolve-operations'),
              label: l10n.dlResOperations,
              secondary: true,
              loading: _busy == AttemptResolution.operations,
              onPressed: _busy != null ? null : () => _go(pending, AttemptResolution.operations),
            ),
          ],
          if (_error != null)
            Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
              child: Text(jobErrorText(l10n, _error!), key: const Key('resolve-error'), style: TextStyle(color: theme.colorScheme.error)),
            ),
        ],
      ),
    );
  }
}
