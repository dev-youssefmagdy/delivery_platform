import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';

final providerRepositoryProvider = Provider<ProviderRepository>(
  (ref) => ProviderRepository(ref.watch(apiClientProvider)),
);

final catalogRepositoryProvider = Provider<CatalogRepository>(
  (ref) => CatalogRepository(ref.watch(apiClientProvider)),
);

Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Failures show a retry state; no hidden automatic retries.
final citiesProvider = FutureProvider<List<CityInfo>>(
  (ref) => ref.watch(providerRepositoryProvider).cities(),
  retry: _noAutoRetry,
);

/// Every active service category (no position: the driver picks what to offer).
final serviceCategoriesProvider = FutureProvider<List<ServiceCategoryInfo>>(
  (ref) => ref.watch(catalogRepositoryProvider).categories(),
  retry: _noAutoRetry,
);

final vehicleCategoriesProvider = FutureProvider<List<VehicleCategoryInfo>>(
  (ref) => ref.watch(catalogRepositoryProvider).vehicleCategories(),
  retry: _noAutoRetry,
);

/// Why the driver can or cannot work, computed by the server on every read.
final eligibilityProvider = FutureProvider.autoDispose<EligibilityInfo>(
  (ref) => ref.watch(providerRepositoryProvider).eligibility(),
  retry: _noAutoRetry,
);

/// Picks a photo (camera or gallery). The factory takes the localized sheet
/// labels; tests override it with a fake that returns a prepared file path.
typedef PhotoPickerFactory = PhotoFieldHandler Function({
  required String cameraLabel,
  required String galleryLabel,
});

final photoPickerFactoryProvider = Provider<PhotoPickerFactory>(
  (ref) =>
      ({required cameraLabel, required galleryLabel}) =>
          ImagePickerPhotoHandler(
            cameraLabel: cameraLabel,
            galleryLabel: galleryLabel,
          ),
);

/// The driver's application with its progress (`GET /provider/application`).
/// Rebuilt when the session changes; writes replace the state with the server's
/// answer so the wizard always shows what the server computed.
class ApplicationNotifier extends AsyncNotifier<ProviderApplication> {
  @override
  Future<ProviderApplication> build() async {
    final session = ref.watch(sessionStatusProvider);
    if (session != SessionStatus.authenticated) {
      return const ProviderApplication.notStarted();
    }
    return ref.read(providerRepositoryProvider).application();
  }

  void set(ProviderApplication application) => state = AsyncData(application);

  /// Re-reads the application. A failed re-read keeps the data already on
  /// screen (the write that triggered it succeeded); the error is returned so
  /// callers that want to tell the driver can.
  Future<Object?> reload() async {
    final previous = state.value;
    final next = await AsyncValue.guard(
      () => ref.read(providerRepositoryProvider).application(),
    );
    state = next.hasError && previous != null ? AsyncData(previous) : next;
    return next.hasError ? next.error : null;
  }
}

final applicationProvider =
    AsyncNotifierProvider<ApplicationNotifier, ProviderApplication>(
      ApplicationNotifier.new,
      retry: _noAutoRetry,
    );

/// Everything the onboarding screens do. Screens call this, never the
/// repository or dio directly (Plan §6.2); each write refreshes the
/// application from the server's answer.
class OnboardingActions {
  OnboardingActions(this._ref);

  final Ref _ref;

  ProviderRepository get _repo => _ref.read(providerRepositoryProvider);

  ApplicationNotifier get _application =>
      _ref.read(applicationProvider.notifier);

  /// Creates the application, or updates the profile fields of an existing one.
  Future<void> saveProfile({
    required bool started,
    required ProviderType providerType,
    required int cityId,
    required String name,
    required String idempotencyKey,
    DateTime? dateOfBirth,
    List<int> serviceCategoryIds = const [],
  }) async {
    final app = started
        ? await _repo.updateApplication(
            providerType: providerType,
            cityId: cityId,
            name: name,
            dateOfBirth: dateOfBirth,
          )
        : await _repo.startApplication(
            providerType: providerType,
            cityId: cityId,
            name: name,
            dateOfBirth: dateOfBirth,
            serviceCategoryIds: serviceCategoryIds,
            idempotencyKey: idempotencyKey,
          );
    _application.set(app);
    // The name lives on the account too.
    await _ref.read(meProvider.notifier).reload();
  }

  Future<void> saveServices(List<int> serviceCategoryIds) async =>
      _application.set(
        await _repo.updateApplication(serviceCategoryIds: serviceCategoryIds),
      );

  Future<ProviderVehicleInfo> createVehicle(
    VehicleDraft draft,
    String idempotencyKey,
  ) async {
    final vehicle = await _repo.createVehicle(
      draft,
      idempotencyKey: idempotencyKey,
    );
    await _application.reload();
    return vehicle;
  }

  Future<ProviderVehicleInfo> updateVehicle(
    String vehicleId,
    VehicleDraft draft,
  ) async {
    final vehicle = await _repo.updateVehicle(vehicleId, draft);
    await _application.reload();
    return vehicle;
  }

  Future<void> deleteVehicle(String vehicleId) async {
    await _repo.deleteVehicle(vehicleId);
    await _application.reload();
  }

  Future<void> addVehiclePhoto(
    String vehicleId, {
    required String filePath,
    required VehiclePhotoKind kind,
    required String idempotencyKey,
  }) async {
    await _repo.addVehiclePhoto(
      vehicleId,
      filePath: filePath,
      kind: kind,
      idempotencyKey: idempotencyKey,
    );
    await _application.reload();
  }

  Future<void> removeVehiclePhoto(String vehicleId, String photoId) async {
    await _repo.removeVehiclePhoto(vehicleId, photoId);
    await _application.reload();
  }

  Future<void> submitVehicle(String vehicleId, String idempotencyKey) async {
    await _repo.submitVehicle(vehicleId, idempotencyKey: idempotencyKey);
    await _application.reload();
  }

  Future<void> uploadDocument({
    required String documentType,
    required String filePath,
    required String idempotencyKey,
    String? number,
    DateTime? issuedAt,
    DateTime? expiresAt,
    String? vehicleId,
  }) async {
    await _repo.uploadDocument(
      documentType: documentType,
      filePath: filePath,
      idempotencyKey: idempotencyKey,
      number: number,
      issuedAt: issuedAt,
      expiresAt: expiresAt,
      vehicleId: vehicleId,
    );
    await _application.reload();
  }

  /// Accepts the listed policy versions (driver terms, terms, privacy).
  Future<void> acceptPolicies(List<String> policyIds) async {
    await _ref.read(authActionsProvider).acceptConsents(policyIds);
    await _application.reload();
  }

  Future<void> submit(String idempotencyKey) async => _application.set(
    await _repo.submitApplication(idempotencyKey: idempotencyKey),
  );
}

final onboardingActionsProvider = Provider<OnboardingActions>(
  OnboardingActions.new,
);

/// True while the driver is editing the application in the wizard even though
/// the server status would normally show the verification screen (resubmission).
class WizardOpenNotifier extends Notifier<bool> {
  @override
  bool build() => false;

  void open() => state = true;

  void close() => state = false;
}

final wizardOpenProvider = NotifierProvider<WizardOpenNotifier, bool>(
  WizardOpenNotifier.new,
);
