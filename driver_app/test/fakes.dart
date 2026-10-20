import 'package:flutter/widgets.dart';
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

/// JSON of `GET /provider/application` for tests (the exact backend shape);
/// override the pieces a test cares about.
Map<String, dynamic> applicationJson({
  bool started = true,
  String status = 'documents_pending',
  String? statusReason,
  String resumeStep = 'profile',
  Map<String, bool> steps = const {},
  bool editable = true,
  bool canSubmit = false,
  List<int> services = const [],
  List<Map<String, dynamic>> tasks = const [],
  List<Map<String, dynamic>> documents = const [],
  List<Map<String, dynamic>> vehicles = const [],
  List<Map<String, dynamic>> expiring = const [],
  String? name,
  String? submittedAt,
  Map<String, dynamic>? support,
}) => {
  'started': started,
  if (started) ...{
    'id': 'prov-1',
    'status': status,
    'status_reason': statusReason,
    'provider_type': 'individual_driver',
    'city_id': 1,
    'name': name ?? 'Omar Hassan',
    'date_of_birth': '1990-01-01',
    'services': [
      for (final id in services)
        {'service_category_id': id, 'status': 'requested'},
    ],
    'editable': editable,
    'submitted_at': submittedAt,
    'steps': steps,
    'resume_step': resumeStep,
    'can_submit': canSubmit,
    'tasks': tasks,
    'required_documents': documents,
    'expiring_documents': expiring,
    'vehicles': vehicles,
    'pending_policy_ids': <String>[],
    'support': support ?? {'phone': null, 'email': null, 'whatsapp': null},
  },
};

Map<String, dynamic> documentTypeJson(
  String code, {
  String name = 'Driving licence',
  bool expiry = true,
  bool number = true,
  String appliesTo = 'provider',
}) => {
  'id': code.hashCode.abs() % 1000,
  'code': code,
  'name': {'ar': name, 'en': name},
  'description': {'ar': null, 'en': null},
  'applies_to': appliesTo,
  'requires_expiry': expiry,
  'requires_number': number,
  'allowed_mimes': ['image/jpeg', 'image/png', 'application/pdf'],
  'max_size_kb': 5120,
};

Map<String, dynamic> requiredDocumentJson(
  String code, {
  String name = 'Driving licence',
  String? state,
  String? reason,
  String? expiresAt,
  bool mandatory = true,
  bool expiry = true,
  bool number = true,
  String? vehicleId,
}) => {
  'document_type': documentTypeJson(
    code,
    name: name,
    expiry: expiry,
    number: number,
    appliesTo: vehicleId == null ? 'provider' : 'vehicle',
  ),
  'mandatory': mandatory,
  'state': state,
  'vehicle_id': vehicleId,
  'expires_at': expiresAt,
  'document': state == null
      ? null
      : {
          'id': 'doc-$code',
          'document_type': code,
          'vehicle_id': vehicleId,
          'status': state,
          'rejection_reason': reason,
          'expires_at': expiresAt,
        },
};

Map<String, dynamic> vehicleJson({
  String id = 'veh-1',
  String status = 'draft',
  bool editable = true,
  List<Map<String, dynamic>> photos = const [],
  String? reason,
}) => {
  'id': id,
  'vehicle_category_id': 4,
  'make': 'Toyota',
  'model': 'Corolla',
  'year': 2020,
  'color': 'White',
  'plate': 'ABC 1234',
  'passenger_capacity': 4,
  'payload_kg': null,
  'volume_m3': null,
  'capabilities': <String>['air_conditioning'],
  'status': status,
  'rejection_reason': reason,
  'editable': editable,
  'photos': photos,
};

/// A scripted provider API: the current application JSON is mutated by the
/// writes the way the server would, so widget tests can walk the wizard.
class FakeProviderRepository implements ProviderRepository {
  FakeProviderRepository({Map<String, dynamic>? application})
    : json = application ?? applicationJson(started: false);

  Map<String, dynamic> json;
  Object? writeError;
  Object? readError;
  Object? eligibilityError;
  EligibilityInfo eligibilityInfo = const EligibilityInfo(eligible: true);
  List<CityInfo> citiesList = [
    CityInfo(id: 1, name: LocalizedText.single('Cairo', language: 'en')),
    CityInfo(id: 2, name: LocalizedText.single('Giza', language: 'en')),
  ];
  final calls = <String>[];
  final started = <Map<String, dynamic>>[];
  final updates = <Map<String, dynamic>>[];
  final vehicleDrafts = <VehicleDraft>[];
  final uploads = <Map<String, dynamic>>[];
  final uploadAttemptKeys = <String>[];
  final photos = <Map<String, dynamic>>[];
  final submitted = <String>[];

  @override
  ApiClient get api => throw UnimplementedError();

  Object? _takeError() {
    final e = writeError;
    writeError = null;
    return e;
  }

  @override
  Future<List<CityInfo>> cities() async => citiesList;

  @override
  Future<ProviderApplication> application() async {
    calls.add('application');
    if (readError != null) throw readError!;
    return ProviderApplication.fromJson(json);
  }

  @override
  Future<EligibilityInfo> eligibility() async {
    if (eligibilityError != null) throw eligibilityError!;
    return eligibilityInfo;
  }

  @override
  Future<ProviderApplication> startApplication({
    required ProviderType providerType,
    required int cityId,
    required String name,
    required String idempotencyKey,
    DateTime? dateOfBirth,
    List<int> serviceCategoryIds = const [],
  }) async {
    calls.add('start');
    final error = _takeError();
    if (error != null) throw error;
    started.add({
      'type': providerType.value,
      'city': cityId,
      'name': name,
      'dob': dateOfBirth,
      'key': idempotencyKey,
    });
    json = applicationJson(
      resumeStep: 'services',
      name: name,
      steps: {'profile': true},
    );
    return ProviderApplication.fromJson(json);
  }

  @override
  Future<ProviderApplication> updateApplication({
    ProviderType? providerType,
    int? cityId,
    String? name,
    DateTime? dateOfBirth,
    List<int>? serviceCategoryIds,
  }) async {
    calls.add('update');
    final error = _takeError();
    if (error != null) throw error;
    updates.add({
      'type': providerType?.value,
      'city': cityId,
      'name': name,
      'services': serviceCategoryIds,
    });
    if (serviceCategoryIds != null) {
      json['services'] = [
        for (final id in serviceCategoryIds)
          {'service_category_id': id, 'status': 'requested'},
      ];
      (json['steps'] as Map)['services'] = true;
    }
    return ProviderApplication.fromJson(json);
  }

  @override
  Future<ProviderApplication> submitApplication({
    required String idempotencyKey,
  }) async {
    calls.add('submit');
    final error = _takeError();
    if (error != null) throw error;
    submitted.add(idempotencyKey);
    json['status'] = 'under_review';
    json['editable'] = false;
    json['can_submit'] = false;
    json['tasks'] = <Map<String, dynamic>>[];
    return ProviderApplication.fromJson(json);
  }

  @override
  Future<ProviderVehicleInfo> createVehicle(
    VehicleDraft draft, {
    required String idempotencyKey,
  }) async {
    calls.add('createVehicle');
    final error = _takeError();
    if (error != null) throw error;
    vehicleDrafts.add(draft);
    final vehicle = vehicleJson();
    json['vehicles'] = [...(json['vehicles'] as List), vehicle];
    return ProviderVehicleInfo.fromJson(vehicle);
  }

  @override
  Future<ProviderVehicleInfo> updateVehicle(
    String vehicleId,
    VehicleDraft draft,
  ) async {
    calls.add('updateVehicle');
    final error = _takeError();
    if (error != null) throw error;
    vehicleDrafts.add(draft);
    return ProviderVehicleInfo.fromJson(
      (json['vehicles'] as List).cast<Map<String, dynamic>>().firstWhere(
        (v) => v['id'] == vehicleId,
      ),
    );
  }

  @override
  Future<void> deleteVehicle(String vehicleId) async {
    calls.add('deleteVehicle');
    json['vehicles'] = [
      for (final v in (json['vehicles'] as List))
        if ((v as Map)['id'] != vehicleId) v,
    ];
  }

  @override
  Future<ProviderVehicleInfo> addVehiclePhoto(
    String vehicleId, {
    required String filePath,
    required VehiclePhotoKind kind,
    required String idempotencyKey,
  }) async {
    calls.add('addPhoto');
    final error = _takeError();
    if (error != null) throw error;
    photos.add({'vehicle': vehicleId, 'path': filePath, 'kind': kind.value});
    final vehicles = (json['vehicles'] as List).cast<Map<String, dynamic>>();
    final vehicle = vehicles.firstWhere((v) => v['id'] == vehicleId);
    vehicle['photos'] = [
      ...(vehicle['photos'] as List),
      {'id': 'ph-${photos.length}', 'kind': kind.value, 'url': null},
    ];
    return ProviderVehicleInfo.fromJson(vehicle);
  }

  @override
  Future<ProviderVehicleInfo> removeVehiclePhoto(
    String vehicleId,
    String photoId,
  ) async {
    calls.add('removePhoto');
    final vehicles = (json['vehicles'] as List).cast<Map<String, dynamic>>();
    final vehicle = vehicles.firstWhere((v) => v['id'] == vehicleId);
    vehicle['photos'] = [
      for (final p in (vehicle['photos'] as List))
        if ((p as Map)['id'] != photoId) p,
    ];
    return ProviderVehicleInfo.fromJson(vehicle);
  }

  @override
  Future<ProviderVehicleInfo> submitVehicle(
    String vehicleId, {
    required String idempotencyKey,
  }) async {
    calls.add('submitVehicle');
    final error = _takeError();
    if (error != null) throw error;
    final vehicles = (json['vehicles'] as List).cast<Map<String, dynamic>>();
    final vehicle = vehicles.firstWhere((v) => v['id'] == vehicleId);
    vehicle['status'] = 'under_review';
    vehicle['editable'] = false;
    return ProviderVehicleInfo.fromJson(vehicle);
  }

  @override
  Future<List<ProviderDocumentInfo>> documents() async => const [];

  @override
  Future<ProviderDocumentInfo> uploadDocument({
    required String documentType,
    required String filePath,
    required String idempotencyKey,
    String? number,
    DateTime? issuedAt,
    DateTime? expiresAt,
    String? vehicleId,
  }) async {
    calls.add('upload');
    uploadAttemptKeys.add(idempotencyKey);
    final error = _takeError();
    if (error != null) throw error;
    uploads.add({
      'type': documentType,
      'path': filePath,
      'key': idempotencyKey,
      'number': number,
      'expires': expiresAt,
      'vehicle': vehicleId,
    });
    for (final d
        in (json['required_documents'] as List).cast<Map<String, dynamic>>()) {
      if ((d['document_type'] as Map)['code'] == documentType &&
          d['vehicle_id'] == vehicleId) {
        d['state'] = 'pending';
        d['document'] = {
          'id': 'doc-new',
          'document_type': documentType,
          'status': 'pending',
        };
      }
    }
    return ProviderDocumentInfo(
      id: 'doc-new',
      documentType: documentType,
      status: DocumentStatus.pending,
    );
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

class FakeCatalogRepository implements CatalogRepository {
  FakeCatalogRepository({
    this.categoryNames = const ['Economy ride', 'Courier'],
  });

  final List<String> categoryNames;

  @override
  ApiClient get api => throw UnimplementedError();

  @override
  Future<List<ServiceFamilyInfo>> families() async => const [];

  @override
  Future<List<ServiceCategoryInfo>> categories({
    BookingType? bookingType,
    int? familyId,
    double? lat,
    double? lng,
  }) async => [
    for (var i = 0; i < categoryNames.length; i++)
      ServiceCategoryInfo.fromJson({
        'id': i + 1,
        'code': 'cat_$i',
        'version': {'id': 1, 'number': 1},
        'family': {
          'id': 1,
          'code': 'rides',
          'kind': 'passenger_city',
          'name': {'ar': 'x'},
        },
        'name': {'ar': categoryNames[i], 'en': categoryNames[i]},
        'description': {},
        'booking': {
          'allowed_booking_types': ['on_demand'],
        },
        'pricing': {},
        'limits': {},
        'payments': {},
        'form_schema': {'version': 1, 'fields': []},
      }),
  ];

  @override
  Future<List<VehicleCategoryInfo>> vehicleCategories({
    int? serviceCategoryId,
  }) async => [
    VehicleCategoryInfo(
      id: 4,
      code: 'economy',
      name: LocalizedText.single('Economy car', language: 'en'),
    ),
  ];

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

/// Returns a prepared path instead of opening the camera / gallery.
class FakePhotoHandler implements PhotoFieldHandler {
  FakePhotoHandler(this.path);

  final String? path;
  int picks = 0;

  @override
  Future<String?> pick(BuildContext context) async {
    picks++;
    return path;
  }
}
