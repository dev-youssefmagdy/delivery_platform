import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Maps an auth failure to ARB text (no server strings in widgets). The
/// server's stable `code` decides; unknown codes fall back to a generic text.
String authErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  switch (error.code) {
    case 'otp_invalid':
      return l10n.otpErrorInvalid;
    case 'otp_expired':
      return l10n.otpErrorExpired;
    case 'otp_attempts_exceeded':
      return l10n.otpErrorLocked;
    case 'otp_resend_too_soon':
    case 'too_many_requests':
      return l10n.otpErrorTooManyRequests(error.retryAfterSeconds ?? 60);
    case 'otp_delivery_failed':
      return l10n.otpErrorDeliveryFailed;
    case 'account_suspended':
      return l10n.statusSuspendedBody;
    case 'account_deleted':
      return l10n.statusDeletedBody;
    case 'validation_failed':
      return l10n.authPhoneInvalid;
  }
  return l10n.errorGenericBody;
}

/// True for codes after which the current OTP can no longer succeed.
bool otpNeedsNewCode(Object error) =>
    error is ApiException &&
    (error.code == 'otp_expired' || error.code == 'otp_attempts_exceeded');
