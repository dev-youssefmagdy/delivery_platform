import 'package:flutter/material.dart';

import 'components.dart';
import 'tokens.dart';

/// Full-screen, non-dismissible notice used for maintenance mode and forced
/// updates. Pure presentation: all text comes from the caller (ARB strings).
class BlockingNotice extends StatelessWidget {
  const BlockingNotice({
    super.key,
    required this.icon,
    required this.title,
    required this.message,
    this.details = const [],
    this.actionLabel,
    this.onAction,
  });

  final IconData icon;
  final String title;
  final String message;

  /// Extra lines under the message (support contacts, required version).
  final List<String> details;
  final String? actionLabel;
  final VoidCallback? onAction;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Scaffold(
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsetsDirectional.all(AppSpacing.lg),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(icon, size: 72, color: theme.colorScheme.primary),
                const SizedBox(height: AppSpacing.lg),
                Text(
                  title,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.headlineSmall,
                ),
                const SizedBox(height: AppSpacing.sm),
                Text(
                  message,
                  textAlign: TextAlign.center,
                  style: theme.textTheme.bodyLarge,
                ),
                for (final line in details) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Directionality(
                    textDirection: TextDirection.ltr,
                    child: SelectableText(
                      line,
                      textAlign: TextAlign.center,
                      style: theme.textTheme.bodyMedium,
                    ),
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
        ),
      ),
    );
  }
}
