import 'dart:async';

import 'package:flutter/material.dart';

import '../money/money.dart';
import '../money/money_formatter.dart';
import 'tokens.dart';

/// Primary action button with a loading state (disables itself while busy so a
/// double tap can never double-submit).
class AppButton extends StatelessWidget {
  const AppButton({
    super.key,
    required this.label,
    required this.onPressed,
    this.loading = false,
    this.secondary = false,
    this.icon,
    this.expand = true,
  });

  final String label;
  final VoidCallback? onPressed;
  final bool loading;
  final bool secondary;
  final IconData? icon;
  final bool expand;

  @override
  Widget build(BuildContext context) {
    final child = loading
        ? const SizedBox.square(
            dimension: 22,
            child: CircularProgressIndicator(strokeWidth: 2.5),
          )
        : Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (icon != null) ...[
                Icon(icon),
                const SizedBox(width: AppSpacing.sm),
              ],
              Flexible(child: Text(label, textAlign: TextAlign.center)),
            ],
          );
    final handler = loading ? null : onPressed;
    final button = secondary
        ? OutlinedButton(onPressed: handler, child: child)
        : FilledButton(onPressed: handler, child: child);
    return Semantics(
      button: true,
      label: label,
      child: expand ? SizedBox(width: double.infinity, child: button) : button,
    );
  }
}

class AppTextField extends StatelessWidget {
  const AppTextField({
    super.key,
    required this.label,
    this.controller,
    this.hint,
    this.errorText,
    this.keyboardType,
    this.textInputAction,
    this.obscureText = false,
    this.onChanged,
    this.onSubmitted,
    this.maxLines = 1,
    this.prefixIcon,
    this.enabled = true,
    this.textDirection,
  });

  final String label;
  final TextEditingController? controller;
  final String? hint;
  final String? errorText;
  final TextInputType? keyboardType;
  final TextInputAction? textInputAction;
  final bool obscureText;
  final ValueChanged<String>? onChanged;
  final ValueChanged<String>? onSubmitted;
  final int maxLines;
  final Widget? prefixIcon;
  final bool enabled;
  final TextDirection? textDirection;

  @override
  Widget build(BuildContext context) => TextField(
    controller: controller,
    enabled: enabled,
    obscureText: obscureText,
    keyboardType: keyboardType,
    textInputAction: textInputAction,
    maxLines: obscureText ? 1 : maxLines,
    textDirection: textDirection,
    onChanged: onChanged,
    onSubmitted: onSubmitted,
    decoration: InputDecoration(
      labelText: label,
      hintText: hint,
      errorText: errorText,
      prefixIcon: prefixIcon,
    ),
  );
}

/// Money rendered with [MoneyFormatter]; numbers always read left-to-right.
class MoneyText extends StatelessWidget {
  const MoneyText({
    super.key,
    required this.money,
    required this.formatter,
    this.style,
  });

  final Money money;
  final MoneyFormatter formatter;
  final TextStyle? style;

  @override
  Widget build(BuildContext context) => Text(
    formatter.format(money),
    style: style ?? Theme.of(context).textTheme.titleMedium,
    semanticsLabel: formatter.format(money),
  );
}

enum ChipTone { neutral, success, warning, danger, info }

class StatusChip extends StatelessWidget {
  const StatusChip({
    super.key,
    required this.label,
    this.tone = ChipTone.neutral,
  });

  final String label;
  final ChipTone tone;

  Color get _color => switch (tone) {
    ChipTone.success => AppColors.success,
    ChipTone.warning => AppColors.warning,
    ChipTone.danger => AppColors.danger,
    ChipTone.info => AppColors.info,
    ChipTone.neutral => AppColors.neutral,
  };

  @override
  Widget build(BuildContext context) => DecoratedBox(
    decoration: BoxDecoration(
      color: _color.withValues(alpha: 0.12),
      borderRadius: BorderRadius.circular(AppRadius.lg),
    ),
    child: Padding(
      padding: const EdgeInsetsDirectional.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.xs + 2,
      ),
      child: Text(
        label,
        style: Theme.of(context).textTheme.labelMedium
            ?.copyWith(color: _color, fontWeight: FontWeight.w600),
      ),
    ),
  );
}

class TimelineStep {
  const TimelineStep({required this.title, this.subtitle, this.done = false});
  final String title;
  final String? subtitle;
  final bool done;
}

/// Vertical timeline; the rail is on the start side (flips in RTL).
class Timeline extends StatelessWidget {
  const Timeline({super.key, required this.steps});
  final List<TimelineStep> steps;

  @override
  Widget build(BuildContext context) {
    final scheme = Theme.of(context).colorScheme;
    return Column(
      children: [
        for (var i = 0; i < steps.length; i++)
          IntrinsicHeight(
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                SizedBox(
                  width: 28,
                  child: Column(
                    children: [
                      Icon(
                        steps[i].done
                            ? Icons.check_circle
                            : Icons.radio_button_unchecked,
                        size: 20,
                        color: steps[i].done ? scheme.primary : scheme.outline,
                      ),
                      if (i != steps.length - 1)
                        Expanded(
                          child: VerticalDivider(
                            width: 2,
                            thickness: 2,
                            color: steps[i].done
                                ? scheme.primary
                                : scheme.outlineVariant,
                          ),
                        ),
                    ],
                  ),
                ),
                Expanded(
                  child: Padding(
                    padding: const EdgeInsetsDirectional.only(
                      start: AppSpacing.sm,
                      bottom: AppSpacing.md,
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          steps[i].title,
                          style: Theme.of(context).textTheme.titleSmall,
                        ),
                        if (steps[i].subtitle != null)
                          Text(
                            steps[i].subtitle!,
                            style: Theme.of(context).textTheme.bodySmall,
                          ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }
}

/// Base card for offers: provider identity on the start side, price on the
/// end side, actions below. Feature code composes it (P2.x).
class OfferCard extends StatelessWidget {
  const OfferCard({
    super.key,
    required this.title,
    required this.price,
    this.subtitle,
    this.leading,
    this.actions = const [],
  });

  final String title;
  final String? subtitle;
  final Widget price;
  final Widget? leading;
  final List<Widget> actions;

  @override
  Widget build(BuildContext context) => Card(
    child: Padding(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              if (leading != null) ...[
                leading!,
                const SizedBox(width: AppSpacing.md),
              ],
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(title, style: Theme.of(context).textTheme.titleMedium),
                    if (subtitle != null)
                      Text(
                        subtitle!,
                        style: Theme.of(context).textTheme.bodySmall,
                      ),
                  ],
                ),
              ),
              price,
            ],
          ),
          if (actions.isNotEmpty) ...[
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                for (var i = 0; i < actions.length; i++) ...[
                  if (i > 0) const SizedBox(width: AppSpacing.sm),
                  Expanded(child: actions[i]),
                ],
              ],
            ),
          ],
        ],
      ),
    ),
  );
}

class EmptyState extends StatelessWidget {
  const EmptyState({
    super.key,
    required this.title,
    this.message,
    this.icon = Icons.inbox_outlined,
    this.actionLabel,
    this.onAction,
  });

  final String title;
  final String? message;
  final IconData icon;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) => _CenteredState(
    icon: icon,
    title: title,
    message: message,
    actionLabel: actionLabel,
    onAction: onAction,
  );
}

/// Error state with a retry action. Pass `offline: true` for connectivity
/// failures to use the offline icon.
class ErrorState extends StatelessWidget {
  const ErrorState({
    super.key,
    required this.title,
    required this.retryLabel,
    required this.onRetry,
    this.message,
    this.offline = false,
  });

  final String title;
  final String? message;
  final String retryLabel;
  final VoidCallback onRetry;
  final bool offline;

  @override
  Widget build(BuildContext context) => _CenteredState(
    icon: offline ? Icons.wifi_off_rounded : Icons.error_outline_rounded,
    title: title,
    message: message,
    actionLabel: retryLabel,
    onAction: onRetry,
    iconColor: Theme.of(context).colorScheme.error,
  );
}

class _CenteredState extends StatelessWidget {
  const _CenteredState({
    required this.icon,
    required this.title,
    this.message,
    this.actionLabel,
    this.onAction,
    this.iconColor,
  });

  final IconData icon;
  final String title;
  final String? message;
  final String? actionLabel;
  final VoidCallback? onAction;
  final Color? iconColor;

  @override
  Widget build(BuildContext context) => Center(
    child: Padding(
      padding: const EdgeInsetsDirectional.all(AppSpacing.lg),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 56,
            color: iconColor ?? Theme.of(context).colorScheme.outline,
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            title,
            textAlign: TextAlign.center,
            style: Theme.of(context).textTheme.titleMedium,
          ),
          if (message != null) ...[
            const SizedBox(height: AppSpacing.sm),
            Text(
              message!,
              textAlign: TextAlign.center,
              style: Theme.of(context).textTheme.bodyMedium,
            ),
          ],
          if (actionLabel != null && onAction != null) ...[
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              label: actionLabel!,
              onPressed: onAction,
              expand: false,
              secondary: true,
            ),
          ],
        ],
      ),
    ),
  );
}

/// Centered progress indicator used as the generic loading state.
class LoadingView extends StatelessWidget {
  const LoadingView({super.key, this.semanticLabel});
  final String? semanticLabel;

  @override
  Widget build(BuildContext context) =>
      Center(child: CircularProgressIndicator(semanticsLabel: semanticLabel));
}

/// Shimmer-free pulsing placeholder block.
class Skeleton extends StatefulWidget {
  const Skeleton({super.key, this.width, this.height = 16, this.radius = 8});
  final double? width;
  final double height;
  final double radius;

  @override
  State<Skeleton> createState() => _SkeletonState();
}

class _SkeletonState extends State<Skeleton>
    with SingleTickerProviderStateMixin {
  late final AnimationController _c = AnimationController(
    vsync: this,
    duration: const Duration(milliseconds: 900),
  )..repeat(reverse: true);

  @override
  void dispose() {
    _c.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final base = Theme.of(context).colorScheme.surfaceContainerHighest;
    return ExcludeSemantics(
      child: AnimatedBuilder(
        animation: _c,
        builder: (_, _) => Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            color: base.withValues(alpha: 0.5 + 0.5 * _c.value),
            borderRadius: BorderRadius.circular(widget.radius),
          ),
        ),
      ),
    );
  }
}

/// Banner shown when connectivity is lost.
class OfflineBanner extends StatelessWidget {
  const OfflineBanner({super.key, required this.message});
  final String message;

  @override
  Widget build(BuildContext context) => Semantics(
    liveRegion: true,
    child: Material(
      color: Theme.of(context).colorScheme.errorContainer,
      child: SafeArea(
        bottom: false,
        child: Padding(
          padding: const EdgeInsetsDirectional.symmetric(
            horizontal: AppSpacing.md,
            vertical: AppSpacing.sm,
          ),
          child: Row(
            children: [
              Icon(
                Icons.wifi_off_rounded,
                size: 18,
                color: Theme.of(context).colorScheme.onErrorContainer,
              ),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                child: Text(
                  message,
                  style: TextStyle(
                    color: Theme.of(context).colorScheme.onErrorContainer,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    ),
  );
}

/// Bottom sheet asking the user to confirm. Resolves true on confirm.
Future<bool> showConfirmSheet(
  BuildContext context, {
  required String title,
  required String confirmLabel,
  required String cancelLabel,
  String? message,
  bool destructive = false,
}) async {
  final result = await showModalBottomSheet<bool>(
    context: context,
    showDragHandle: true,
    builder: (ctx) => SafeArea(
      child: Padding(
        padding: const EdgeInsetsDirectional.fromSTEB(
          AppSpacing.md,
          0,
          AppSpacing.md,
          AppSpacing.md,
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(title, style: Theme.of(ctx).textTheme.titleLarge),
            if (message != null) ...[
              const SizedBox(height: AppSpacing.sm),
              Text(message),
            ],
            const SizedBox(height: AppSpacing.lg),
            FilledButton(
              style: destructive
                  ? FilledButton.styleFrom(
                      backgroundColor: Theme.of(ctx).colorScheme.error,
                    )
                  : null,
              onPressed: () => Navigator.of(ctx).pop(true),
              child: Text(confirmLabel),
            ),
            const SizedBox(height: AppSpacing.sm),
            OutlinedButton(
              onPressed: () => Navigator.of(ctx).pop(false),
              child: Text(cancelLabel),
            ),
          ],
        ),
      ),
    ),
  );
  return result ?? false;
}

class RatingStars extends StatelessWidget {
  const RatingStars({
    super.key,
    required this.value,
    this.onChanged,
    this.size = 32,
    this.semanticLabel,
  });

  final int value;
  final ValueChanged<int>? onChanged;
  final double size;
  final String? semanticLabel;

  @override
  Widget build(BuildContext context) => Semantics(
    label: semanticLabel,
    value: '$value/5',
    child: Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        for (var i = 1; i <= 5; i++)
          IconButton(
            iconSize: size,
            onPressed: onChanged == null ? null : () => onChanged!(i),
            icon: Icon(
              i <= value ? Icons.star_rounded : Icons.star_outline_rounded,
              color: AppColors.accent,
            ),
          ),
      ],
    ),
  );
}

/// Debounces rapid calls (search boxes etc.).
class Debouncer {
  Debouncer(this.delay);
  final Duration delay;
  Timer? _timer;
  void run(VoidCallback action) {
    _timer?.cancel();
    _timer = Timer(delay, action);
  }

  void dispose() => _timer?.cancel();
}
