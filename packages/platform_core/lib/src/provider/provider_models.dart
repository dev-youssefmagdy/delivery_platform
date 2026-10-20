import '../enums/document_status.dart';
import '../enums/eligibility_reason.dart';
import '../enums/onboarding_task.dart';
import '../enums/provider_status.dart';
import '../enums/provider_type.dart';
import '../enums/service_approval_status.dart';
import '../enums/vehicle_capability.dart';
import '../enums/vehicle_photo_kind.dart';
import '../enums/vehicle_status.dart';
import '../forms/form_schema.dart';

int? _int(Object? v) => (v as num?)?.toInt();

DateTime? _date(Object? v) => v is String ? DateTime.tryParse(v) : null;

List<Map<String, dynamic>> _maps(Object? v) => v is List
    ? [
        for (final item in v)
          if (item is Map) Map<String, dynamic>.from(item),
      ]
    : const [];

/// Wizard step keys sent by the server (`steps` and `resume_step`).
abstract final class OnboardingStepKey {
  static const profile = 'profile';
  static const services = 'services';
  static const vehicles = 'vehicles';
  static const documents = 'documents';
  static const declarations = 'declarations';
  static const review = 'review';

  static const ordered = [
    profile,
    services,
    vehicles,
    documents,
    declarations,
    review,
  ];
}

/// An active city of the market (`GET /cities`).
class CityInfo {
  const CityInfo({required this.id, required this.name, this.timezone});

  factory CityInfo.fromJson(Map<String, dynamic> json) => CityInfo(
    id: _int(json['id']) ?? 0,
    name: LocalizedText.fromJson(json['name']),
    timezone: json['timezone'] as String?,
  );

  final int id;
  final LocalizedText name;
  final String? timezone;
}

/// How to upload one kind of document (admin-configured document type).
class DocumentTypeInfo {
  const DocumentTypeInfo({
    required this.id,
    required this.code,
    required this.name,
    this.description = const LocalizedText({}),
    this.appliesTo = 'provider',
    this.requiresExpiry = false,
    this.requiresNumber = false,
    this.allowedMimes = const [],
    this.maxSizeKb = 5120,
  });

  factory DocumentTypeInfo.fromJson(Map<String, dynamic> json) =>
      DocumentTypeInfo(
        id: _int(json['id']) ?? 0,
        code: '${json['code']}',
        name: LocalizedText.fromJson(json['name']),
        description: LocalizedText.fromJson(json['description']),
        appliesTo: '${json['applies_to'] ?? 'provider'}',
        requiresExpiry: json['requires_expiry'] == true,
        requiresNumber: json['requires_number'] == true,
        allowedMimes: [
          for (final m in (json['allowed_mimes'] as List? ?? const [])) '$m',
        ],
        maxSizeKb: _int(json['max_size_kb']) ?? 5120,
      );

  final int id;
  final String code;
  final LocalizedText name;
  final LocalizedText description;
  final String appliesTo;
  final bool requiresExpiry;
  final bool requiresNumber;
  final List<String> allowedMimes;
  final int maxSizeKb;

  bool get appliesToVehicle => appliesTo == 'vehicle';
}

/// An uploaded document as its owner sees it. The file itself is never
/// downloaded by the app; only status, reasons and dates matter.
class ProviderDocumentInfo {
  const ProviderDocumentInfo({
    required this.id,
    required this.documentType,
    required this.status,
    this.vehicleId,
    this.rejectionReason,
    this.numberMasked,
    this.issuedAt,
    this.expiresAt,
    this.uploadedAt,
  });

  factory ProviderDocumentInfo.fromJson(Map<String, dynamic> json) =>
      ProviderDocumentInfo(
        id: '${json['id']}',
        documentType: '${json['document_type']}',
        status: DocumentStatus.parse(json['status'] as String?),
        vehicleId: json['vehicle_id'] as String?,
        rejectionReason: json['rejection_reason'] as String?,
        numberMasked: json['number_masked'] as String?,
        issuedAt: _date(json['issued_at']),
        expiresAt: _date(json['expires_at']),
        uploadedAt: _date(json['uploaded_at']),
      );

  final String id;
  final String documentType;
  final DocumentStatus status;
  final String? vehicleId;
  final String? rejectionReason;
  final String? numberMasked;
  final DateTime? issuedAt;
  final DateTime? expiresAt;
  final DateTime? uploadedAt;
}

/// One line of the checklist: a required (or optional) document type and
/// where the driver stands with it. A null [state] means nothing uploaded.
class RequiredDocument {
  const RequiredDocument({
    required this.type,
    required this.mandatory,
    this.state,
    this.vehicleId,
    this.expiresAt,
    this.document,
  });

  factory RequiredDocument.fromJson(Map<String, dynamic> json) =>
      RequiredDocument(
        type: DocumentTypeInfo.fromJson(
          Map<String, dynamic>.from(json['document_type'] as Map? ?? const {}),
        ),
        mandatory: json['mandatory'] == true,
        state: json['state'] == null
            ? null
            : DocumentStatus.parse(json['state'] as String?),
        vehicleId: json['vehicle_id'] as String?,
        expiresAt: _date(json['expires_at']),
        document: json['document'] is Map
            ? ProviderDocumentInfo.fromJson(
                Map<String, dynamic>.from(json['document'] as Map),
              )
            : null,
      );

  final DocumentTypeInfo type;
  final bool mandatory;
  final DocumentStatus? state;
  final String? vehicleId;
  final DateTime? expiresAt;
  final ProviderDocumentInfo? document;

  bool get isMissing => state == null;

  /// Uploaded and either waiting for review or approved.
  bool get isUploaded =>
      state == DocumentStatus.pending || state == DocumentStatus.approved;

  /// The driver has to act: nothing uploaded, rejected or expired.
  bool get needsAction => !isUploaded;
}

class VehiclePhotoInfo {
  const VehiclePhotoInfo({required this.id, required this.kind, this.url});

  factory VehiclePhotoInfo.fromJson(Map<String, dynamic> json) =>
      VehiclePhotoInfo(
        id: '${json['id']}',
        kind: VehiclePhotoKind.parse(json['kind'] as String?),
        url: json['url'] as String?,
      );

  final String id;
  final VehiclePhotoKind kind;
  final String? url;
}

/// A vehicle of the driver (`/provider/vehicles`).
class ProviderVehicleInfo {
  const ProviderVehicleInfo({
    required this.id,
    required this.vehicleCategoryId,
    required this.make,
    required this.model,
    required this.year,
    required this.color,
    required this.plate,
    required this.status,
    this.passengerCapacity,
    this.payloadKg,
    this.volumeM3,
    this.capabilities = const [],
    this.rejectionReason,
    this.editable = false,
    this.photos = const [],
  });

  factory ProviderVehicleInfo.fromJson(Map<String, dynamic> json) =>
      ProviderVehicleInfo(
        id: '${json['id']}',
        vehicleCategoryId: _int(json['vehicle_category_id']) ?? 0,
        make: '${json['make'] ?? ''}',
        model: '${json['model'] ?? ''}',
        year: _int(json['year']) ?? 0,
        color: '${json['color'] ?? ''}',
        plate: '${json['plate'] ?? ''}',
        status: VehicleStatus.parse(json['status'] as String?),
        passengerCapacity: _int(json['passenger_capacity']),
        payloadKg: json['payload_kg'] == null ? null : '${json['payload_kg']}',
        volumeM3: json['volume_m3'] == null ? null : '${json['volume_m3']}',
        capabilities: [
          for (final c in (json['capabilities'] as List? ?? const []))
            VehicleCapability.parse('$c'),
        ],
        rejectionReason: json['rejection_reason'] as String?,
        editable: json['editable'] == true,
        photos: [
          for (final p in _maps(json['photos'])) VehiclePhotoInfo.fromJson(p),
        ],
      );

  final String id;
  final int vehicleCategoryId;
  final String make;
  final String model;
  final int year;
  final String color;
  final String plate;
  final VehicleStatus status;
  final int? passengerCapacity;
  final String? payloadKg;
  final String? volumeM3;
  final List<VehicleCapability> capabilities;
  final String? rejectionReason;
  final bool editable;
  final List<VehiclePhotoInfo> photos;

  String get title => '$make $model $year'.trim();
}

/// A task still open for the driver; [reference] is a document type code.
class OnboardingTaskInfo {
  const OnboardingTaskInfo({
    required this.task,
    this.reference,
    this.vehicleId,
  });

  factory OnboardingTaskInfo.fromJson(Map<String, dynamic> json) =>
      OnboardingTaskInfo(
        task: OnboardingTask.parse(json['task'] as String?),
        reference: json['reference'] as String?,
        vehicleId: json['vehicle_id'] as String?,
      );

  final OnboardingTask task;
  final String? reference;
  final String? vehicleId;
}

class RequestedService {
  const RequestedService({
    required this.serviceCategoryId,
    required this.status,
  });

  factory RequestedService.fromJson(Map<String, dynamic> json) =>
      RequestedService(
        serviceCategoryId: _int(json['service_category_id']) ?? 0,
        status: ServiceApprovalStatus.parse(json['status'] as String?),
      );

  final int serviceCategoryId;
  final ServiceApprovalStatus status;
}

class SupportContact {
  const SupportContact({this.phone, this.email, this.whatsapp});

  factory SupportContact.fromJson(Object? json) {
    if (json is! Map) return const SupportContact();
    return SupportContact(
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      whatsapp: json['whatsapp'] as String?,
    );
  }

  final String? phone;
  final String? email;
  final String? whatsapp;

  bool get isEmpty => phone == null && email == null && whatsapp == null;
}

/// The driver's application with its verification progress
/// (`GET /provider/application`, docs/API.md 8.9).
class ProviderApplication {
  const ProviderApplication({
    required this.started,
    this.id,
    this.status = ProviderStatus.draft,
    this.statusReason,
    this.providerType,
    this.cityId,
    this.name,
    this.dateOfBirth,
    this.services = const [],
    this.editable = false,
    this.submittedAt,
    this.steps = const {},
    this.resumeStep = OnboardingStepKey.profile,
    this.canSubmit = false,
    this.tasks = const [],
    this.requiredDocuments = const [],
    this.expiringDocuments = const [],
    this.vehicles = const [],
    this.pendingPolicyIds = const [],
    this.support = const SupportContact(),
  });

  /// No application yet: the wizard starts at the profile step.
  const ProviderApplication.notStarted() : this(started: false);

  factory ProviderApplication.fromJson(Map<String, dynamic> json) {
    if (json['started'] != true) return const ProviderApplication.notStarted();
    return ProviderApplication(
      started: true,
      id: json['id'] as String?,
      status: ProviderStatus.parse(json['status'] as String?),
      statusReason: json['status_reason'] as String?,
      providerType: json['provider_type'] == null
          ? null
          : ProviderType.parse(json['provider_type'] as String?),
      cityId: _int(json['city_id']),
      name: json['name'] as String?,
      dateOfBirth: _date(json['date_of_birth']),
      services: [
        for (final s in _maps(json['services'])) RequestedService.fromJson(s),
      ],
      editable: json['editable'] == true,
      submittedAt: _date(json['submitted_at']),
      steps: {
        if (json['steps'] is Map)
          for (final e in (json['steps'] as Map).entries)
            '${e.key}': e.value == true,
      },
      resumeStep: '${json['resume_step'] ?? OnboardingStepKey.profile}',
      canSubmit: json['can_submit'] == true,
      tasks: [
        for (final t in _maps(json['tasks'])) OnboardingTaskInfo.fromJson(t),
      ],
      requiredDocuments: [
        for (final d in _maps(json['required_documents']))
          RequiredDocument.fromJson(d),
      ],
      expiringDocuments: [
        for (final d in _maps(json['expiring_documents']))
          RequiredDocument.fromJson(d),
      ],
      vehicles: [
        for (final v in _maps(json['vehicles']))
          ProviderVehicleInfo.fromJson(v),
      ],
      pendingPolicyIds: [
        for (final p in (json['pending_policy_ids'] as List? ?? const [])) '$p',
      ],
      support: SupportContact.fromJson(json['support']),
    );
  }

  final bool started;
  final String? id;
  final ProviderStatus status;
  final String? statusReason;
  final ProviderType? providerType;
  final int? cityId;
  final String? name;
  final DateTime? dateOfBirth;
  final List<RequestedService> services;
  final bool editable;
  final DateTime? submittedAt;
  final Map<String, bool> steps;
  final String resumeStep;
  final bool canSubmit;
  final List<OnboardingTaskInfo> tasks;
  final List<RequiredDocument> requiredDocuments;
  final List<RequiredDocument> expiringDocuments;
  final List<ProviderVehicleInfo> vehicles;
  final List<String> pendingPolicyIds;
  final SupportContact support;

  bool stepDone(String step) => steps[step] == true;

  List<int> get serviceCategoryIds => [
    for (final s in services)
      if (s.status != ServiceApprovalStatus.rejected) s.serviceCategoryId,
  ];

  /// Provider-level documents (the vehicle ones are listed per vehicle).
  List<RequiredDocument> get providerDocuments => [
    for (final d in requiredDocuments)
      if (d.vehicleId == null) d,
  ];

  List<RequiredDocument> documentsOfVehicle(String vehicleId) => [
    for (final d in requiredDocuments)
      if (d.vehicleId == vehicleId) d,
  ];

  ProviderVehicleInfo? vehicleById(String id) {
    for (final v in vehicles) {
      if (v.id == id) return v;
    }
    return null;
  }
}

class EligibilityIssueInfo {
  const EligibilityIssueInfo({required this.reason, this.reference});

  factory EligibilityIssueInfo.fromJson(Map<String, dynamic> json) =>
      EligibilityIssueInfo(
        reason: EligibilityReason.parse(json['reason'] as String?),
        reference: json['reference'] as String?,
      );

  final EligibilityReason reason;
  final String? reference;
}

/// `GET /provider/eligibility`: computed on the server, never cached.
class EligibilityInfo {
  const EligibilityInfo({required this.eligible, this.issues = const []});

  factory EligibilityInfo.fromJson(Map<String, dynamic> json) =>
      EligibilityInfo(
        eligible: json['eligible'] == true,
        issues: [
          for (final r in _maps(json['reasons']))
            EligibilityIssueInfo.fromJson(r),
        ],
      );

  final bool eligible;
  final List<EligibilityIssueInfo> issues;

  /// Each reason once, in server order.
  List<EligibilityReason> get reasons {
    final unique = <EligibilityReason>[];
    for (final issue in issues) {
      if (!unique.contains(issue.reason)) unique.add(issue.reason);
    }
    return unique;
  }
}

/// Fields of the vehicle form; null values are not sent on update.
class VehicleDraft {
  const VehicleDraft({
    this.vehicleCategoryId,
    this.make,
    this.model,
    this.year,
    this.color,
    this.plate,
    this.passengerCapacity,
    this.payloadKg,
    this.volumeM3,
    this.capabilities,
  });

  final int? vehicleCategoryId;
  final String? make;
  final String? model;
  final int? year;
  final String? color;
  final String? plate;
  final int? passengerCapacity;
  final String? payloadKg;
  final String? volumeM3;
  final List<VehicleCapability>? capabilities;

  Map<String, dynamic> toJson() => {
    'vehicle_category_id': ?vehicleCategoryId,
    'make': ?make,
    'model': ?model,
    'year': ?year,
    'color': ?color,
    'plate': ?plate,
    'passenger_capacity': ?passengerCapacity,
    'payload_kg': ?payloadKg,
    'volume_m3': ?volumeM3,
    if (capabilities != null)
      'capabilities': [for (final c in capabilities!) c.value],
  };
}
