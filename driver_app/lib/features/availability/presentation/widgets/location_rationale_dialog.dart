import 'package:flutter/material.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Shown BEFORE the operating system asks for location access: what the
/// location is used for, when, and that a notification stays visible while the
/// app tracks in the background. Returns true when the driver continues.
Future<bool> showLocationRationale(BuildContext context) async {
  final l10n = AppLocalizations.of(context);
  final agreed = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      key: const Key('location-rationale'),
      icon: const Icon(Icons.my_location_outlined),
      title: Text(l10n.avRationaleTitle),
      content: SingleChildScrollView(child: Text(l10n.avRationaleBody)),
      actions: [
        TextButton(
          key: const Key('location-rationale-cancel'),
          onPressed: () => Navigator.of(context).pop(false),
          child: Text(l10n.avRationaleCancel),
        ),
        FilledButton(
          key: const Key('location-rationale-continue'),
          onPressed: () => Navigator.of(context).pop(true),
          child: Text(l10n.avRationaleContinue),
        ),
      ],
    ),
  );
  return agreed ?? false;
}

/// Asks the driver to confirm going offline while a job is in progress.
Future<bool> confirmOfflineWithJob(BuildContext context) async {
  final l10n = AppLocalizations.of(context);
  final confirmed = await showDialog<bool>(
    context: context,
    builder: (context) => AlertDialog(
      key: const Key('offline-confirm'),
      title: Text(l10n.avOfflineConfirmTitle),
      content: Text(l10n.avOfflineConfirmBody),
      actions: [
        TextButton(
          key: const Key('offline-confirm-stay'),
          onPressed: () => Navigator.of(context).pop(false),
          child: Text(l10n.avStayOnline),
        ),
        FilledButton(
          key: const Key('offline-confirm-go'),
          onPressed: () => Navigator.of(context).pop(true),
          child: Text(l10n.avOfflineConfirmAction),
        ),
      ],
    ),
  );
  return confirmed ?? false;
}
