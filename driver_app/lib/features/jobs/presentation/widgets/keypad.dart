import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:platform_core/platform_core.dart';

/// Large numeric keypad for the driving context: 72 px keys, no system
/// keyboard. [onDigit] gets `0`-`9`; [onDecimal] (when given) adds a decimal
/// key; [onBackspace] removes the last character.
class BigKeypad extends StatelessWidget {
  const BigKeypad({
    super.key,
    required this.onDigit,
    required this.onBackspace,
    this.onDecimal,
    this.backspaceLabel,
    this.decimalLabel,
  });

  final ValueChanged<String> onDigit;
  final VoidCallback onBackspace;
  final VoidCallback? onDecimal;
  final String? backspaceLabel;
  final String? decimalLabel;

  Widget _key(BuildContext context, String label, VoidCallback? onTap, {
    Key? key,
    IconData? icon,
    String? semantics,
  }) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.all(AppSpacing.xs),
      child: ConstrainedBox(
        constraints: const BoxConstraints(minHeight: 72),
        child: Semantics(
          button: true,
          label: semantics ?? label,
          excludeSemantics: true,
          child: Material(
            color: theme.colorScheme.surfaceContainerHighest,
            borderRadius: BorderRadius.circular(AppRadius.md),
            child: InkWell(
              key: key,
              borderRadius: BorderRadius.circular(AppRadius.md),
              onTap: onTap == null
                  ? null
                  : () {
                      HapticFeedback.selectionClick();
                      onTap();
                    },
              child: Center(
                child: icon != null
                    ? Icon(icon, size: 30)
                    : IsolatedText(
                        label,
                        style: theme.textTheme.headlineMedium),
              ),
            ),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    Widget row(List<Widget> keys) =>
        Row(children: [for (final k in keys) Expanded(child: k)]);
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          for (final r in const [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
          ])
            row([
              for (final d in r)
                _key(context, d, () => onDigit(d), key: Key('key-$d')),
            ]),
          row([
            onDecimal == null
                ? const SizedBox(height: 72)
                : _key(
                    context,
                    '.',
                    onDecimal,
                    key: const Key('key-decimal'),
                    semantics: decimalLabel,
                  ),
            _key(context, '0', () => onDigit('0'), key: const Key('key-0')),
            _key(
              context,
              '⌫',
              onBackspace,
              key: const Key('key-backspace'),
              icon: Icons.backspace_outlined,
              semantics: backspaceLabel,
            ),
          ]),
        ],
      ),
    );
  }
}
