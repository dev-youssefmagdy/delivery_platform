import 'package:driver_app/core/providers.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import '../../fakes.dart';
import '../../helpers.dart';

const _prefs = {'language_code': 'en', 'onboarding_done': true};

Future<void> _enterPhone(WidgetTester tester) async {
  await tester.enterText(find.byType(TextFormField).first, '1001234567');
  await tester.pump();
  await tester.tap(find.text('Send code'));
  await tester.pumpAndSettle();
}

Future<void> _enterCode(WidgetTester tester, String code) async {
  await tester.enterText(find.byType(TextField).last, code);
  await tester.pumpAndSettle();
}

void main() {
  testWidgets('phone -> OTP stops at the labelled onboarding entry point', (
    tester,
  ) async {
    // Even with an incomplete profile and pending consents the driver app
    // does not ask for them: the real onboarding is P2.1.
    final fake = FakeIdentityRepository(
      me: FakeIdentityRepository.defaultUser(
        complete: false,
        pending: const [
          PolicyVersionRef(id: 'pol-1', type: 'terms', version: 1),
        ],
      ),
    );
    final container = await pumpApp(tester, prefs: _prefs, identity: fake);

    await _enterPhone(tester);
    expect(fake.requestedPhones, ['+201001234567']);
    await _enterCode(tester, '123456');

    expect(container.read(sessionStatusProvider), SessionStatus.authenticated);
    expect(find.text('Driver application'), findsOneWidget);
    expect(find.text('Coming soon'), findsOneWidget);
    expect(find.byKey(const Key('onboarding-coming')), findsOneWidget);
  });

  testWidgets('wrong code and lock-out show typed errors', (tester) async {
    final fake = FakeIdentityRepository();
    await pumpApp(tester, prefs: _prefs, identity: fake);
    await _enterPhone(tester);

    await _enterCode(tester, '000000');
    expect(find.text('The code is incorrect.'), findsOneWidget);

    fake.verifyError = const ApiException(
      kind: ApiFailureKind.validation,
      code: 'otp_attempts_exceeded',
      message: null,
      statusCode: 422,
    );
    await _enterCode(tester, '111111');
    expect(
      find.text('Too many wrong attempts. Request a new code.'),
      findsOneWidget,
    );
    await tester.pump(const Duration(seconds: 31));
    expect(find.byKey(const Key('otp-resend')), findsOneWidget);
  });

  testWidgets('suspended driver sees the status screen and can sign out', (
    tester,
  ) async {
    final fake = FakeIdentityRepository(
      me: FakeIdentityRepository.defaultUser(status: UserStatus.suspended),
    );
    final container = await pumpApp(
      tester,
      prefs: _prefs,
      token: 'abc',
      identity: fake,
    );

    expect(find.text('Account suspended'), findsOneWidget);
    await tester.tap(find.byKey(const Key('status-logout')));
    await tester.pumpAndSettle();
    expect(
      container.read(sessionStatusProvider),
      SessionStatus.unauthenticated,
    );
    expect(find.text('Enter your phone number'), findsOneWidget);
  });

  testWidgets('signed-in driver lands on onboarding entry and can sign out', (
    tester,
  ) async {
    final fake = FakeIdentityRepository();
    await pumpApp(tester, prefs: _prefs, token: 'abc', identity: fake);

    expect(find.byKey(const Key('onboarding-coming')), findsOneWidget);
    await tester.tap(find.byKey(const Key('onboarding-logout')));
    await tester.pumpAndSettle();
    expect(fake.loggedOut, isTrue);
    expect(find.text('Enter your phone number'), findsOneWidget);
  });
}
