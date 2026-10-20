import '../auth/session.dart';
import '../enums/user_status.dart';
import 'identity_models.dart';

/// Where a signed-in user stands; the router maps each stage to a screen.
enum AuthStage {
  /// No usable session (never signed in, signed out or expired).
  signedOut,

  /// Session present, `/me` still loading.
  loading,

  /// Session present, `/me` failed (offline, server error): retry screen.
  error,

  /// Suspended, deleted, deletion pending, verification required, or a
  /// restricted account that has not acknowledged the notice yet.
  accountStatus,

  /// Name missing (customer app only).
  needsProfile,

  /// A newer policy version has to be accepted (customer app only).
  needsConsent,

  /// Everything done: the app's main area is available.
  ready,
}

/// Pure stage rules (unit tested). [requireProfileAndConsent] is false for the
/// driver app, which stops after phone verification until P2.1.
AuthStage computeAuthStage({
  required SessionStatus session,
  required bool meLoading,
  required bool meFailed,
  required MeUser? me,
  required bool requireProfileAndConsent,
  bool restrictedAcknowledged = false,
}) {
  if (session != SessionStatus.authenticated) return AuthStage.signedOut;
  if (me == null) {
    return meFailed && !meLoading ? AuthStage.error : AuthStage.loading;
  }
  switch (me.status) {
    case UserStatus.suspended:
    case UserStatus.deleted:
    case UserStatus.deletionPending:
    case UserStatus.verificationRequired:
      return AuthStage.accountStatus;
    case UserStatus.restricted:
      if (!restrictedAcknowledged) return AuthStage.accountStatus;
    case UserStatus.active:
    case UserStatus.unknown:
      break;
  }
  if (requireProfileAndConsent) {
    if (!me.profileComplete) return AuthStage.needsProfile;
    if (me.pendingConsents.isNotEmpty) return AuthStage.needsConsent;
  }
  return AuthStage.ready;
}
