import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../support/presentation/providers/support_providers.dart';
import '../providers/safety_providers.dart';
import '../widgets/safety_texts.dart';

/// Incident report form of one job (shared form from `platform_core`).
class SafetyReportScreen extends ConsumerWidget {
  const SafetyReportScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) => IncidentReportScreen(
    providers: safetyProviders,
    strings: safetyStrings(AppLocalizations.of(context)),
    bookingId: bookingId,
    locate: safetyLocator(ref),
    onOpenIncident: (id) => context.pushReplacement(Routes.safetyIncident(id)),
  );
}

class SafetyIncidentsScreen extends ConsumerWidget {
  const SafetyIncidentsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => IncidentsScreen(
    providers: safetyProviders,
    strings: safetyStrings(AppLocalizations.of(context)),
    onOpen: (id) => context.push(Routes.safetyIncident(id)),
  );
}

/// One case. A "safety update" notification can also point at a safety SUPPORT ticket (older flow): when no case has this id the
/// ticket with it is opened instead, so the tap never ends on a dead screen.
class SafetyIncidentScreen extends ConsumerStatefulWidget {
  const SafetyIncidentScreen({super.key, required this.incidentId});

  final String incidentId;

  @override
  ConsumerState<SafetyIncidentScreen> createState() =>
      _SafetyIncidentScreenState();
}

class _SafetyIncidentScreenState extends ConsumerState<SafetyIncidentScreen> {
  bool _redirected = false;

  Future<void> _fallback() async {
    if (_redirected) return;
    _redirected = true;
    try {
      await ref.read(supportRepositoryProvider).ticket(widget.incidentId);
      if (mounted) {
        context.pushReplacement(Routes.supportTicket(widget.incidentId));
      }
    } on Object {
      _redirected = false;
    }
  }

  @override
  Widget build(BuildContext context) {
    ref.listen(safetyProviders.incident(widget.incidentId), (_, next) {
      final error = next.error;
      if (error is ApiException && error.statusCode == 404) _fallback();
    });
    return IncidentDetailScreen(
      providers: safetyProviders,
      strings: safetyStrings(AppLocalizations.of(context)),
      incidentId: widget.incidentId,
      loadFile: ref.read(safetyRepositoryProvider).loadFile,
    );
  }
}

class SafetyContactsScreen extends ConsumerWidget {
  const SafetyContactsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) => TrustedContactsScreen(
    providers: safetyProviders,
    strings: safetyStrings(AppLocalizations.of(context)),
  );
}
