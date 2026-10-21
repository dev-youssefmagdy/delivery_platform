import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';

/// Safety button (SRS 6.6). The real SOS flow arrives in P5.1; until then the
/// sheet is explicit about it and offers what is configured: the platform's
/// support contacts and the customer's own emergency contact.
Future<void> showSafetySheet(BuildContext context) => showModalBottomSheet<void>(
  context: context,
  showDragHandle: true,
  isScrollControlled: true,
  builder: (_) => const SafetySheet(),
);

class SafetySheet extends ConsumerWidget {
  const SafetySheet({super.key});

  Future<void> _call(String number) =>
      launchUrl(Uri(scheme: 'tel', path: number));

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final support = ref.watch(appConfigProvider).value?.support;
    final profile = ref.watch(meProvider).value?.customerProfile;
    final emergencyPhone = profile?.emergencyContactPhone;
    final hasAnything =
        (support?.phone?.trim().isNotEmpty ?? false) ||
        (emergencyPhone?.trim().isNotEmpty ?? false);
    return SafeArea(
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
            Text(l10n.bkSafetyTitle, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.sm),
            Text(l10n.bkSafetyNotice, key: const Key('safety-notice')),
            const SizedBox(height: AppSpacing.md),
            if (emergencyPhone != null && emergencyPhone.trim().isNotEmpty)
              ListTile(
                key: const Key('safety-emergency-contact'),
                leading: const Icon(Icons.contact_phone_outlined),
                title: Text(l10n.bkSafetyYourContact),
                subtitle: Text(
                  [profile?.emergencyContactName, emergencyPhone]
                      .whereType<String>()
                      .join(' · '),
                ),
                onTap: () => _call(emergencyPhone.trim()),
              ),
            if (support?.phone != null && support!.phone!.trim().isNotEmpty)
              ListTile(
                key: const Key('safety-support-phone'),
                leading: const Icon(Icons.support_agent),
                title: Text(l10n.bkSafetySupport),
                subtitle: Text(support.phone!.trim()),
                onTap: () => _call(support.phone!.trim()),
              ),
            if (support?.whatsapp != null && support!.whatsapp!.trim().isNotEmpty)
              ListTile(
                leading: const Icon(Icons.chat_outlined),
                title: Text(l10n.bkSafetyWhatsapp),
                subtitle: Text(support.whatsapp!.trim()),
              ),
            if (!hasAnything)
              Text(l10n.bkSafetyNothingConfigured),
          ],
        ),
      ),
    );
  }
}
