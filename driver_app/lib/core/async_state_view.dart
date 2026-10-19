import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../app/l10n/generated/app_localizations.dart';

/// Renders every async UI state (Plan §6.2): loading, data, empty,
/// error with retry, offline, session expired. Widgets never see dio.
class AsyncStateView<T> extends StatelessWidget {
  const AsyncStateView({
    super.key,
    required this.value,
    required this.builder,
    required this.onRetry,
    this.isEmpty,
    this.onSignIn,
  });

  final AsyncValue<T> value;
  final Widget Function(BuildContext context, T data) builder;
  final VoidCallback onRetry;
  final bool Function(T data)? isEmpty;

  /// Shown on session-expired errors when provided.
  final VoidCallback? onSignIn;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return value.when(
      skipLoadingOnReload: true,
      loading: () => LoadingView(semanticLabel: l10n.loadingLabel),
      error: (error, _) {
        if (error is ApiException) {
          if (error.isSessionExpired) {
            return EmptyState(
              icon: Icons.lock_outline,
              title: l10n.errorSessionExpiredTitle,
              message: l10n.errorSessionExpiredBody,
              actionLabel: onSignIn == null ? null : l10n.authTitle,
              onAction: onSignIn,
            );
          }
          if (error.isOffline) {
            return ErrorState(
              offline: true,
              title: l10n.errorOfflineTitle,
              message: l10n.errorOfflineBody,
              retryLabel: l10n.actionRetry,
              onRetry: onRetry,
            );
          }
          return ErrorState(
            title: l10n.errorGenericTitle,
            message: error.requestId == null
                ? (error.message ?? l10n.errorGenericBody)
                : '${error.message ?? l10n.errorGenericBody}\n'
                      '${l10n.errorRequestId(error.requestId!)}',
            retryLabel: l10n.actionRetry,
            onRetry: onRetry,
          );
        }
        return ErrorState(
          title: l10n.errorGenericTitle,
          message: l10n.errorGenericBody,
          retryLabel: l10n.actionRetry,
          onRetry: onRetry,
        );
      },
      data: (data) => (isEmpty?.call(data) ?? false)
          ? EmptyState(title: l10n.emptyTitle)
          : builder(context, data),
    );
  }
}
