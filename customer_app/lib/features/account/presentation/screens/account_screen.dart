import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../auth/presentation/widgets/auth_error_text.dart';
import '../../../notifications/presentation/providers/notification_providers.dart';

/// Account tab: language, saved places, sign-out and account deletion.
class AccountScreen extends ConsumerStatefulWidget {
  const AccountScreen({super.key});

  @override
  ConsumerState<AccountScreen> createState() => _AccountScreenState();
}

class _AccountScreenState extends ConsumerState<AccountScreen> {
  // One key per deletion intent, kept across retries (Plan 6.2).
  String? _deletionKey;

  Future<void> _delete() async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showConfirmSheet(
      context,
      title: l10n.deleteAccountTitle,
      message: l10n.deleteAccountBody,
      confirmLabel: l10n.deleteAccountConfirm,
      cancelLabel: l10n.actionCancel,
      destructive: true,
    );
    if (!confirmed || !mounted) return;
    final key = _deletionKey ??= newIdempotencyKey();
    try {
      await ref.read(authActionsProvider).requestDeletion(key);
      _deletionKey = null;
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(authErrorText(l10n, e))));
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final name = ref.watch(meProvider).value?.name;
    return Scaffold(
      appBar: AppBar(title: Text(l10n.navAccount)),
      body: ListView(
        children: [
          if (name != null) ListTile(title: Text(l10n.homeGreeting(name))),
          ListTile(
            key: const Key('home-saved-places'),
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.bookmarks_outlined),
            title: Text(l10n.homeSavedPlaces),
            onTap: () => context.push(Routes.savedPlaces),
          ),
          ListTile(
            key: const Key('home-safety'),
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.shield_outlined),
            title: Text(l10n.sfSettingsTile),
            onTap: () => context.push(Routes.safetyContacts),
          ),
          ListTile(
            key: const Key('home-notifications'),
            minTileHeight: context.density.minTouchTarget,
            leading: Badge(
              isLabelVisible: ref.watch(unreadCountProvider) > 0,
              label: Text('${ref.watch(unreadCountProvider)}'),
              child: const Icon(Icons.notifications_outlined),
            ),
            title: Text(l10n.accountNotifications),
            onTap: () => context.push(Routes.notifications),
          ),
          ListTile(
            key: const Key('home-support'),
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.support_agent_outlined),
            title: Text(l10n.supportHelp),
            onTap: () => context.push(Routes.support),
          ),
          ListTile(
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.language),
            title: Text(l10n.homeChangeLanguage),
            onTap: () => context.go('${Routes.language}?from=settings'),
          ),
          ListTile(
            key: const Key('home-logout'),
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.logout),
            title: Text(l10n.homeLogout),
            onTap: () => ref.read(authActionsProvider).logout(),
          ),
          ListTile(
            key: const Key('home-delete-account'),
            minTileHeight: context.density.minTouchTarget,
            leading: Icon(
              Icons.delete_outline,
              color: Theme.of(context).colorScheme.error,
            ),
            title: Text(l10n.homeDeleteAccount),
            onTap: _delete,
          ),
        ],
      ),
    );
  }
}
