import 'dart:io';

import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import 'api_client_test.dart' show FakeAdapter, json, env;

/// Exact shape of `GET /provider/application` as produced by the backend
/// (docs/API.md 8.9).
const _applicationJson = {
  'started': true,
  'id': 'prov-1',
  'status': 'more_information_required',
  'status_reason': 'The licence photo is blurry',
  'status_changed_at': '2026-10-22T09:00:00Z',
  'provider_type': 'individual_driver',
  'city_id': 3,
  'name': 'Omar Hassan',
  'date_of_birth': '1992-04-05',
  'services': [
    {'service_category_id': 7, 'status': 'approved'},
    {'service_category_id': 8, 'status': 'rejected'},
  ],
  'editable': true,
  'submitted_at': '2026-10-21T10:00:00Z',
  'steps': {
    'profile': true,
    'services': true,
    'vehicles': true,
    'documents': false,
    'declarations': false,
  },
  'resume_step': 'documents',
  'can_submit': false,
  'tasks': [
    {
      'task': 'replace_rejected_document',
      'reference': 'licence',
      'vehicle_id': null,
    },
    {'task': 'accept_policies', 'reference': null, 'vehicle_id': null},
    {'task': 'something_new', 'reference': null, 'vehicle_id': null},
  ],
  'required_documents': [
    {
      'document_type': {
        'id': 1,
        'code': 'licence',
        'name': {'ar': 'رخصة', 'en': 'Licence'},
        'description': {'ar': null, 'en': null},
        'applies_to': 'provider',
        'requires_expiry': true,
        'requires_number': true,
        'allowed_mimes': ['image/jpeg', 'application/pdf'],
        'max_size_kb': 5120,
      },
      'mandatory': true,
      'state': 'rejected',
      'vehicle_id': null,
      'expires_at': null,
      'document': {
        'id': 'doc-1',
        'document_type': 'licence',
        'vehicle_id': null,
        'status': 'rejected',
        'rejection_reason': 'Blurry',
        'number_masked': '••••456',
        'issued_at': null,
        'expires_at': null,
        'uploaded_at': '2026-10-21T09:00:00Z',
      },
    },
    {
      'document_type': {
        'id': 2,
        'code': 'registration',
        'name': {'ar': 'تسجيل'},
        'applies_to': 'vehicle',
        'requires_expiry': false,
        'requires_number': false,
        'allowed_mimes': ['image/jpeg'],
        'max_size_kb': 2048,
      },
      'mandatory': true,
      'state': null,
      'vehicle_id': 'veh-1',
      'expires_at': null,
      'document': null,
    },
  ],
  'expiring_documents': [],
  'vehicles': [
    {
      'id': 'veh-1',
      'vehicle_category_id': 4,
      'make': 'Toyota',
      'model': 'Corolla',
      'year': 2020,
      'color': 'White',
      'plate': 'ABC 1234',
      'passenger_capacity': 4,
      'payload_kg': null,
      'volume_m3': null,
      'capabilities': ['air_conditioning', 'teleporter'],
      'status': 'under_review',
      'rejection_reason': null,
      'editable': false,
      'photos': [
        {'id': 'ph-1', 'kind': 'front', 'url': 'https://api.test/signed'},
      ],
    },
  ],
  'pending_policy_ids': ['pol-1'],
  'support': {'phone': '+20100', 'email': null, 'whatsapp': null},
};

void main() {
  late FakeAdapter adapter;
  late ProviderRepository repo;

  setUp(() {
    adapter = FakeAdapter((o) {
      if (o.path.endsWith('/provider/application') && o.method == 'GET') {
        return json(200, {'success': true, 'data': _applicationJson});
      }
      if (o.path.endsWith('/cities')) {
        return json(200, {
          'success': true,
          'data': [
            {
              'id': 3,
              'name': {'ar': 'القاهرة', 'en': 'Cairo'},
              'timezone': 'Africa/Cairo',
            },
          ],
        });
      }
      if (o.path.endsWith('/provider/documents')) {
        return json(201, {
          'success': true,
          'data': {
            'id': 'doc-9',
            'document_type': 'licence',
            'status': 'pending',
          },
        });
      }
      return json(200, {'success': true, 'data': _applicationJson});
    });
    final dio = Dio(BaseOptions(baseUrl: env.apiBaseUrl))
      ..httpClientAdapter = adapter;
    repo = ProviderRepository(
      ApiClient(
        env: env,
        dio: dio,
        readToken: () => 'tok',
        readLanguageCode: () => 'ar',
      ),
    );
  });

  test('parses the application, tasks, documents and vehicles', () async {
    final app = await repo.application();

    expect(app.started, isTrue);
    expect(app.status, ProviderStatus.moreInformationRequired);
    expect(app.statusReason, 'The licence photo is blurry');
    expect(app.providerType, ProviderType.individualDriver);
    expect(app.dateOfBirth, DateTime.parse('1992-04-05'));
    expect(app.serviceCategoryIds, [
      7,
    ], reason: 'rejected services are dropped');
    expect(app.resumeStep, OnboardingStepKey.documents);
    expect(app.stepDone(OnboardingStepKey.vehicles), isTrue);
    expect(app.stepDone(OnboardingStepKey.documents), isFalse);
    expect(app.tasks.map((t) => t.task), [
      OnboardingTask.replaceRejectedDocument,
      OnboardingTask.acceptPolicies,
      OnboardingTask.unknown,
    ]);
    expect(app.tasks.first.reference, 'licence');

    final licence = app.providerDocuments.single;
    expect(licence.state, DocumentStatus.rejected);
    expect(licence.needsAction, isTrue);
    expect(licence.type.requiresNumber, isTrue);
    expect(licence.type.name.resolve('en'), 'Licence');
    expect(licence.document!.rejectionReason, 'Blurry');
    expect(licence.document!.numberMasked, '••••456');

    final registration = app.documentsOfVehicle('veh-1').single;
    expect(registration.isMissing, isTrue);
    expect(registration.type.appliesToVehicle, isTrue);

    final vehicle = app.vehicleById('veh-1')!;
    expect(vehicle.status, VehicleStatus.underReview);
    expect(vehicle.title, 'Toyota Corolla 2020');
    expect(vehicle.capabilities, [
      VehicleCapability.airConditioning,
      VehicleCapability.unknown,
    ]);
    expect(vehicle.photos.single.kind, VehiclePhotoKind.front);
    expect(app.support.phone, '+20100');
    expect(app.pendingPolicyIds, ['pol-1']);
  });

  test('an application that was not started parses to notStarted', () {
    final app = ProviderApplication.fromJson(const {'started': false});

    expect(app.started, isFalse);
    expect(app.resumeStep, OnboardingStepKey.profile);
    expect(app.requiredDocuments, isEmpty);
  });

  test('startApplication sends the profile and an idempotency key', () async {
    await repo.startApplication(
      providerType: ProviderType.courier,
      cityId: 3,
      name: 'Omar',
      dateOfBirth: DateTime(1992, 4, 5),
      serviceCategoryIds: const [7],
      idempotencyKey: 'key-1',
    );

    final req = adapter.requests.single;
    expect(req.method, 'POST');
    expect(req.path, endsWith('/provider/applications'));
    expect(req.headers['Idempotency-Key'], 'key-1');
    expect(req.data, {
      'provider_type': 'courier',
      'city_id': 3,
      'name': 'Omar',
      'date_of_birth': '1992-04-05',
      'service_category_ids': [7],
    });
  });

  test('updateApplication sends only the fields that were given', () async {
    await repo.updateApplication(serviceCategoryIds: const [7, 8]);

    final req = adapter.requests.single;
    expect(req.method, 'PATCH');
    expect(req.data, {
      'service_category_ids': [7, 8],
    });
  });

  test('uploadDocument is multipart with the optional fields', () async {
    final file = File('${Directory.systemTemp.path}/pc_test_doc.jpg')
      ..writeAsBytesSync([1, 2, 3]);
    addTearDown(file.deleteSync);

    final doc = await repo.uploadDocument(
      documentType: 'licence',
      filePath: file.path,
      idempotencyKey: 'key-2',
      number: ' AB-123 ',
      expiresAt: DateTime(2028, 1, 9),
      vehicleId: 'veh-1',
    );

    expect(doc.status, DocumentStatus.pending);
    final req = adapter.requests.single;
    expect(req.headers['Idempotency-Key'], 'key-2');
    expect(req.headers['content-type'], startsWith('multipart/form-data'));
    final form = req.data as FormData;
    final fields = {for (final f in form.fields) f.key: f.value};
    expect(fields, {
      'document_type': 'licence',
      'number': 'AB-123',
      'expires_at': '2028-01-09',
      'vehicle_id': 'veh-1',
    });
    expect(form.files.single.key, 'file');
    expect(form.files.single.value.filename, 'pc_test_doc.jpg');
  });

  test('vehicle photos are multipart with the photo kind', () async {
    final file = File('${Directory.systemTemp.path}/pc_test_photo.jpg')
      ..writeAsBytesSync([1]);
    addTearDown(file.deleteSync);

    await repo.addVehiclePhoto(
      'veh-1',
      filePath: file.path,
      kind: VehiclePhotoKind.back,
      idempotencyKey: 'key-3',
    );

    final req = adapter.requests.single;
    expect(req.path, endsWith('/provider/vehicles/veh-1/photos'));
    final form = req.data as FormData;
    expect({for (final f in form.fields) f.key: f.value}, {'kind': 'back'});
    expect(form.files.single.key, 'photo');
  });

  test('VehicleDraft serialises only what is set', () {
    const draft = VehicleDraft(
      make: 'Kia',
      year: 2022,
      capabilities: [VehicleCapability.childSeat],
    );

    expect(draft.toJson(), {
      'make': 'Kia',
      'year': 2022,
      'capabilities': ['child_seat'],
    });
  });

  test(
    'cities and eligibility parse; reasons are unique with a fallback',
    () async {
      final cities = await repo.cities();
      expect(cities.single.name.resolve('en'), 'Cairo');

      final info = EligibilityInfo.fromJson(const {
        'eligible': false,
        'reasons': [
          {'reason': 'document_missing', 'reference': 'licence'},
          {'reason': 'document_missing', 'reference': 'id_card'},
          {'reason': 'brand_new_reason', 'reference': null},
        ],
      });
      expect(info.eligible, isFalse);
      expect(info.reasons, [
        EligibilityReason.documentMissing,
        EligibilityReason.unknown,
      ]);
      expect(info.issues.length, 3);
    },
  );
}
