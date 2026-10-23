import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/safety_providers.dart';
import 'safety_texts.dart';

/// Safety button (SRS 13.2): opens the SOS (press and hold), the incident report, trusted contacts, trip sharing and the local emergency
/// numbers. [bookingId] is the active job; without it only the numbers, contacts and support are offered.
Future<void> showSafetySheet(
  BuildContext context, {
  String? bookingId,
  bool shareAllowed = true,
}) => showModalBottomSheet<void>(
  context: context,
  showDragHandle: true,
  isScrollControlled: true,
  useRootNavigator: true,
  builder: (_) => SafetySheet(bookingId: bookingId, shareAllowed: shareAllowed),
);

class SafetySheet extends ConsumerWidget {
  const SafetySheet({super.key, this.bookingId, this.shareAllowed = true});

  final String? bookingId;
  final bool shareAllowed;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final strings = safetyStrings(AppLocalizations.of(context));
    final router = GoRouter.of(context);
    final id = bookingId;
    return SafetySheetBody(
      providers: safetyProviders,
      strings: strings,
      bookingId: id,
      shareAllowed: shareAllowed,
      locate: safetyLocator(ref),
      supportPhone: ref.watch(appConfigProvider).value?.support.phone,
      onCall: (number) => launchUrl(Uri(scheme: 'tel', path: number)),
      onReport: id == null
          ? null
          : () {
              Navigator.of(context).pop();
              router.push(Routes.safetyReport(id));
            },
      onContacts: () {
        Navigator.of(context).pop();
        router.push(Routes.safetyContacts);
      },
      onOpenIncident: (incident) {
        Navigator.of(context).pop();
        router.push(Routes.safetyIncident(incident));
      },
      onShare: id == null
          ? null
          : () {
              Navigator.of(context).pop();
              final root = router.routerDelegate.navigatorKey.currentContext;
              if (root == null) return;
              showModalBottomSheet<void>(
                context: root,
                showDragHandle: true,
                isScrollControlled: true,
                builder: (_) => TripShareSheet(
                  providers: safetyProviders,
                  strings: strings,
                  bookingId: id,
                  onContacts: () {
                    Navigator.of(root).pop();
                    router.push(Routes.safetyContacts);
                  },
                ),
              );
            },
    );
  }
}
