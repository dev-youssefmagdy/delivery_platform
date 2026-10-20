import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../providers/catalog_providers.dart';
import 'catalog_icons.dart';

/// Service family chips and category cards fed by the catalog API
/// (SRS §6.3). Choosing a category selects it for the booking flow (route
/// selection is built in P2.6) and offers its dynamic details form.
class ServicePicker extends ConsumerWidget {
  const ServicePicker({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final catalog = ref.watch(catalogProvider);
    return Padding(
      padding: const EdgeInsetsDirectional.symmetric(
        horizontal: AppSpacing.md,
        vertical: AppSpacing.sm,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.catalogTitle,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: AppSpacing.sm),
          ConstrainedBox(
            constraints: const BoxConstraints(minHeight: 160),
            child: AsyncStateView<CatalogData>(
              value: catalog,
              onRetry: () => ref.invalidate(catalogProvider),
              builder: (context, data) => data.categories.isEmpty
                  ? EmptyState(
                      title: l10n.catalogEmptyTitle,
                      message: l10n.catalogEmptyBody,
                      icon: Icons.directions_car_outlined,
                    )
                  : _Catalog(data: data),
            ),
          ),
        ],
      ),
    );
  }
}

class _Catalog extends ConsumerWidget {
  const _Catalog({required this.data});

  final CatalogData data;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final language = Localizations.localeOf(context).languageCode;
    final familyId = ref.watch(effectiveFamilyIdProvider);
    final selected = ref.watch(selectedCategoryProvider);
    final categories = data.categoriesOf(familyId);
    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        if (data.families.length > 1)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                for (final family in data.families)
                  Padding(
                    padding: const EdgeInsetsDirectional.only(
                      end: AppSpacing.sm,
                    ),
                    child: ChoiceChip(
                      key: Key('family-${family.id}'),
                      avatar: Icon(catalogIcon(family.icon), size: 18),
                      label: Text(family.name.resolve(language)),
                      selected: family.id == familyId,
                      onSelected: (_) => ref
                          .read(selectedFamilyIdProvider.notifier)
                          .select(family.id),
                    ),
                  ),
              ],
            ),
          ),
        const SizedBox(height: AppSpacing.sm),
        for (final category in categories)
          _CategoryCard(
            category: category,
            language: language,
            selected: selected?.id == category.id,
            onTap: () => ref
                .read(selectedCategoryIdProvider.notifier)
                .select(category.id),
          ),
        if (selected != null)
          _SelectedPanel(category: selected, language: language),
      ],
    );
  }
}

class _CategoryCard extends ConsumerWidget {
  const _CategoryCard({
    required this.category,
    required this.language,
    required this.selected,
    required this.onTap,
  });

  final ServiceCategoryInfo category;
  final String language;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final scheme = Theme.of(context).colorScheme;
    final config = ref.watch(appConfigProvider).value;
    final formatter = ref.watch(moneyFormatterProvider);
    final facts = <String>[
      if (category.maxPassengers != null)
        l10n.catalogCapacityPassengers(category.maxPassengers!),
      if (category.maxLuggage != null && category.maxLuggage! > 0)
        l10n.catalogCapacityLuggage(category.maxLuggage!),
      if (category.maxWeightKg != null)
        l10n.catalogCapacityWeight(_trim(category.maxWeightKg!)),
      if (category.minFareMinor != null && config != null)
        l10n.catalogFareFrom(
          formatter.format(
            Money(
              minor: category.minFareMinor!,
              currency: config.currencyCode,
              exponent: config.currencyExponent,
            ),
          ),
        ),
      if (category.negotiationEnabled) l10n.catalogNegotiable,
    ];
    return Card(
      key: Key('category-${category.id}'),
      margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppRadius.md),
        side: BorderSide(
          color: selected ? scheme.primary : scheme.outlineVariant,
          width: selected ? 2 : 1,
        ),
      ),
      child: InkWell(
        borderRadius: BorderRadius.circular(AppRadius.md),
        onTap: onTap,
        child: Semantics(
          button: true,
          selected: selected,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  catalogIcon(category.icon),
                  size: 32,
                  color: scheme.primary,
                ),
                const SizedBox(width: AppSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        category.name.resolve(language),
                        style: Theme.of(context).textTheme.titleMedium,
                      ),
                      if (!category.description.isEmpty)
                        Text(
                          category.description.resolve(language),
                          style: Theme.of(context).textTheme.bodyMedium,
                        ),
                      if (facts.isNotEmpty) ...[
                        const SizedBox(height: AppSpacing.xs),
                        Wrap(
                          spacing: AppSpacing.sm,
                          children: [
                            for (final fact in facts)
                              Text(
                                fact,
                                style: Theme.of(context).textTheme.bodySmall,
                              ),
                          ],
                        ),
                      ],
                    ],
                  ),
                ),
                if (selected) Icon(Icons.check_circle, color: scheme.primary),
              ],
            ),
          ),
        ),
      ),
    );
  }

  static String _trim(double v) =>
      v == v.truncateToDouble() ? v.toInt().toString() : v.toString();
}

class _SelectedPanel extends ConsumerWidget {
  const _SelectedPanel({required this.category, required this.language});

  final ServiceCategoryInfo category;
  final String language;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final vehicles = category.vehicleCategories
        .map((v) => v.name.resolve(language))
        .join('، ');
    return Card(
      key: const Key('selected-service-panel'),
      color: Theme.of(context).colorScheme.primaryContainer,
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              l10n.catalogSelectedLabel,
              style: Theme.of(context).textTheme.labelMedium,
            ),
            Text(
              category.name.resolve(language),
              style: Theme.of(context).textTheme.titleMedium,
            ),
            if (vehicles.isNotEmpty)
              Text(
                l10n.catalogVehiclesLabel(vehicles),
                style: Theme.of(context).textTheme.bodySmall,
              ),
            const SizedBox(height: AppSpacing.sm),
            if (category.formSchema.fields.isNotEmpty)
              AppButton(
                key: const Key('open-service-details'),
                label: l10n.catalogDetailsButton,
                secondary: true,
                expand: false,
                onPressed: () => context.push(Routes.serviceDetails),
              ),
          ],
        ),
      ),
    );
  }
}
