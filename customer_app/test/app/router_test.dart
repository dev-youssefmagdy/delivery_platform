import 'package:customer_app/app/router.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  group('computeRedirect', () {
    test('unauthenticated cannot open home', () {
      expect(
        computeRedirect(
          location: Routes.home,
          session: SessionStatus.unauthenticated,
        ),
        Routes.splash,
      );
      expect(
        computeRedirect(location: Routes.home, session: SessionStatus.expired),
        Routes.splash,
      );
    });

    test('authenticated is moved off onboarding/auth', () {
      for (final l in [Routes.auth, Routes.onboarding]) {
        expect(
          computeRedirect(location: l, session: SessionStatus.authenticated),
          Routes.home,
        );
      }
      expect(
        computeRedirect(
          location: Routes.language,
          session: SessionStatus.authenticated,
        ),
        isNull,
      );
    });
  });

  test('destinationAfterSplash order: language, onboarding, auth, home', () {
    String d(SessionStatus s, bool l, bool o) => destinationAfterSplash(
      session: s,
      languageChosen: l,
      onboardingDone: o,
    );
    expect(d(SessionStatus.unauthenticated, false, false), Routes.language);
    expect(d(SessionStatus.unauthenticated, true, false), Routes.onboarding);
    expect(d(SessionStatus.unauthenticated, true, true), Routes.auth);
    expect(d(SessionStatus.authenticated, false, false), Routes.home);
  });
}
