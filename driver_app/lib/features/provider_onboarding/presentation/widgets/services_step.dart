import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/provider_onboarding_providers.dart';
import 'onboarding_labels.dart';

/// Step 2: which services the driver offers. The server reviews each one.
class ServicesStep extends ConsumerStatefulWidget {
  const ServicesStep({
    super.key,
    required this.application,
    required this.onDone,
  });

  final ProviderApplication application;
  final VoidCallback onDone;

  @override
  ConsumerState<ServicesStep> createState() => _ServicesStepState();
}

class _ServicesStepState extends ConsumerState<ServicesStep> {
  late Set<int> _selected;
  bool _busy = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _selected = widget.application.serviceCategoryIds.toSet();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(onboardingActionsProvider)
          .saveServices(_selected.toList());
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
    final language = Localizations.localeOf(context).languageCode;
    final categories = ref.watch(serviceCategoriesProvider);
    final app = widget.application;

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(
          l10n.obServicesTitle,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(l10n.obServicesIntro),
        const SizedBox(height: AppSpacing.md),
        categories.when(
          loading: () => const Center(child: CircularProgressIndicator()),
          error: (e, _) => ErrorState(
            offline: e is ApiException && e.isOffline,
            title: l10n.errorGenericTitle,
            message: onboardingErrorText(l10n, e),
            retryLabel: l10n.actionRetry,
            onRetry: () => ref.invalidate(serviceCategoriesProvider),
          ),
          data: (list) => list.isEmpty
              ? Text(l10n.obServicesEmpty)
              : Column(
                  children: [
                    for (final c in list)
                      CheckboxListTile(
                        key: Key('service-${c.id}'),
                        value: _selected.contains(c.id),
                        title: Text(c.name.resolve(language)),
                        subtitle: _status(app, c.id, l10n),
                        onChanged:
                            app.started &&
                                !app.editable &&
                                app.status != ProviderStatus.approved
                            ? null
                            : (on) => setState(
                                () => on == true
                                    ? _selected.add(c.id)
                                    : _selected.remove(c.id),
                              ),
                      ),
                  ],
                ),
        ),
        if (_error != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            _error!,
            key: const Key('services-error'),
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        AppButton(
          key: const Key('services-save'),
          label: l10n.obServicesSave,
          loading: _busy,
          onPressed: _selected.isEmpty ? null : _save,
        ),
      ],
    );
  }

  Widget? _status(
    ProviderApplication app,
    int categoryId,
    AppLocalizations l10n,
  ) {
    for (final s in app.services) {
      if (s.serviceCategoryId == categoryId) {
        return Align(
          alignment: AlignmentDirectional.centerStart,
          child: StatusChip(label: s.status.label(l10n), tone: s.status.tone),
        );
      }
    }
    return null;
  }
}
