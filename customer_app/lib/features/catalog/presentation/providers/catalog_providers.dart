import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';

final catalogRepositoryProvider = Provider<CatalogRepository>(
  (ref) => CatalogRepository(ref.watch(apiClientProvider)),
);

/// Families and categories of the service catalog, fetched together.
class CatalogData {
  const CatalogData({required this.families, required this.categories});

  final List<ServiceFamilyInfo> families;
  final List<ServiceCategoryInfo> categories;

  List<ServiceCategoryInfo> categoriesOf(int? familyId) => [
    for (final c in categories)
      if (familyId == null || c.familyId == familyId) c,
  ];

  ServiceCategoryInfo? categoryById(int? id) {
    for (final c in categories) {
      if (c.id == id) return c;
    }
    return null;
  }
}

Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Failures show the retry state of the picker; no hidden automatic retries.
final catalogProvider = FutureProvider<CatalogData>((ref) async {
  final repository = ref.watch(catalogRepositoryProvider);
  final results = await Future.wait<Object>([
    repository.families(),
    repository.categories(),
  ]);
  return CatalogData(
    families: results[0] as List<ServiceFamilyInfo>,
    categories: results[1] as List<ServiceCategoryInfo>,
  );
}, retry: _noAutoRetry);

class SelectedFamilyNotifier extends Notifier<int?> {
  @override
  int? build() => null;

  void select(int familyId) => state = familyId;
}

/// The family the user picked, or the first family while none is picked (or
/// the picked one disappeared from the catalog).
final selectedFamilyIdProvider = NotifierProvider<SelectedFamilyNotifier, int?>(
  SelectedFamilyNotifier.new,
);

final effectiveFamilyIdProvider = Provider<int?>((ref) {
  final data = ref.watch(catalogProvider).value;
  if (data == null || data.families.isEmpty) return null;
  final picked = ref.watch(selectedFamilyIdProvider);
  return data.families.any((f) => f.id == picked)
      ? picked
      : data.families.first.id;
});

class SelectedCategoryNotifier extends Notifier<int?> {
  @override
  int? build() => null;

  void select(int categoryId) => state = categoryId;
}

final selectedCategoryIdProvider =
    NotifierProvider<SelectedCategoryNotifier, int?>(
      SelectedCategoryNotifier.new,
    );

/// The chosen category with its published form schema; null when nothing is
/// chosen or the category left the catalog.
final selectedCategoryProvider = Provider<ServiceCategoryInfo?>((ref) {
  final data = ref.watch(catalogProvider).value;
  return data?.categoryById(ref.watch(selectedCategoryIdProvider));
});

/// Answers to the selected service's form (`service_details`), per category
/// id. The booking flow (P2.6) sends them with the booking.
class ServiceDetailsNotifier extends Notifier<Map<int, Map<String, Object?>>> {
  @override
  Map<int, Map<String, Object?>> build() => const {};

  void save(int categoryId, Map<String, Object?> details) =>
      state = {...state, categoryId: details};
}

final serviceDetailsProvider =
    NotifierProvider<ServiceDetailsNotifier, Map<int, Map<String, Object?>>>(
      ServiceDetailsNotifier.new,
    );
