import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/provider_onboarding_providers.dart';
import 'onboarding_labels.dart';

/// Step 6: what is still open and the submit button. Everything shown comes
/// from the server's computed task list, never from local guesses.
class ReviewStep extends ConsumerStatefulWidget {
  const ReviewStep({
    super.key,
    required this.application,
    required this.onGoToStep,
    required this.onSubmitted,
  });

  final ProviderApplication application;
  final void Function(String step) onGoToStep;
  final VoidCallback onSubmitted;

  @override
  ConsumerState<ReviewStep> createState() => _ReviewStepState();
}

class _ReviewStepState extends ConsumerState<ReviewStep> {
  bool _busy = false;
  String? _error;
  String _intentKey = newIdempotencyKey();

  String _stepOf(OnboardingTask task) => switch (task) {
    OnboardingTask.completeProfile => OnboardingStepKey.profile,
    OnboardingTask.selectServices => OnboardingStepKey.services,
    OnboardingTask.addVehicle ||
    OnboardingTask.addVehiclePhotos ||
    OnboardingTask.submitVehicle ||
    OnboardingTask.fixVehicle => OnboardingStepKey.vehicles,
    OnboardingTask.uploadDocument ||
    OnboardingTask.replaceRejectedDocument ||
    OnboardingTask.renewExpiredDocument => OnboardingStepKey.documents,
    OnboardingTask.acceptPolicies => OnboardingStepKey.declarations,
    _ => OnboardingStepKey.review,
  };

  Future<void> _submit() async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref.read(onboardingActionsProvider).submit(_intentKey);
      _intentKey = newIdempotencyKey();
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.obReviewSubmitted)));
      widget.onSubmitted();
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
    final app = widget.application;
    final open = [
      for (final t in app.tasks)
        if (t.task != OnboardingTask.submitApplication) t,
    ];

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(l10n.obReviewTitle, style: Theme.of(context).textTheme.titleLarge),
        const SizedBox(height: AppSpacing.md),
        if (open.isEmpty)
          Text(l10n.obReviewReady, key: const Key('review-ready'))
        else ...[
          Text(
            l10n.obReviewTodo,
            style: Theme.of(context).textTheme.titleSmall,
          ),
          for (final t in open)
            ListTile(
              key: Key('task-${t.task.value}'),
              minTileHeight: context.density.minTouchTarget,
              contentPadding: EdgeInsets.zero,
              leading: const Icon(Icons.radio_button_unchecked),
              title: Text(taskLabel(l10n, t, app, language)),
              trailing: TextButton(
                onPressed: () => widget.onGoToStep(_stepOf(t.task)),
                child: Text(l10n.obGoToStep),
              ),
            ),
        ],
        if (_error != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            _error!,
            key: const Key('review-error'),
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        AppButton(
          key: const Key('review-submit'),
          label: l10n.obReviewSubmit,
          loading: _busy,
          onPressed: app.canSubmit ? _submit : null,
        ),
      ],
    );
  }
}
