import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';

/// Opens the phone's maps app with directions to [place]. Says so when no app
/// can handle it.
Future<void> openNavigation(BuildContext context, JobPlace place) async {
  final l10n = AppLocalizations.of(context);
  final messenger = ScaffoldMessenger.of(context);
  final uri = Uri.https('www.google.com', '/maps/dir/', {
    'api': '1',
    'destination': '${place.lat},${place.lng}',
    'travelmode': 'driving',
  });
  var ok = false;
  try {
    ok = await launchUrl(uri, mode: LaunchMode.externalApplication);
  } on Object {
    ok = false;
  }
  if (!ok) {
    messenger.showSnackBar(SnackBar(content: Text(l10n.jbNavFailed)));
  }
}

/// Starts a phone call to [phone].
Future<void> callCustomer(BuildContext context, String phone) async {
  final l10n = AppLocalizations.of(context);
  final messenger = ScaffoldMessenger.of(context);
  var ok = false;
  try {
    ok = await launchUrl(Uri(scheme: 'tel', path: phone));
  } on Object {
    ok = false;
  }
  if (!ok) {
    messenger.showSnackBar(SnackBar(content: Text(l10n.jbCallFailed)));
  }
}
