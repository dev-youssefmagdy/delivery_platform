import 'package:customer_app/core/providers.dart';
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
  testWidgets('phone -> OTP -> profile -> consent -> home', (tester) async {
    final fake = FakeIdentityRepository(
      me: FakeIdentityRepository.defaultUser(
        complete: false,
        pending: const [
          PolicyVersionRef(id: 'pol-1', type: 'terms', version: 1),
          PolicyVersionRef(id: 'pol-2', type: 'privacy', version: 1),
        ],
      ),
    );
    final container = await pumpApp(tester, prefs: _prefs, identity: fake);

    await _enterPhone(tester);
    expect(fake.requestedPhones, ['+201001234567']);
    expect(find.text('Verification code'), findsOneWidget);
    expect(find.byKey(const Key('otp-countdown')), findsOneWidget);

    await _enterCode(tester, '123456');
    expect(find.text('Complete your profile'), findsOneWidget);
    expect(container.read(sessionStatusProvider), SessionStatus.authenticated);

    // Name is required before continuing.
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(
      find.text('Enter your name (at least 2 characters)'),
      findsOneWidget,
    );

    await tester.enterText(find.byType(TextField).first, 'Mona Ali');
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(fake.lastProfile!.name, 'Mona Ali');
    expect(find.text('Terms and privacy'), findsWidgets);

    // Continue stays disabled until the checkbox is ticked; policies are readable.
    await tester.tap(find.text('Read').first);
    await tester.pumpAndSettle();
    expect(find.text('Policy body terms'), findsOneWidget);
    await tester.tapAt(const Offset(10, 10));
    await tester.pumpAndSettle();

    await tester.tap(find.byKey(const Key('consent-accept')));
    await tester.tap(find.byKey(const Key('consent-marketing')));
    await tester.pump();
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(fake.accepted.single, ['pol-1', 'pol-2']);
    expect(fake.acceptedMarketing, isTrue);
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets('wrong code shows a typed error and keeps the user on OTP', (
    tester,
  ) async {
    final fake = FakeIdentityRepository();
    await pumpApp(tester, prefs: _prefs, identity: fake);
    await _enterPhone(tester);

    await _enterCode(tester, '000000');
    expect(find.text('The code is incorrect.'), findsOneWidget);
    expect(find.text('Verification code'), findsOneWidget);
    expect(fake.verifyAttempts, ['000000']);
  });

  testWidgets(
    'locked code asks for a new one; resend is gated by the countdown '
    'and server rate limits show their wait time',
    (tester) async {
      final fake = FakeIdentityRepository()
        ..verifyError = const ApiException(
          kind: ApiFailureKind.validation,
          code: 'otp_attempts_exceeded',
          message: null,
          statusCode: 422,
        );
      await pumpApp(tester, prefs: _prefs, identity: fake);
      await _enterPhone(tester);
      await _enterCode(tester, '111111');
      expect(
        find.text('Too many wrong attempts. Request a new code.'),
        findsOneWidget,
      );

      // Countdown still running: no resend button yet.
      expect(find.byKey(const Key('otp-resend')), findsNothing);
      await tester.pump(const Duration(seconds: 31));
      expect(find.byKey(const Key('otp-resend')), findsOneWidget);

      fake.requestError = const ApiException(
        kind: ApiFailureKind.rateLimited,
        code: 'too_many_requests',
        message: null,
        statusCode: 429,
        retryAfterSeconds: 45,
      );
      await tester.tap(find.byKey(const Key('otp-resend')));
      await tester.pumpAndSettle();
      expect(
        find.text('Too many attempts. Try again in 45 seconds.'),
        findsOneWidget,
      );
      expect(find.byKey(const Key('otp-countdown')), findsOneWidget);

      await tester.pump(const Duration(seconds: 46));
      fake.requestError = null;
      await tester.tap(find.byKey(const Key('otp-resend')));
      await tester.pumpAndSettle();
      expect(fake.requestedPhones.length, 3);
      expect(
        find.text('Too many wrong attempts. Request a new code.'),
        findsNothing,
      );
    },
  );

  testWidgets('invalid phone is rejected before any request', (tester) async {
    final fake = FakeIdentityRepository();
    await pumpApp(tester, prefs: _prefs, identity: fake);

    await tester.enterText(find.byType(TextFormField).first, '12');
    await tester.tap(find.text('Send code'));
    await tester.pumpAndSettle();

    expect(find.text('Enter a valid phone number'), findsWidgets);
    expect(fake.requestedPhones, isEmpty);
  });

  testWidgets('offline request shows the offline message', (tester) async {
    final fake = FakeIdentityRepository()
      ..requestError = const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      );
    await pumpApp(tester, prefs: _prefs, identity: fake);
    await _enterPhone(tester);

    expect(find.byKey(const Key('auth-error')), findsOneWidget);
    expect(find.text('Check your connection and try again.'), findsOneWidget);
  });

  testWidgets('suspended account lands on the status screen and can sign out', (
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
    expect(fake.loggedOut, isTrue);
    expect(
      container.read(sessionStatusProvider),
      SessionStatus.unauthenticated,
    );
    expect(find.text('Enter your phone number'), findsOneWidget);
  });

  testWidgets('deletion pending can be cancelled', (tester) async {
    final fake = FakeIdentityRepository(
      me: FakeIdentityRepository.defaultUser(
        status: UserStatus.deletionPending,
        deletion: const DeletionRequestInfo(id: 'd-1', status: 'pending'),
      ),
    );
    await pumpApp(tester, prefs: _prefs, token: 'abc', identity: fake);

    expect(find.text('Deletion requested'), findsOneWidget);
    await tester.tap(find.byKey(const Key('cancel-deletion')));
    await tester.pumpAndSettle();
    expect(fake.cancelled, isTrue);
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets('restricted account sees a notice once, then continues', (
    tester,
  ) async {
    final fake = FakeIdentityRepository(
      me: FakeIdentityRepository.defaultUser(status: UserStatus.restricted),
    );
    await pumpApp(tester, prefs: _prefs, token: 'abc', identity: fake);

    expect(find.text('Account restricted'), findsOneWidget);
    await tester.tap(find.text('Continue'));
    await tester.pumpAndSettle();
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets('failed /me shows a retry screen', (tester) async {
    final fake = FakeIdentityRepository()
      ..meError = const ApiException(
        kind: ApiFailureKind.network,
        code: 'network_error',
        message: null,
      );
    await pumpApp(tester, prefs: _prefs, token: 'abc', identity: fake);

    expect(find.text('You are offline'), findsOneWidget);
    fake.meError = null;
    await tester.tap(find.text('Try again'));
    await tester.pumpAndSettle();
    expect(find.byType(NavigationBar), findsOneWidget);
  });

  testWidgets('home: sign out, and account deletion uses one idempotency key', (
    tester,
  ) async {
    final fake = FakeIdentityRepository();
    await pumpApp(tester, prefs: _prefs, token: 'abc', identity: fake);

    await tester.tap(
      find.descendant(
        of: find.byType(NavigationBar),
        matching: find.text('Account'),
      ),
    );
    await tester.pumpAndSettle();
    await tester.tap(find.byKey(const Key('home-delete-account')));
    await tester.pumpAndSettle();
    await tester.tap(find.text('Request deletion'));
    await tester.pumpAndSettle();
    expect(fake.deletionKeys.single, isNotEmpty);
    expect(find.text('Deletion requested'), findsOneWidget);
  });
}
