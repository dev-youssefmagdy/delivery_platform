import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../providers/provider_onboarding_providers.dart';
import 'declarations_step.dart';
import 'documents_step.dart';
import 'profile_step.dart';
import 'review_step.dart';
import 'services_step.dart';
import 'vehicles_step.dart';

/// The application wizard: profile, services, vehicles, documents,
/// declarations, review. It opens at the step the server says is next
/// (`resume_step`), so a driver always resumes where they left off, on any
/// device. Every step saves to the server before moving on.
class OnboardingWizard extends ConsumerStatefulWidget {
  const OnboardingWizard({
    super.key,
    required this.application,
    this.onFinished,
  });

  final ProviderApplication application;
  final VoidCallback? onFinished;

  @override
  ConsumerState<OnboardingWizard> createState() => _OnboardingWizardState();
}

class _OnboardingWizardState extends ConsumerState<OnboardingWizard> {
  late int _index;

  @override
  void initState() {
    super.initState();
    final start = OnboardingStepKey.ordered.indexOf(
      widget.application.resumeStep,
    );
    _index = start < 0 ? 0 : start;
  }

  void _go(String step) {
    final i = OnboardingStepKey.ordered.indexOf(step);
    if (i >= 0) setState(() => _index = i);
  }

  void _next() {
    if (_index < OnboardingStepKey.ordered.length - 1) {
      setState(() => _index++);
    }
  }

  String _title(AppLocalizations l10n, String step) => switch (step) {
    OnboardingStepKey.profile => l10n.obStepProfile,
    OnboardingStepKey.services => l10n.obStepServices,
    OnboardingStepKey.vehicles => l10n.obStepVehicles,
    OnboardingStepKey.documents => l10n.obStepDocuments,
    OnboardingStepKey.declarations => l10n.obStepDeclarations,
    _ => l10n.obStepReview,
  };

  void _openVehicle(String? id) => context.push(
    id == null ? Routes.providerVehicleNew : '${Routes.providerVehicle}/$id',
  );

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final app = ref.watch(applicationProvider).value ?? widget.application;
    final steps = OnboardingStepKey.ordered;
    final step = steps[_index];
    // Steps after the profile need an application on the server.
    final canLeaveProfile = app.started;

    final body = switch (step) {
      OnboardingStepKey.profile => ProfileStep(
        key: const ValueKey('step-profile'),
        application: app,
        onDone: _next,
      ),
      OnboardingStepKey.services => ServicesStep(
        key: const ValueKey('step-services'),
        application: app,
        onDone: _next,
      ),
      OnboardingStepKey.vehicles => VehiclesStep(
        key: const ValueKey('step-vehicles'),
        application: app,
        onOpenVehicle: _openVehicle,
        onNext: _next,
      ),
      OnboardingStepKey.documents => DocumentsStep(
        key: const ValueKey('step-documents'),
        application: app,
        onNext: _next,
      ),
      OnboardingStepKey.declarations => DeclarationsStep(
        key: const ValueKey('step-declarations'),
        onDone: _next,
      ),
      _ => ReviewStep(
        key: const ValueKey('step-review'),
        application: app,
        onGoToStep: _go,
        onSubmitted: () => widget.onFinished?.call(),
      ),
    };

    return Column(
      children: [
        Padding(
          padding: const EdgeInsetsDirectional.fromSTEB(
            AppSpacing.md,
            AppSpacing.sm,
            AppSpacing.md,
            0,
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                l10n.obStepCounter(_index + 1, steps.length),
                key: const Key('wizard-counter'),
                style: Theme.of(context).textTheme.labelMedium,
              ),
              const SizedBox(height: AppSpacing.xs),
              LinearProgressIndicator(
                value: (_index + 1) / steps.length,
                semanticsLabel: _title(l10n, step),
              ),
              const SizedBox(height: AppSpacing.sm),
              SingleChildScrollView(
                scrollDirection: Axis.horizontal,
                child: Row(
                  children: [
                    for (var i = 0; i < steps.length; i++)
                      Padding(
                        padding: const EdgeInsetsDirectional.only(
                          end: AppSpacing.sm,
                        ),
                        child: ChoiceChip(
                          key: Key('wizard-step-${steps[i]}'),
                          label: Text(_title(l10n, steps[i])),
                          avatar: app.stepDone(steps[i])
                              ? const Icon(Icons.check_circle, size: 18)
                              : null,
                          selected: i == _index,
                          onSelected: i == 0 || canLeaveProfile
                              ? (_) => setState(() => _index = i)
                              : null,
                        ),
                      ),
                  ],
                ),
              ),
            ],
          ),
        ),
        Expanded(child: body),
        if (_index > 0)
          Padding(
            padding: const EdgeInsetsDirectional.fromSTEB(
              AppSpacing.md,
              0,
              AppSpacing.md,
              AppSpacing.md,
            ),
            child: AppButton(
              key: const Key('wizard-back'),
              label: l10n.actionBack,
              secondary: true,
              onPressed: () => setState(() => _index--),
            ),
          ),
      ],
    );
  }
}
