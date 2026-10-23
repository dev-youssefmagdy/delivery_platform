import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:url_launcher/url_launcher.dart';

import '../ui/components.dart';
import '../ui/tokens.dart';
import 'safety_models.dart';
import 'safety_strings.dart';

/// Hold-to-activate emergency button (SRS 13.2): a ring fills while the finger stays down for [holdSeconds]; releasing early
/// cancels without any effect, so a pocket tap can never raise an alert, while a real emergency needs one deliberate press and
/// no confirmation dialog. TalkBack / VoiceOver users activate it with the standard long-press gesture (exposed as a semantic
/// action). Large (at least 120 dp) and high contrast.
class SosHoldButton extends StatefulWidget {
  const SosHoldButton({
    super.key,
    required this.label,
    required this.hint,
    required this.holdingLabel,
    required this.holdSeconds,
    required this.onActivated,
    this.enabled = true,
  });

  final String label;
  final String hint;
  final String holdingLabel;
  final int holdSeconds;
  final VoidCallback onActivated;
  final bool enabled;

  @override
  State<SosHoldButton> createState() => _SosHoldButtonState();
}

class _SosHoldButtonState extends State<SosHoldButton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller =
      AnimationController(
        vsync: this,
        duration: Duration(seconds: widget.holdSeconds),
      )..addStatusListener((status) {
        if (status == AnimationStatus.completed) _activate();
      });
  bool _holding = false;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _activate() {
    HapticFeedback.heavyImpact();
    setState(() => _holding = false);
    _controller.reset();
    widget.onActivated();
  }

  void _start() {
    if (!widget.enabled) return;
    HapticFeedback.selectionClick();
    setState(() => _holding = true);
    _controller.forward(from: 0);
  }

  void _cancel() {
    if (!_holding) return;
    setState(() => _holding = false);
    _controller.reset();
  }

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context);
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        Semantics(
          button: true,
          enabled: widget.enabled,
          label: '${widget.label}. ${widget.hint}',
          onLongPress: widget.enabled ? _activate : null,
          excludeSemantics: true,
          child: Listener(
            onPointerDown: (_) => _start(),
            onPointerUp: (_) => _cancel(),
            onPointerCancel: (_) => _cancel(),
            child: AnimatedBuilder(
              animation: _controller,
              builder: (context, _) => SizedBox.square(
                dimension: 150,
                child: Stack(
                  alignment: Alignment.center,
                  children: [
                    SizedBox.square(
                      dimension: 150,
                      child: CircularProgressIndicator(
                        value: _controller.value,
                        strokeWidth: 8,
                        color: Colors.white,
                        backgroundColor: AppColors.danger.withValues(
                          alpha: 0.25,
                        ),
                      ),
                    ),
                    Container(
                      width: 124,
                      height: 124,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: widget.enabled
                            ? AppColors.danger
                            : scheme.disabledColor,
                        boxShadow: [
                          BoxShadow(
                            color: AppColors.danger.withValues(
                              alpha: _holding ? 0.55 : 0.25,
                            ),
                            blurRadius: _holding ? 24 : 10,
                          ),
                        ],
                      ),
                      alignment: Alignment.center,
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.sos_rounded,
                            color: Colors.white,
                            size: 44,
                          ),
                          Text(
                            _holding ? widget.holdingLabel : widget.label,
                            style: const TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w800,
                              fontSize: 15,
                            ),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(
          widget.hint,
          style: Theme.of(context).textTheme.bodySmall,
          textAlign: TextAlign.center,
        ),
      ],
    );
  }
}

/// Local emergency numbers with a call button each. Calling opens the phone's dialler; the platform never calls for the person.
class EmergencyNumbersCard extends StatelessWidget {
  const EmergencyNumbersCard({
    super.key,
    required this.numbers,
    required this.strings,
    this.onCall,
  });

  final List<EmergencyNumber> numbers;
  final SafetyStrings strings;

  /// Test seam; defaults to opening the dialler.
  final Future<void> Function(String number)? onCall;

  Future<void> _call(String number) async {
    if (onCall != null) return onCall!(number);
    await launchUrl(Uri(scheme: 'tel', path: number));
  }

  @override
  Widget build(BuildContext context) {
    if (numbers.isEmpty) return const SizedBox.shrink();
    return Card(
      key: const Key('emergency-numbers'),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(
              strings.emergencyNumbersTitle,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.sm),
            for (final n in numbers)
              Padding(
                padding: const EdgeInsets.only(bottom: AppSpacing.sm),
                child: AppButton(
                  key: Key('call-${n.key}'),
                  label: strings.callNumber(
                    n.label ?? strings.emergencyNumberName(n.key),
                    n.number,
                  ),
                  icon: Icons.call,
                  secondary: true,
                  onPressed: () => _call(n.number),
                ),
              ),
            Text(
              strings.noDispatchNotice,
              key: const Key('no-dispatch-notice'),
              style: Theme.of(context).textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }
}

/// The honest list of what happened after an alert or a report, exactly as the server reported it.
class SafetyActionList extends StatelessWidget {
  const SafetyActionList({
    super.key,
    required this.actions,
    required this.title,
  });

  final List<SafetyAction> actions;
  final String title;

  IconData _icon(SafetyAction a) => switch (a.status) {
    'done' => Icons.check_circle,
    'queued' || 'partial' => Icons.schedule,
    'not_contacted' => Icons.info_outline,
    _ => Icons.remove_circle_outline,
  };

  Color _color(SafetyAction a) => switch (a.status) {
    'done' => AppColors.success,
    'queued' || 'partial' => AppColors.warning,
    'not_contacted' => AppColors.danger,
    _ => AppColors.neutral,
  };

  @override
  Widget build(BuildContext context) {
    if (actions.isEmpty) return const SizedBox.shrink();
    return Column(
      key: const Key('safety-actions'),
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        Text(title, style: Theme.of(context).textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        for (final a in actions)
          Padding(
            padding: const EdgeInsets.only(bottom: AppSpacing.sm),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(_icon(a), color: _color(a), size: 22),
                const SizedBox(width: AppSpacing.sm),
                Expanded(child: Text(a.message)),
              ],
            ),
          ),
      ],
    );
  }
}
