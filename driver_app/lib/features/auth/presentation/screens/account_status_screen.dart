import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/auth_providers.dart';
import '../widgets/auth_error_text.dart';

/// Shown instead of the app when the account cannot be used: suspended,
/// restricted (notice), deletion pending, verification required, deleted, or
/// when `/me` could not be loaded (retry).
class AccountStatusScreen extends ConsumerStatefulWidget {
  const AccountStatusScreen({super.key});

  @override
  ConsumerState<AccountStatusScreen> createState() =>
      _AccountStatusScreenState();
}

class _AccountStatusScreenState extends ConsumerState<AccountStatusScreen> {
  bool _busy = false;
  String? _error;

  Future<void> _run(Future<void> Function() action) async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await action();
    } on Object catch (e) {
      if (mounted) setState(() => _error = authErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final me = ref.watch(meProvider);
    final actions = ref.read(authActionsProvider);
    final user = me.value;

    if (user == null) {
      final error = me.error;
      return Scaffold(
        body: SafeArea(
          child: me.isLoading
              ? LoadingView(semanticLabel: l10n.loadingLabel)
              : ErrorState(
                  offline: error is ApiException && error.isOffline,
                  title: error is ApiException && error.isOffline
                      ? l10n.errorOfflineTitle
                      : l10n.errorGenericTitle,
                  message: error == null
                      ? l10n.errorGenericBody
                      : authErrorText(l10n, error),
                  retryLabel: l10n.actionRetry,
                  onRetry: () => ref.read(meProvider.notifier).reload(),
                ),
        ),
      );
    }

    final support = ref.watch(appConfigProvider).value?.support;
    final (icon, title, body) = switch (user.status) {
      UserStatus.suspended => (
        Icons.block,
        l10n.statusSuspendedTitle,
        l10n.statusSuspendedBody,
      ),
      UserStatus.restricted => (
        Icons.warning_amber_rounded,
        l10n.statusRestrictedTitle,
        l10n.statusRestrictedBody,
      ),
      UserStatus.deletionPending => (
        Icons.delete_outline,
        l10n.statusDeletionPendingTitle,
        l10n.statusDeletionPendingBody,
      ),
      UserStatus.verificationRequired => (
        Icons.verified_user_outlined,
        l10n.statusVerificationTitle,
        l10n.statusVerificationBody,
      ),
      _ => (
        Icons.person_off_outlined,
        l10n.statusDeletedTitle,
        l10n.statusDeletedBody,
      ),
    };
    final contact = support?.phone ?? support?.email;

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Icon(icon, size: 64, color: Theme.of(context).colorScheme.error),
              const SizedBox(height: AppSpacing.md),
              Text(
                title,
                key: const Key('status-title'),
                style: Theme.of(context).textTheme.headlineSmall,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: AppSpacing.sm),
              Text(body, textAlign: TextAlign.center),
              if (contact != null) ...[
                const SizedBox(height: AppSpacing.md),
                Text(
                  '${l10n.supportContactLabel}: $contact',
                  textAlign: TextAlign.center,
                ),
              ],
              if (_error != null)
                Padding(
                  padding: const EdgeInsetsDirectional.only(top: AppSpacing.md),
                  child: Text(
                    _error!,
                    textAlign: TextAlign.center,
                    style: TextStyle(
                      color: Theme.of(context).colorScheme.error,
                    ),
                  ),
                ),
              const SizedBox(height: AppSpacing.lg),
              if (user.status == UserStatus.restricted)
                AppButton(
                  label: l10n.actionContinue,
                  onPressed: () =>
                      ref.read(restrictedAckProvider.notifier).acknowledge(),
                ),
              if (user.status == UserStatus.deletionPending &&
                  user.deletionRequest != null) ...[
                AppButton(
                  key: const Key('cancel-deletion'),
                  label: l10n.statusCancelDeletion,
                  loading: _busy,
                  onPressed: () => _run(
                    () => actions.cancelDeletion(user.deletionRequest!.id),
                  ),
                ),
                const SizedBox(height: AppSpacing.sm),
              ],
              AppButton(
                key: const Key('status-logout'),
                label: l10n.homeLogout,
                secondary: true,
                onPressed: _busy ? null : () => _run(actions.logout),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
