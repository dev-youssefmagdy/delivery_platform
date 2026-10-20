import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

MeUser user({
  UserStatus status = UserStatus.active,
  bool complete = true,
  List<PolicyVersionRef> pending = const [],
}) => MeUser(
  id: 'u',
  status: status,
  profileComplete: complete,
  pendingConsents: pending,
);

AuthStage stage(
  MeUser? me, {
  SessionStatus session = SessionStatus.authenticated,
  bool loading = false,
  bool failed = false,
  bool full = true,
  bool ack = false,
}) => computeAuthStage(
  session: session,
  meLoading: loading,
  meFailed: failed,
  me: me,
  requireProfileAndConsent: full,
  restrictedAcknowledged: ack,
);

void main() {
  test('signed out for every non-authenticated session', () {
    for (final s in [
      SessionStatus.unknown,
      SessionStatus.unauthenticated,
      SessionStatus.expired,
    ]) {
      expect(stage(user(), session: s), AuthStage.signedOut);
    }
  });

  test('loading and error while /me is unavailable', () {
    expect(stage(null, loading: true), AuthStage.loading);
    expect(stage(null, failed: true), AuthStage.error);
  });

  test('blocking statuses win over profile and consent', () {
    for (final s in [
      UserStatus.suspended,
      UserStatus.deleted,
      UserStatus.deletionPending,
      UserStatus.verificationRequired,
    ]) {
      expect(stage(user(status: s, complete: false)), AuthStage.accountStatus);
    }
  });

  test('restricted shows the notice once, then continues', () {
    expect(stage(user(status: UserStatus.restricted)), AuthStage.accountStatus);
    expect(
      stage(user(status: UserStatus.restricted), ack: true),
      AuthStage.ready,
    );
  });

  test('profile, then consent, then ready', () {
    const policy = PolicyVersionRef(id: 'p', type: 'terms', version: 1);
    expect(stage(user(complete: false)), AuthStage.needsProfile);
    expect(stage(user(pending: [policy])), AuthStage.needsConsent);
    expect(stage(user()), AuthStage.ready);
  });

  test('driver app stops after verification', () {
    const policy = PolicyVersionRef(id: 'p', type: 'terms', version: 1);
    expect(
      stage(user(complete: false, pending: [policy]), full: false),
      AuthStage.ready,
    );
  });
}
