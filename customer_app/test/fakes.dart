import 'dart:convert';

import 'package:flutter/painting.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

/// Scriptable identity API for widget tests.
class FakeIdentityRepository implements IdentityRepository {
  FakeIdentityRepository({MeUser? me}) : meUser = me ?? defaultUser();

  MeUser meUser;
  Object? requestError;
  Object? verifyError;
  Object? meError;
  String validCode = '123456';
  final requestedPhones = <String>[];
  final verifyAttempts = <String>[];
  final accepted = <List<String>>[];
  bool? acceptedMarketing;
  final deletionKeys = <String>[];
  bool loggedOut = false;
  bool cancelled = false;
  ProfileUpdate? lastProfile;

  static MeUser defaultUser({
    UserStatus status = UserStatus.active,
    bool complete = true,
    List<PolicyVersionRef> pending = const [],
    DeletionRequestInfo? deletion,
  }) => MeUser(
    id: 'u-1',
    name: complete ? 'Mona' : null,
    phone: '+201001234567',
    status: status,
    profileComplete: complete,
    pendingConsents: pending,
    deletionRequest: deletion,
  );

  @override
  Future<OtpRequestResult> requestOtp(String phoneE164) async {
    requestedPhones.add(phoneE164);
    if (requestError != null) throw requestError!;
    return const OtpRequestResult(
      expiresInSeconds: 300,
      resendInSeconds: 30,
      length: 6,
    );
  }

  @override
  Future<AuthSessionInfo> verifyOtp(String phoneE164, String code) async {
    verifyAttempts.add(code);
    if (verifyError != null) throw verifyError!;
    if (code != validCode) {
      throw const ApiException(
        kind: ApiFailureKind.validation,
        code: 'otp_invalid',
        message: null,
        statusCode: 422,
      );
    }
    return AuthSessionInfo(token: 'tok', user: meUser, isNewUser: true);
  }

  @override
  Future<MeUser> me() async {
    if (meError != null) throw meError!;
    return meUser;
  }

  @override
  Future<MeUser> updateProfile(ProfileUpdate update) async {
    lastProfile = update;
    meUser = MeUser(
      id: meUser.id,
      name: update.name ?? meUser.name,
      phone: meUser.phone,
      status: meUser.status,
      profileComplete: true,
      pendingConsents: meUser.pendingConsents,
    );
    return meUser;
  }

  @override
  Future<PolicyDocument> policy(String type) async => PolicyDocument(
    id: 'p',
    type: type,
    version: 1,
    title: 'Policy title $type',
    content: 'Policy body $type',
  );

  @override
  Future<void> acceptConsents(
    List<String> policyIds, {
    bool? marketingOptIn,
  }) async {
    accepted.add(policyIds);
    acceptedMarketing = marketingOptIn;
    meUser = MeUser(
      id: meUser.id,
      name: meUser.name,
      status: meUser.status,
      profileComplete: meUser.profileComplete,
    );
  }

  @override
  Future<void> logout() async => loggedOut = true;

  @override
  Future<void> requestAccountDeletion({
    required String idempotencyKey,
    String? reason,
  }) async {
    deletionKeys.add(idempotencyKey);
    meUser = defaultUser(
      status: UserStatus.deletionPending,
      deletion: const DeletionRequestInfo(id: 'd-1', status: 'pending'),
    );
  }

  @override
  Future<void> cancelAccountDeletion(String requestId) async {
    cancelled = true;
    meUser = defaultUser();
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

/// Scriptable catalog API for widget tests.
class FakeCatalogRepository implements CatalogRepository {
  FakeCatalogRepository({
    this.familiesResult = const [],
    this.categoriesResult = const [],
  });

  List<ServiceFamilyInfo> familiesResult;
  List<ServiceCategoryInfo> categoriesResult;
  Object? error;
  int familiesCalls = 0;
  double? lastLat;
  double? lastLng;

  @override
  Future<List<ServiceFamilyInfo>> families() async {
    familiesCalls++;
    if (error != null) throw error!;
    return familiesResult;
  }

  @override
  Future<List<ServiceCategoryInfo>> categories({
    BookingType? bookingType,
    int? familyId,
    double? lat,
    double? lng,
  }) async {
    lastLat = lat;
    lastLng = lng;
    if (error != null) throw error!;
    return categoriesResult;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

/// Scriptable device location for widget tests (never touches geolocator).
class FakeLocationService implements LocationService {
  FakeLocationService({
    this.permission = LocationPermissionState.denied,
    this.position,
  });

  LocationPermissionState permission;
  GeoPoint? position;

  /// Permission the OS grants when [request] is called.
  LocationPermissionState? grantOnRequest;
  int requests = 0;
  int settingsOpened = 0;
  bool throwOnCurrent = false;

  @override
  Future<LocationPermissionState> status() async => permission;

  @override
  Future<LocationPermissionState> request() async {
    requests++;
    permission = grantOnRequest ?? permission;
    return permission;
  }

  @override
  Future<GeoPoint?> current() async {
    if (throwOnCurrent) throw StateError('no fix');
    return permission == LocationPermissionState.granted ? position : null;
  }

  @override
  Stream<GeoPoint> watch({int distanceFilterMeters = 20}) =>
      const Stream.empty();

  @override
  Future<void> openAppSettings() async => settingsOpened++;

  @override
  Future<void> openLocationSettings() async => settingsOpened++;
}

/// Scriptable geography API for widget tests.
class FakeGeoRepository implements GeoRepository {
  FakeGeoRepository({this.saved = const []});

  List<PlaceResult> searchResults = const [];
  Object? searchError;
  PlaceResult? reverseResult;
  Object? reverseError;
  ServiceAreaInfo area = const ServiceAreaInfo(
    inServiceArea: true,
    cityName: LocalizedText({'ar': 'القاهرة', 'en': 'Cairo'}),
    zoneId: 1,
  );
  Object? areaError;
  List<SavedPlaceInfo> saved;
  Object? savedError;
  Object? createError;

  final searchQueries = <String>[];
  final resolvedPoints = <LatLng>[];
  final created = <Map<String, Object?>>[];
  final deleted = <String>[];

  @override
  Future<List<PlaceResult>> searchPlaces(
    String query, {
    LatLng? near,
    int? limit,
  }) async {
    searchQueries.add(query);
    if (searchError != null) throw searchError!;
    return searchResults;
  }

  @override
  Future<PlaceResult?> reverse(LatLng point) async {
    if (reverseError != null) throw reverseError!;
    return reverseResult;
  }

  @override
  Future<ServiceAreaInfo> resolveArea(LatLng point) async {
    resolvedPoints.add(point);
    if (areaError != null) throw areaError!;
    return area;
  }

  @override
  Future<List<SavedPlaceInfo>> savedPlaces() async {
    if (savedError != null) throw savedError!;
    return saved;
  }

  @override
  Future<SavedPlaceInfo> createSavedPlace({
    required String label,
    required SavedPlaceType type,
    required LatLng point,
    PlaceResult? place,
  }) async {
    if (createError != null) throw createError!;
    created.add({'label': label, 'type': type, 'point': point});
    final info = SavedPlaceInfo(
      id: 'sp-${created.length}',
      label: label,
      type: type,
      point: point,
      addressLine: place?.address,
    );
    saved = [...saved, info];
    return info;
  }

  @override
  Future<void> deleteSavedPlace(String id) async {
    deleted.add(id);
    saved = [
      for (final p in saved)
        if (p.id != id) p,
    ];
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

final _transparentPng = base64Decode(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
);

/// Tiles that never hit the network.
class NoNetworkTileProvider extends TileProvider {
  @override
  ImageProvider getImage(TileCoordinates coordinates, TileLayer options) =>
      MemoryImage(_transparentPng);
}
