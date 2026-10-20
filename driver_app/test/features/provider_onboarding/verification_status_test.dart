import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';

const _prefs = {'language_code': 'en', 'onboarding_done': true};

Future<FakeProviderRepository> _pump(
  WidgetTester tester,
  Map<String, dynamic> application, {
  Map<String, Object> prefs = _prefs,
  void Function(FakeProviderRepository)? configure,
}) async {
  tester.view.physicalSize = const Size(900, 3000);
  tester.view.devicePixelRatio = 1;
  addTearDown(tester.view.reset);
  final provider = FakeProviderRepository(application: application);
  configure?.call(provider);
  await pumpApp(tester, prefs: prefs, token: 'abc', provider: provider);
  return provider;
}

void main() {
  testWidgets('under review: waits, nothing to do, no way to edit', (
    tester,
  ) async {
    await _pump(
      tester,
      applicationJson(
        status: 'under_review',
        editable: false,
        steps: {
          'profile': true,
          'services': true,
          'vehicles': true,
          'documents': true,
        },
        submittedAt: '2026-10-20T10:00:00Z',
        documents: [requiredDocumentJson('licence', state: 'pending')],
        vehicles: [vehicleJson(status: 'under_review', editable: false)],
        support: {
          'phone': '+201000000',
          'email': 'help@example.test',
          'whatsapp': null,
        },
      ),
    );

    expect(find.byKey(const Key('verification-headline')), findsOneWidget);
    expect(find.text('We are reviewing your application'), findsWidgets);
    expect(find.text('Waiting for review'), findsOneWidget);
    expect(find.byKey(const Key('verification-continue')), findsNothing);
    expect(find.byKey(const Key('wizard-counter')), findsNothing);
    expect(find.text('Call support: +201000000'), findsOneWidget);
    expect(find.text('Email support: help@example.test'), findsOneWidget);
  });

  testWidgets(
    'more information: shows the reviewer reason, tasks and rejection reasons, then resubmits via the wizard',
    (tester) async {
      await _pump(
        tester,
        applicationJson(
          status: 'more_information_required',
          statusReason: 'The licence photo is not readable',
          resumeStep: 'documents',
          steps: {'profile': true, 'services': true, 'vehicles': true},
          tasks: [
            {
              'task': 'replace_rejected_document',
              'reference': 'licence',
              'vehicle_id': null,
            },
          ],
          documents: [
            requiredDocumentJson(
              'licence',
              name: 'Driving licence',
              state: 'rejected',
              reason: 'Blurry photo of the licence',
            ),
          ],
        ),
      );

      expect(find.text('We need more information'), findsWidgets);
      expect(find.text('The licence photo is not readable'), findsOneWidget);
      expect(
        find.text('Replace the rejected document: Driving licence'),
        findsOneWidget,
      );
      expect(
        find.textContaining('Blurry photo of the licence'),
        findsOneWidget,
      );

      await tester.tap(find.byKey(const Key('verification-continue')));
      await tester.pumpAndSettle();

      expect(find.byKey(const Key('wizard-counter')), findsOneWidget);
      expect(
        find.text('Step 4 of 6'),
        findsOneWidget,
        reason: 'resumes at the documents step',
      );
    },
  );

  testWidgets('rejected: shows the reason and allows a new submission', (
    tester,
  ) async {
    await _pump(
      tester,
      applicationJson(
        status: 'rejected',
        statusReason: 'Documents do not match the applicant',
        resumeStep: 'review',
      ),
    );

    expect(find.text('Your application was not accepted'), findsWidgets);
    expect(find.text('Documents do not match the applicant'), findsOneWidget);
    expect(find.byKey(const Key('verification-continue')), findsOneWidget);
  });

  testWidgets('approved and eligible', (tester) async {
    await _pump(
      tester,
      applicationJson(
        status: 'approved',
        editable: false,
        steps: {'profile': true},
      ),
    );

    expect(find.text('Your application is approved'), findsWidgets);
    expect(find.byKey(const Key('eligibility-ok')), findsOneWidget);
  });

  testWidgets(
    'approved but not ready: lists each blocking reason once, and expiring documents',
    (tester) async {
      await _pump(
        tester,
        applicationJson(
          status: 'approved',
          editable: false,
          steps: {'profile': true},
          expiring: [
            requiredDocumentJson(
              'licence',
              name: 'Driving licence',
              state: 'approved',
              expiresAt: '2026-11-01',
            ),
          ],
          documents: [
            requiredDocumentJson(
              'licence',
              name: 'Driving licence',
              state: 'expired',
              expiresAt: '2026-09-01',
            ),
          ],
          tasks: [
            {
              'task': 'renew_expired_document',
              'reference': 'licence',
              'vehicle_id': null,
            },
          ],
        ),
        configure: (p) => p.eligibilityInfo = const EligibilityInfo(
          eligible: false,
          issues: [
            EligibilityIssueInfo(
              reason: EligibilityReason.documentExpired,
              reference: 'licence',
            ),
            EligibilityIssueInfo(
              reason: EligibilityReason.documentExpired,
              reference: 'id',
            ),
            EligibilityIssueInfo(reason: EligibilityReason.noApprovedVehicle),
          ],
        ),
      );

      expect(find.byKey(const Key('eligibility-blocked')), findsOneWidget);
      expect(find.text('A required document has expired.'), findsOneWidget);
      expect(find.text('There is no approved vehicle.'), findsOneWidget);
      expect(
        find.text('Renew the expired document: Driving licence'),
        findsOneWidget,
      );
      expect(find.text('Documents expiring soon'), findsOneWidget);
      expect(find.byKey(const Key('expiring-licence')), findsOneWidget);
      expect(find.byKey(const Key('verification-update')), findsOneWidget);
    },
  );

  testWidgets('suspended accounts see the matching text', (tester) async {
    await _pump(
      tester,
      applicationJson(status: 'temporarily_suspended', editable: false),
    );
    expect(find.text('Your account is temporarily suspended'), findsWidgets);
    expect(find.byKey(const Key('verification-continue')), findsNothing);
  });

  testWidgets('permanently suspended accounts see the matching text', (
    tester,
  ) async {
    await _pump(
      tester,
      applicationJson(status: 'permanently_suspended', editable: false),
    );
    expect(find.text('Your account is permanently suspended'), findsWidgets);
  });

  testWidgets('pull-to-refresh style reload picks up a new status', (
    tester,
  ) async {
    final provider = await _pump(
      tester,
      applicationJson(status: 'under_review', editable: false),
    );
    expect(find.text('We are reviewing your application'), findsWidgets);

    provider.json = applicationJson(status: 'approved', editable: false);
    await tester.tap(find.byKey(const Key('onboarding-refresh')));
    await tester.pumpAndSettle();

    expect(find.text('Your application is approved'), findsWidgets);
  });

  testWidgets('a failed refresh keeps what is on screen and says so', (
    tester,
  ) async {
    final provider = await _pump(
      tester,
      applicationJson(status: 'under_review', editable: false),
    );
    provider.readError = const ApiException(
      kind: ApiFailureKind.network,
      code: 'network_error',
      message: null,
    );

    await tester.tap(find.byKey(const Key('onboarding-refresh')));
    await tester.pumpAndSettle();

    expect(find.text('We are reviewing your application'), findsWidgets);
    expect(find.text('Check your connection and try again.'), findsOneWidget);
  });

  testWidgets('Arabic renders right-to-left with Arabic texts', (tester) async {
    await _pump(
      tester,
      applicationJson(
        status: 'more_information_required',
        statusReason: 'صورة الرخصة غير واضحة',
        documents: [
          requiredDocumentJson(
            'licence',
            name: 'رخصة القيادة',
            state: 'rejected',
            reason: 'صورة غير واضحة',
          ),
        ],
      ),
      prefs: const {'language_code': 'ar', 'onboarding_done': true},
    );

    expect(find.text('نحتاج إلى معلومات إضافية'), findsWidgets);
    expect(find.text('صورة الرخصة غير واضحة'), findsOneWidget);
    expect(
      directionOf(tester, find.byKey(const Key('verification-headline'))),
      TextDirection.rtl,
    );
  });
}
