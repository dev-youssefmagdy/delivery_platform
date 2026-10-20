import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/provider_onboarding_providers.dart';
import 'onboarding_labels.dart';

/// Step 5: the terms the driver has to accept (driver terms, terms of use,
/// privacy). The accepted version and time are recorded by the server.
class DeclarationsStep extends ConsumerStatefulWidget {
  const DeclarationsStep({super.key, required this.onDone});

  final VoidCallback onDone;

  @override
  ConsumerState<DeclarationsStep> createState() => _DeclarationsStepState();
}

class _DeclarationsStepState extends ConsumerState<DeclarationsStep> {
  final Set<String> _checked = {};
  final Set<String> _expanded = {};
  final Map<String, Future<PolicyDocument>> _texts = {};
  bool _busy = false;
  String? _error;

  String _name(AppLocalizations l10n, String type) => switch (type) {
    'terms' => l10n.obPolicyTerms,
    'privacy' => l10n.obPolicyPrivacy,
    'driver_terms' => l10n.obPolicyDriverTerms,
    _ => l10n.obPolicyOther,
  };

  Future<void> _accept(List<PolicyVersionRef> pending) async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(onboardingActionsProvider).acceptPolicies([
        for (final p in pending) p.id,
      ]);
      if (mounted) widget.onDone();
    } on Object catch (e) {
      if (mounted) setState(() => _error = onboardingErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final pending =
        ref.watch(meProvider).value?.pendingConsents ??
        const <PolicyVersionRef>[];
    final allChecked = pending.every((p) => _checked.contains(p.id));

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(l10n.obDeclTitle, style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: AppSpacing.sm),
        Text(l10n.obDeclIntro),
        const SizedBox(height: AppSpacing.md),
        if (pending.isEmpty)
          Text(l10n.obDeclNone, key: const Key('declarations-none'))
        else
          for (final p in pending) ...[
            CheckboxListTile(
              key: Key('policy-${p.type}'),
              value: _checked.contains(p.id),
              controlAffinity: ListTileControlAffinity.leading,
              title: Text(l10n.obDeclAccept(_name(l10n, p.type))),
              onChanged: (on) => setState(
                () => on == true ? _checked.add(p.id) : _checked.remove(p.id),
              ),
            ),
            Align(
              alignment: AlignmentDirectional.centerStart,
              child: TextButton(
                key: Key('policy-read-${p.type}'),
                onPressed: () => setState(() {
                  if (!_expanded.add(p.id)) _expanded.remove(p.id);
                  _texts.putIfAbsent(
                    p.id,
                    () => ref.read(authActionsProvider).policy(p.type),
                  );
                }),
                child: Text(l10n.obDeclRead),
              ),
            ),
            if (_expanded.contains(p.id))
              FutureBuilder<PolicyDocument>(
                future: _texts[p.id],
                builder: (context, snapshot) => snapshot.hasData
                    ? Padding(
                        padding: const EdgeInsetsDirectional.only(
                          bottom: AppSpacing.md,
                        ),
                        child: Text(
                          snapshot.data!.content,
                          key: Key('policy-text-${p.type}'),
                        ),
                      )
                    : snapshot.hasError
                    ? Text(onboardingErrorText(l10n, snapshot.error!))
                    : const LinearProgressIndicator(),
              ),
          ],
        if (_error != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            _error!,
            key: const Key('declarations-error'),
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        if (pending.isEmpty)
          AppButton(label: l10n.actionNext, onPressed: widget.onDone)
        else
          AppButton(
            key: const Key('declarations-accept'),
            label: l10n.obDeclAcceptAll,
            loading: _busy,
            onPressed: allChecked ? () => _accept(pending) : null,
          ),
      ],
    );
  }
}
