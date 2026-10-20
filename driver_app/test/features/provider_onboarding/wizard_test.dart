import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';

const _prefs = {'language_code': 'en', 'onboarding_done': true};

const _network = ApiException(
  kind: ApiFailureKind.network,
  code: 'network_error',
  message: null,
);

ApiException _rule(String code) => ApiException(
  kind: ApiFailureKind.validation,
  code: code,
  message: null,
  statusCode: 422,
);

Future<void> _tall(WidgetTester tester) async {
  tester.view.physicalSize = const Size(900, 2600);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
}

Future<void> _pumpWizard(
  WidgetTester tester,
  FakeProviderRepository provider, {
  FakeIdentityRepository? identity,
  FakePhotoHandler? photos,
}) async {
  await _tall(tester);
  await pumpApp(
    tester,
    prefs: _prefs,
    token: 'abc',
    provider: provider,
    identity: identity,
    photos: photos,
  );
}

Future<void> _tapText(WidgetTester tester, String text) async {
  final finder = find.text(text);
  await tester.ensureVisible(finder.first);
  await tester.tap(finder.first);
  await tester.pumpAndSettle();
}

Future<void> _tapKey(WidgetTester tester, String key) async {
  final finder = find.byKey(Key(key));
  await tester.ensureVisible(finder);
  await tester.tap(finder);
  await tester.pumpAndSettle();
}

void main() {
  group('profile step', () {
    testWidgets('starts at step 1 and validates before it saves', (
      tester,
    ) async {
      final provider = FakeProviderRepository();
      await _pumpWizard(tester, provider);

      expect(find.text('Step 1 of 6'), findsOneWidget);
      expect(find.text('Tell us about yourself'), findsOneWidget);

      await _tapKey(tester, 'profile-save');
      expect(find.text('This field is required'), findsWidgets);
      expect(provider.calls.where((c) => c == 'start'), isEmpty);
    });

    testWidgets(
      'saving creates the application and moves to the services step',
      (tester) async {
        final provider = FakeProviderRepository();
        await _pumpWizard(tester, provider);

        await tester.enterText(find.byType(TextField).first, 'Omar Hassan');
        await _tapKey(tester, 'profile-type');
        await _tapText(tester, 'Individual driver');
        await _tapKey(tester, 'profile-city');
        await _tapText(tester, 'Cairo');
        await _tapKey(tester, 'profile-dob');
        await _tapText(tester, 'OK');
        await _tapKey(tester, 'profile-save');

        expect(provider.started, hasLength(1));
        expect(provider.started.single['name'], 'Omar Hassan');
        expect(provider.started.single['type'], 'individual_driver');
        expect(provider.started.single['city'], 1);
        expect(provider.started.single['dob'], isA<DateTime>());
        expect(provider.started.single['key'], isNotEmpty);
        expect(find.text('Step 2 of 6'), findsOneWidget);
        expect(find.text('What do you want to offer?'), findsOneWidget);
      },
    );

    testWidgets('a server rule is shown inline and the entries are kept', (
      tester,
    ) async {
      final provider = FakeProviderRepository()
        ..writeError = _rule('provider_city_unavailable');
      await _pumpWizard(tester, provider);

      await tester.enterText(find.byType(TextField).first, 'Omar Hassan');
      await _tapKey(tester, 'profile-type');
      await _tapText(tester, 'Courier');
      await _tapKey(tester, 'profile-city');
      await _tapText(tester, 'Giza');
      await _tapKey(tester, 'profile-dob');
      await _tapText(tester, 'OK');
      await _tapKey(tester, 'profile-save');

      expect(find.text('We do not operate in this city yet.'), findsOneWidget);
      expect(find.text('Omar Hassan'), findsOneWidget);
      expect(find.text('Step 1 of 6'), findsOneWidget);
      expect(find.text('Server says no'), findsNothing);
    });

    testWidgets('a company needs no date of birth', (tester) async {
      await _pumpWizard(tester, FakeProviderRepository());

      expect(find.byKey(const Key('profile-dob')), findsOneWidget);
      await _tapKey(tester, 'profile-type');
      await _tapText(tester, 'Company / fleet');

      expect(find.byKey(const Key('profile-dob')), findsNothing);
    });
  });

  group('resume', () {
    testWidgets('opens at the step the server says is next', (tester) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'documents',
          steps: {'profile': true, 'services': true, 'vehicles': true},
          documents: [requiredDocumentJson('licence', state: null)],
        ),
      );
      await _pumpWizard(tester, provider);

      expect(find.text('Step 4 of 6'), findsOneWidget);
      expect(find.text('Required documents'), findsOneWidget);
      expect(find.text('Not uploaded'), findsOneWidget);
    });

    testWidgets('steps can be revisited and Back returns one step', (
      tester,
    ) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'services',
          steps: {'profile': true},
        ),
      );
      await _pumpWizard(tester, provider);

      await _tapKey(tester, 'wizard-step-vehicles');
      expect(find.text('Step 3 of 6'), findsOneWidget);
      await _tapKey(tester, 'wizard-back');
      expect(find.text('Step 2 of 6'), findsOneWidget);
    });

    testWidgets('a server failure shows a retry state that recovers', (
      tester,
    ) async {
      final provider = FakeProviderRepository()..readError = _network;
      await _pumpWizard(tester, provider);

      expect(find.text('You are offline'), findsOneWidget);
      provider.readError = null;
      await _tapText(tester, 'Try again');

      expect(find.text('Tell us about yourself'), findsOneWidget);
    });
  });

  group('services step', () {
    testWidgets('saves the chosen services', (tester) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'services',
          steps: {'profile': true},
        ),
      );
      await _pumpWizard(tester, provider);

      expect(find.text('Economy ride'), findsOneWidget);
      await _tapKey(tester, 'service-1');
      await _tapKey(tester, 'service-2');
      await _tapKey(tester, 'services-save');

      expect(provider.updates.single['services'], [1, 2]);
      expect(find.text('Step 3 of 6'), findsOneWidget);
    });

    testWidgets('cannot continue without a service', (tester) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'services',
          steps: {'profile': true},
        ),
      );
      await _pumpWizard(tester, provider);

      final button = tester.widget<FilledButton>(
        find.descendant(
          of: find.byKey(const Key('services-save')),
          matching: find.byType(FilledButton),
        ),
      );
      expect(button.onPressed, isNull);
    });
  });

  group('vehicles', () {
    FakeProviderRepository vehiclesStep({
      List<Map<String, dynamic>> vehicles = const [],
    }) => FakeProviderRepository(
      application: applicationJson(
        resumeStep: 'vehicles',
        steps: {'profile': true, 'services': true},
        vehicles: vehicles,
        documents: [
          requiredDocumentJson(
            'registration',
            name: 'Vehicle registration',
            vehicleId: vehicles.isEmpty ? null : 'veh-1',
            state: null,
          ),
        ],
      ),
    );

    testWidgets(
      'an empty list invites the driver to add a vehicle and saves it',
      (tester) async {
        final provider = vehiclesStep();
        await _pumpWizard(tester, provider);

        expect(find.text('You have not added a vehicle yet.'), findsOneWidget);
        await _tapKey(tester, 'vehicle-add');
        expect(find.text('Vehicle details'), findsOneWidget);

        await _tapKey(tester, 'vehicle-save');
        expect(find.text('This field is required'), findsWidgets);
        expect(provider.calls, isNot(contains('createVehicle')));

        await _tapKey(tester, 'vehicle-category');
        await _tapText(tester, 'Economy car');
        final fields = find.byType(TextField);
        await tester.enterText(fields.at(0), 'Kia');
        await tester.enterText(fields.at(1), 'Rio');
        await tester.enterText(fields.at(2), '2021');
        await tester.enterText(fields.at(3), 'Blue');
        await tester.enterText(fields.at(4), 'ABC 123');
        await _tapText(tester, 'Child seat');
        await _tapKey(tester, 'vehicle-save');

        final draft = provider.vehicleDrafts.single;
        expect(draft.make, 'Kia');
        expect(draft.year, 2021);
        expect(draft.plate, 'ABC 123');
        expect(draft.vehicleCategoryId, 4);
        expect(draft.capabilities, [VehicleCapability.childSeat]);
        expect(
          find.byKey(const Key('vehicle-add-photo')),
          findsOneWidget,
          reason: 'photos are available once the vehicle exists',
        );
      },
    );

    testWidgets('photos are added with a side and removed again', (
      tester,
    ) async {
      final provider = vehiclesStep(vehicles: [vehicleJson()]);
      final photos = FakePhotoHandler('/tmp/front.jpg');
      await _pumpWizard(tester, provider, photos: photos);

      await _tapKey(tester, 'vehicle-veh-1');
      await _tapKey(tester, 'vehicle-add-photo');
      await _tapText(tester, 'Front');

      expect(photos.picks, 1);
      expect(provider.photos.single, {
        'vehicle': 'veh-1',
        'path': '/tmp/front.jpg',
        'kind': 'front',
      });

      await _tapKey(tester, 'photo-remove-ph-1');
      expect(provider.calls, contains('removePhoto'));
    });

    testWidgets(
      'an incomplete vehicle cannot be submitted and the reason is shown',
      (tester) async {
        final provider = vehiclesStep(vehicles: [vehicleJson()]);
        await _pumpWizard(tester, provider);
        await _tapKey(tester, 'vehicle-veh-1');

        provider.writeError = _rule('vehicle_incomplete');
        await _tapKey(tester, 'vehicle-submit');
        expect(
          find.text(
            'The vehicle is not complete: add the missing photos and documents.',
          ),
          findsOneWidget,
        );

        await _tapKey(tester, 'vehicle-submit');
        expect(provider.calls.where((c) => c == 'submitVehicle'), hasLength(2));
        expect(
          find.text('Vehicles'),
          findsWidgets,
          reason: 'back on the wizard after a successful submit',
        );
        expect(find.text('Under review'), findsOneWidget);
      },
    );

    testWidgets('a vehicle in review is read-only', (tester) async {
      final provider = vehiclesStep(
        vehicles: [vehicleJson(status: 'under_review', editable: false)],
      );
      await _pumpWizard(tester, provider);
      await _tapKey(tester, 'vehicle-veh-1');

      expect(
        find.text('The vehicle cannot be changed while it is being reviewed.'),
        findsOneWidget,
      );
      expect(find.byKey(const Key('vehicle-save')), findsNothing);
      expect(find.byKey(const Key('vehicle-submit')), findsNothing);
    });

    testWidgets('a rejected vehicle shows the reason and can be fixed', (
      tester,
    ) async {
      final provider = vehiclesStep(
        vehicles: [
          vehicleJson(status: 'rejected', reason: 'Plate not visible'),
        ],
      );
      await _pumpWizard(tester, provider);
      expect(find.textContaining('Plate not visible'), findsOneWidget);

      await _tapKey(tester, 'vehicle-veh-1');
      expect(find.byKey(const Key('vehicle-rejection')), findsOneWidget);
      expect(find.byKey(const Key('vehicle-save')), findsOneWidget);
    });
  });

  group('documents', () {
    FakeProviderRepository documentsStep() => FakeProviderRepository(
      application: applicationJson(
        resumeStep: 'documents',
        steps: {'profile': true, 'services': true, 'vehicles': true},
        documents: [
          requiredDocumentJson('licence', name: 'Driving licence', state: null),
          requiredDocumentJson(
            'national_id',
            name: 'National ID',
            state: 'rejected',
            reason: 'Blurry photo',
            number: false,
            expiry: false,
          ),
          requiredDocumentJson(
            'insurance',
            name: 'Insurance',
            state: 'approved',
            expiresAt: '2030-01-05',
          ),
        ],
      ),
    );

    testWidgets('lists every required document with its state and reason', (
      tester,
    ) async {
      await _pumpWizard(tester, documentsStep());

      expect(find.text('Driving licence'), findsOneWidget);
      expect(find.text('Not uploaded'), findsOneWidget);
      expect(find.text('Rejected'), findsOneWidget);
      expect(find.text('Reason: Blurry photo'), findsOneWidget);
      expect(find.text('Approved'), findsOneWidget);
      expect(find.text('Upload'), findsOneWidget);
      expect(find.text('Upload again'), findsOneWidget);
    });

    testWidgets('uploads a document with number and expiry date', (
      tester,
    ) async {
      final provider = documentsStep();
      await _pumpWizard(tester, provider);

      await _tapText(tester, 'Upload');
      expect(find.text('Photo of the document'), findsOneWidget);

      await _tapKey(tester, 'document-send');
      expect(
        find.text('Choose the photo of the document first.'),
        findsOneWidget,
      );

      await _tapKey(tester, 'document-choose-photo');
      await _tapKey(tester, 'document-send');
      expect(
        find.text('This field is required'),
        findsNWidgets(2),
        reason: 'number and expiry date',
      );
      expect(provider.uploads, isEmpty);

      await tester.enterText(find.byType(TextField).first, 'AB-123456');
      await _tapKey(tester, 'document-expires-at');
      await _tapText(tester, 'OK');
      await _tapKey(tester, 'document-send');

      final upload = provider.uploads.single;
      expect(upload['type'], 'licence');
      expect(upload['path'], '/tmp/fake-photo.jpg');
      expect(upload['number'], 'AB-123456');
      expect(upload['expires'], isA<DateTime>());
      expect(upload['vehicle'], isNull);
      expect(find.text('Waiting for review'), findsOneWidget);
      expect(
        find.text('Photo of the document'),
        findsNothing,
        reason: 'back on the documents step',
      );
    });

    testWidgets(
      'a failed upload keeps the form and retries with the same key',
      (tester) async {
        final provider = documentsStep();
        await _pumpWizard(tester, provider);

        await _tapText(tester, 'Upload again');
        await _tapKey(tester, 'document-choose-photo');
        provider.writeError = _network;
        await _tapKey(tester, 'document-send');

        expect(find.byKey(const Key('document-upload-error')), findsOneWidget);
        expect(
          find.text('Check your connection and try again.'),
          findsOneWidget,
        );
        expect(find.text('Try again'), findsOneWidget);

        await _tapText(tester, 'Try again');

        expect(provider.uploadAttemptKeys, hasLength(2));
        expect(
          provider.uploadAttemptKeys.first,
          provider.uploadAttemptKeys.last,
          reason: 'a retry is the same intent',
        );
        expect(provider.uploads.single['type'], 'national_id');
      },
    );

    testWidgets('changing what is sent uses a new idempotency key', (
      tester,
    ) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'documents',
          steps: {'profile': true},
          documents: [
            requiredDocumentJson('licence', state: null, expiry: false),
          ],
        ),
      );
      await _pumpWizard(tester, provider);
      await _tapText(tester, 'Upload');
      await _tapKey(tester, 'document-choose-photo');
      await tester.enterText(find.byType(TextField).first, 'AB-1');
      provider.writeError = _rule('document_infected');
      await _tapKey(tester, 'document-send');
      expect(
        find.text('The file was rejected by the security scan.'),
        findsOneWidget,
      );

      await tester.enterText(find.byType(TextField).first, 'AB-2');
      await _tapKey(tester, 'document-send');

      expect(provider.uploadAttemptKeys, hasLength(2));
      expect(
        provider.uploadAttemptKeys.first,
        isNot(provider.uploadAttemptKeys.last),
      );
    });

    testWidgets('server codes map to localized texts, never server strings', (
      tester,
    ) async {
      final provider = documentsStep();
      await _pumpWizard(tester, provider);
      await _tapText(tester, 'Upload');
      await _tapKey(tester, 'document-choose-photo');
      await tester.enterText(find.byType(TextField).first, 'AB-1');
      await _tapKey(tester, 'document-expires-at');
      await _tapText(tester, 'OK');

      for (final (code, text) in [
        (
          'document_type_not_allowed',
          'This file type is not allowed for this document.',
        ),
        (
          'document_too_large',
          'The file is too large. Choose a smaller photo.',
        ),
        (
          'document_image_too_small',
          'The image is too small. Take a clearer photo.',
        ),
        (
          'file_scanner_unavailable',
          'Files cannot be checked right now. Try again shortly.',
        ),
        (
          'totally_unknown_code',
          'We could not save the change. Please try again.',
        ),
      ]) {
        provider.writeError = _rule(code);
        await _tapKey(tester, 'document-send');
        expect(find.text(text), findsOneWidget, reason: code);
      }
    });
  });

  group('declarations', () {
    testWidgets('the driver accepts the pending terms', (tester) async {
      final identity = FakeIdentityRepository(
        me: FakeIdentityRepository.defaultUser(
          pending: const [
            PolicyVersionRef(id: 'pol-d', type: 'driver_terms', version: 1),
            PolicyVersionRef(id: 'pol-t', type: 'terms', version: 2),
          ],
        ),
      );
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'declarations',
          steps: {
            'profile': true,
            'services': true,
            'vehicles': true,
            'documents': true,
          },
        ),
      );
      await _pumpWizard(tester, provider, identity: identity);

      expect(find.text('I have read and accept: Driver terms'), findsOneWidget);
      final accept = find.descendant(
        of: find.byKey(const Key('declarations-accept')),
        matching: find.byType(FilledButton),
      );
      expect(tester.widget<FilledButton>(accept).onPressed, isNull);

      await _tapKey(tester, 'policy-read-driver_terms');
      expect(find.text('Policy body driver_terms'), findsOneWidget);

      await _tapKey(tester, 'policy-driver_terms');
      expect(
        tester.widget<FilledButton>(accept).onPressed,
        isNull,
        reason: 'both must be ticked',
      );
      await _tapKey(tester, 'policy-terms');
      expect(tester.widget<FilledButton>(accept).onPressed, isNotNull);
      await _tapKey(tester, 'declarations-accept');

      expect(identity.accepted.single, ['pol-d', 'pol-t']);
      expect(find.text('Step 6 of 6'), findsOneWidget);
    });

    testWidgets('nothing pending shows that and continues', (tester) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'declarations',
          steps: {'profile': true},
        ),
      );
      await _pumpWizard(tester, provider);

      expect(find.byKey(const Key('declarations-none')), findsOneWidget);
    });
  });

  group('review and submit', () {
    testWidgets(
      'lists what is open and blocks submit until the server allows it',
      (tester) async {
        final provider = FakeProviderRepository(
          application: applicationJson(
            resumeStep: 'review',
            steps: {'profile': true, 'services': true},
            tasks: [
              {'task': 'add_vehicle', 'reference': null, 'vehicle_id': null},
              {
                'task': 'upload_document',
                'reference': 'licence',
                'vehicle_id': null,
              },
            ],
            documents: [
              requiredDocumentJson(
                'licence',
                name: 'Driving licence',
                state: null,
              ),
            ],
          ),
        );
        await _pumpWizard(tester, provider);

        expect(find.text('Add a vehicle'), findsOneWidget);
        expect(find.text('Upload: Driving licence'), findsOneWidget);
        final submit = find.descendant(
          of: find.byKey(const Key('review-submit')),
          matching: find.byType(FilledButton),
        );
        expect(tester.widget<FilledButton>(submit).onPressed, isNull);

        await tester.tap(find.byType(TextButton).first);
        await tester.pumpAndSettle();
        expect(
          find.text('Step 3 of 6'),
          findsOneWidget,
          reason: 'the task leads to the vehicles step',
        );
      },
    );

    testWidgets(
      'submitting sends the application and shows the verification status',
      (tester) async {
        final provider = FakeProviderRepository(
          application: applicationJson(
            resumeStep: 'review',
            steps: {
              'profile': true,
              'services': true,
              'vehicles': true,
              'documents': true,
              'declarations': true,
            },
            canSubmit: true,
            tasks: [
              {
                'task': 'submit_application',
                'reference': null,
                'vehicle_id': null,
              },
            ],
          ),
        );
        await _pumpWizard(tester, provider);

        expect(find.byKey(const Key('review-ready')), findsOneWidget);
        await _tapKey(tester, 'review-submit');

        expect(provider.submitted, hasLength(1));
        expect(find.text('We are reviewing your application'), findsWidgets);
        expect(find.byKey(const Key('wizard-counter')), findsNothing);
      },
    );

    testWidgets('an incomplete application answer from the server is shown', (
      tester,
    ) async {
      final provider = FakeProviderRepository(
        application: applicationJson(
          resumeStep: 'review',
          steps: {'profile': true},
          canSubmit: true,
          tasks: [
            {
              'task': 'submit_application',
              'reference': null,
              'vehicle_id': null,
            },
          ],
        ),
      )..writeError = _rule('application_incomplete');
      await _pumpWizard(tester, provider);

      await _tapKey(tester, 'review-submit');

      expect(
        find.text(
          'The application is not complete yet. Finish the remaining tasks.',
        ),
        findsOneWidget,
      );
    });
  });
}
