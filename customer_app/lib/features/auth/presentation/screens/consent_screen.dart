import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/auth_providers.dart';
import '../widgets/auth_error_text.dart';

String policyLabel(AppLocalizations l10n, String type) => switch (type) {
  'terms' => l10n.consentPolicyTerms,
  'privacy' => l10n.consentPolicyPrivacy,
  _ => l10n.consentPolicyOther,
};

/// Terms/privacy acceptance. The consent is stored against the exact policy
/// versions listed in `/me`; marketing is a separate, optional choice.
class ConsentScreen extends ConsumerStatefulWidget {
  const ConsentScreen({super.key});

  @override
  ConsumerState<ConsentScreen> createState() => _ConsentScreenState();
}

class _ConsentScreenState extends ConsumerState<ConsentScreen> {
  bool _accepted = false;
  bool _marketing = false;
  bool _busy = false;
  String? _error;

  Future<void> _submit(List<PolicyVersionRef> pending) async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(authActionsProvider).acceptConsents([
        for (final p in pending) p.id,
      ], marketingOptIn: _marketing);
    } on Object catch (e) {
      if (mounted) setState(() => _error = authErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _read(PolicyVersionRef policy) async {
    final l10n = AppLocalizations.of(context);
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (ctx) => _PolicySheet(
        future: ref.read(authActionsProvider).policy(policy.type),
        fallbackTitle: policyLabel(l10n, policy.type),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final pending = ref.watch(meProvider).value?.pendingConsents ?? const [];
    return Scaffold(
      appBar: AppBar(title: Text(l10n.consentTitle)),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          children: [
            Text(l10n.consentSubtitle),
            const SizedBox(height: AppSpacing.md),
            for (final policy in pending)
              Card(
                child: ListTile(
                  key: Key('policy-${policy.type}'),
                  minTileHeight: context.density.minTouchTarget,
                  leading: const Icon(Icons.description_outlined),
                  title: Text(policyLabel(l10n, policy.type)),
                  trailing: TextButton(
                    onPressed: () => _read(policy),
                    child: Text(l10n.consentRead),
                  ),
                ),
              ),
            const SizedBox(height: AppSpacing.md),
            CheckboxListTile(
              key: const Key('consent-accept'),
              value: _accepted,
              onChanged: _busy ? null : (v) => setState(() => _accepted = v!),
              controlAffinity: ListTileControlAffinity.leading,
              contentPadding: EdgeInsets.zero,
              title: Text(l10n.consentAccept),
            ),
            CheckboxListTile(
              key: const Key('consent-marketing'),
              value: _marketing,
              onChanged: _busy ? null : (v) => setState(() => _marketing = v!),
              controlAffinity: ListTileControlAffinity.leading,
              contentPadding: EdgeInsets.zero,
              title: Text(l10n.consentMarketing),
            ),
            if (_error != null)
              Padding(
                padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
                child: Text(
                  _error!,
                  style: TextStyle(color: Theme.of(context).colorScheme.error),
                ),
              ),
            const SizedBox(height: AppSpacing.md),
            AppButton(
              label: l10n.actionContinue,
              loading: _busy,
              onPressed: _accepted && pending.isNotEmpty
                  ? () => _submit(pending)
                  : null,
            ),
          ],
        ),
      ),
    );
  }
}

class _PolicySheet extends StatelessWidget {
  const _PolicySheet({required this.future, required this.fallbackTitle});

  final Future<PolicyDocument> future;
  final String fallbackTitle;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return SizedBox(
      height: MediaQuery.sizeOf(context).height * 0.75,
      child: FutureBuilder<PolicyDocument>(
        future: future,
        builder: (context, snapshot) {
          if (snapshot.connectionState != ConnectionState.done) {
            return LoadingView(semanticLabel: l10n.loadingLabel);
          }
          if (snapshot.hasError) {
            return EmptyState(
              icon: Icons.error_outline,
              title: fallbackTitle,
              message: authErrorText(l10n, snapshot.error!),
            );
          }
          final doc = snapshot.data!;
          return ListView(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            children: [
              Text(doc.title, style: Theme.of(context).textTheme.titleLarge),
              const SizedBox(height: AppSpacing.md),
              Text(doc.content),
            ],
          );
        },
      ),
    );
  }
}
