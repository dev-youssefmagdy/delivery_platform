import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';

/// Post-login landing. Only reachable when authenticated (router guard); real
/// content arrives with the ride flows (P2.6). Hosts the language switch.
class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.homeTitle)),
      body: ListView(
        children: [
          ListTile(
            minTileHeight: context.density.minTouchTarget,
            leading: const Icon(Icons.language),
            title: Text(l10n.homeChangeLanguage),
            onTap: () => context.go('${Routes.language}?from=settings'),
          ),
        ],
      ),
    );
  }
}
