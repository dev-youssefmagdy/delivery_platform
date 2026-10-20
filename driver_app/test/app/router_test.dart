import 'package:driver_app/app/router.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  group('computeRedirect', () {
    test('signed out cannot open signed-in screens', () {
      for (final l in [Routes.providerOnboarding, Routes.accountStatus]) {
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

    test('verified drivers go to the onboarding entry point', () {
      for (final stage in [
        AuthStage.ready,
        AuthStage.needsProfile,
        AuthStage.needsConsent,
      ]) {
        expect(
          computeRedirect(location: Routes.auth, stage: stage),
          Routes.providerOnboarding,
        );
        expect(
          computeRedirect(location: Routes.providerOnboarding, stage: stage),
          isNull,
        );
      }
    });

    test('blocked accounts are pinned to the status screen', () {
      for (final stage in [AuthStage.accountStatus, AuthStage.error]) {
        expect(
          computeRedirect(location: Routes.providerOnboarding, stage: stage),
          Routes.accountStatus,
        );
        expect(
          computeRedirect(location: Routes.accountStatus, stage: stage),
          isNull,
        );
        expect(
          computeRedirect(location: Routes.language, stage: stage),
          isNull,
        );
      }
    });

    test('while /me loads, guarded screens wait on the splash', () {
      expect(
        computeRedirect(
          location: Routes.providerOnboarding,
          stage: AuthStage.loading,
        ),
        Routes.splash,
      );
    });
  });

  test('destinationAfterSplash order: language, onboarding, auth, onboarding entry', () {
    String d(SessionStatus s, bool l, bool o) => destinationAfterSplash(
      session: s,
      languageChosen: l,
      onboardingDone: o,
    );
    expect(d(SessionStatus.unauthenticated, false, false), Routes.language);
    expect(d(SessionStatus.unauthenticated, true, false), Routes.onboarding);
    expect(d(SessionStatus.unauthenticated, true, true), Routes.auth);
    expect(
      d(SessionStatus.authenticated, false, false),
      Routes.providerOnboarding,
    );
  });
}
