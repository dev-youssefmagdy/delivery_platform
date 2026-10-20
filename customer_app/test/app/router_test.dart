import 'package:customer_app/app/router.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  group('computeRedirect', () {
    test('signed out cannot open signed-in screens', () {
      for (final l in [
        Routes.home,
        Routes.profile,
        Routes.consent,
        Routes.accountStatus,
      ]) {
        expect(
          computeRedirect(location: l, stage: AuthStage.signedOut),
          Routes.splash,
        );
      }
      for (final l in [Routes.auth, Routes.otp, Routes.language]) {
        expect(
          computeRedirect(location: l, stage: AuthStage.signedOut),
          isNull,
        );
      }
    });

    test('service details is only for a ready account', () {
      expect(
        computeRedirect(
          location: Routes.serviceDetails,
          stage: AuthStage.ready,
        ),
        isNull,
      );
      expect(
        computeRedirect(
          location: Routes.serviceDetails,
          stage: AuthStage.signedOut,
        ),
        Routes.splash,
      );
      expect(
        computeRedirect(
          location: Routes.serviceDetails,
          stage: AuthStage.needsConsent,
        ),
        Routes.consent,
      );
    });

    test('each signed-in stage is pinned to its screen', () {
      const expected = {
        AuthStage.needsProfile: Routes.profile,
        AuthStage.needsConsent: Routes.consent,
        AuthStage.accountStatus: Routes.accountStatus,
        AuthStage.error: Routes.accountStatus,
        AuthStage.ready: Routes.home,
      };
      expected.forEach((stage, route) {
        expect(computeRedirect(location: route, stage: stage), isNull);
        for (final other in [
          Routes.home,
          Routes.profile,
          Routes.consent,
          Routes.auth,
          Routes.otp,
          Routes.onboarding,
        ]) {
          if (other == route) continue;
          expect(computeRedirect(location: other, stage: stage), route);
        }
        expect(
          computeRedirect(location: Routes.language, stage: stage),
          isNull,
        );
      });
    });

    test('while /me loads, guarded screens wait on the splash', () {
      expect(
        computeRedirect(location: Routes.home, stage: AuthStage.loading),
        Routes.splash,
      );
      expect(
        computeRedirect(location: Routes.splash, stage: AuthStage.loading),
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
