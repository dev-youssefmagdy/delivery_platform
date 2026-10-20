import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../auth/presentation/providers/auth_providers.dart';
import '../providers/provider_onboarding_providers.dart';
import 'date_field.dart';
import 'onboarding_labels.dart';

/// Step 1: name, birth date, provider type and city. Creates the application
/// on first save, updates it afterwards.
class ProfileStep extends ConsumerStatefulWidget {
  const ProfileStep({
    super.key,
    required this.application,
    required this.onDone,
  });

  final ProviderApplication application;
  final VoidCallback onDone;

  @override
  ConsumerState<ProfileStep> createState() => _ProfileStepState();
}

class _ProfileStepState extends ConsumerState<ProfileStep> {
  late final TextEditingController _name;
  ProviderType? _type;
  int? _cityId;
  DateTime? _dob;
  bool _submitted = false;
  bool _busy = false;
  String? _error;
  String _intentKey = newIdempotencyKey();

  @override
  void initState() {
    super.initState();
    final app = widget.application;
    _name = TextEditingController(
      text: app.name ?? ref.read(meProvider).value?.name ?? '',
    );
    _type = app.providerType;
    _cityId = app.cityId;
    _dob = app.dateOfBirth;
  }

  @override
  void dispose() {
    _name.dispose();
    super.dispose();
  }

  bool get _needsBirthDate => _type != ProviderType.companyFleet;

  DateTime get _latestBirthDate {
    final now = DateTime.now();
    return DateTime(now.year - 18, now.month, now.day);
  }

  bool get _valid =>
      _name.text.trim().length >= 2 &&
      _type != null &&
      _cityId != null &&
      (!_needsBirthDate || (_dob != null && !_dob!.isAfter(_latestBirthDate)));

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _submitted = true;
      _error = null;
    });
    if (!_valid) return;

    setState(() => _busy = true);
    try {
      await ref
          .read(onboardingActionsProvider)
          .saveProfile(
            started: widget.application.started,
            providerType: _type!,
            cityId: _cityId!,
            name: _name.text.trim(),
            dateOfBirth: _needsBirthDate ? _dob : null,
            idempotencyKey: _intentKey,
          );
      _intentKey = newIdempotencyKey();
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
    final cities = ref.watch(citiesProvider);
    final editable = !widget.application.started || widget.application.editable;
    final now = DateTime.now();

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(
          l10n.obProfileTitle,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(l10n.obProfileIntro),
        const SizedBox(height: AppSpacing.lg),
        AppTextField(
          label: l10n.obProfileName,
          controller: _name,
          enabled: editable,
          textInputAction: TextInputAction.next,
          errorText: _submitted && _name.text.trim().length < 2
              ? l10n.validationRequired
              : null,
          onChanged: (_) => setState(() => _intentKey = newIdempotencyKey()),
        ),
        const SizedBox(height: AppSpacing.md),
        DropdownButtonFormField<ProviderType>(
          key: const Key('profile-type'),
          initialValue: _type,
          decoration: InputDecoration(
            labelText: l10n.obProfileType,
            errorText: _submitted && _type == null
                ? l10n.validationRequired
                : null,
          ),
          items: [
            for (final t in ProviderType.values)
              if (t != ProviderType.unknown)
                DropdownMenuItem(value: t, child: Text(t.label(l10n))),
          ],
          onChanged: editable
              ? (v) => setState(() {
                  _type = v;
                  _intentKey = newIdempotencyKey();
                })
              : null,
        ),
        const SizedBox(height: AppSpacing.md),
        cities.when(
          loading: () => const LinearProgressIndicator(),
          error: (e, _) => ErrorState(
            offline: e is ApiException && e.isOffline,
            title: l10n.errorGenericTitle,
            message: onboardingErrorText(l10n, e),
            retryLabel: l10n.actionRetry,
            onRetry: () => ref.invalidate(citiesProvider),
          ),
          data: (list) => list.isEmpty
              ? Text(l10n.obProfileCitiesEmpty)
              : DropdownButtonFormField<int>(
                  key: const Key('profile-city'),
                  initialValue: list.any((c) => c.id == _cityId)
                      ? _cityId
                      : null,
                  decoration: InputDecoration(
                    labelText: l10n.obProfileCity,
                    hintText: l10n.obProfileCityPick,
                    errorText: _submitted && _cityId == null
                        ? l10n.validationRequired
                        : null,
                  ),
                  items: [
                    for (final c in list)
                      DropdownMenuItem(
                        value: c.id,
                        child: Text(c.name.resolve(language)),
                      ),
                  ],
                  onChanged: editable
                      ? (v) => setState(() {
                          _cityId = v;
                          _intentKey = newIdempotencyKey();
                        })
                      : null,
                ),
        ),
        if (_needsBirthDate) ...[
          const SizedBox(height: AppSpacing.md),
          DateField(
            fieldKey: const Key('profile-dob'),
            label: l10n.obProfileDob,
            value: _dob,
            firstDate: DateTime(now.year - 90),
            lastDate: _latestBirthDate,
            initialDate: DateTime(now.year - 30),
            errorText: !_submitted
                ? null
                : _dob == null
                ? l10n.validationRequired
                : _dob!.isAfter(_latestBirthDate)
                ? l10n.obErrUnderage(18)
                : null,
            onPicked: editable
                ? (d) => setState(() {
                    _dob = d;
                    _intentKey = newIdempotencyKey();
                  })
                : (_) {},
          ),
        ],
        if (_error != null) ...[
          const SizedBox(height: AppSpacing.md),
          Text(
            _error!,
            key: const Key('profile-error'),
            style: TextStyle(color: Theme.of(context).colorScheme.error),
          ),
        ],
        const SizedBox(height: AppSpacing.lg),
        AppButton(
          key: const Key('profile-save'),
          label: l10n.obProfileSave,
          loading: _busy,
          onPressed: editable ? _save : widget.onDone,
        ),
      ],
    );
  }
}
