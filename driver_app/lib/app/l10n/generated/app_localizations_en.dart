// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'Platform Driver';

  @override
  String get splashLoading => 'Loading…';

  @override
  String get languageTitle => 'Choose your language';

  @override
  String get languageSubtitle =>
      'You can change the language later in settings.';

  @override
  String get languageArabicName => 'العربية';

  @override
  String get languageEnglishName => 'English';

  @override
  String get actionContinue => 'Continue';

  @override
  String get actionSkip => 'Skip';

  @override
  String get actionNext => 'Next';

  @override
  String get actionGetStarted => 'Get started';

  @override
  String get actionRetry => 'Try again';

  @override
  String get onboarding1Title => 'Receive requests';

  @override
  String get onboarding1Body => 'Work when you like and get requests near you.';

  @override
  String get onboarding2Title => 'Make your offer';

  @override
  String get onboarding2Body =>
      'Accept the customer\'s fare or propose your own.';

  @override
  String get onboarding3Title => 'Earn transparently';

  @override
  String get onboarding3Body =>
      'See your earnings and commission clearly on every trip.';

  @override
  String get authTitle => 'Sign in';

  @override
  String get homeTitle => 'Home';

  @override
  String get homeChangeLanguage => 'Change language';

  @override
  String get offlineBanner => 'No internet connection';

  @override
  String get errorGenericTitle => 'Something went wrong';

  @override
  String get errorGenericBody =>
      'We could not complete the request. Please try again.';

  @override
  String get errorOfflineTitle => 'You are offline';

  @override
  String get errorOfflineBody => 'Check your connection and try again.';

  @override
  String get errorSessionExpiredTitle => 'Session expired';

  @override
  String get errorSessionExpiredBody => 'Sign in again to continue.';

  @override
  String errorRequestId(String id) {
    return 'Reference: $id';
  }

  @override
  String get emptyTitle => 'Nothing here yet';

  @override
  String get loadingLabel => 'Loading';

  @override
  String get maintenanceTitle => 'We are under maintenance';

  @override
  String get maintenanceBody =>
      'We will be back soon. Sorry for the inconvenience.';

  @override
  String get forceUpdateTitle => 'Update required';

  @override
  String get forceUpdateBody =>
      'Please update the app to the latest version to continue.';

  @override
  String get validationRequired => 'This field is required';

  @override
  String get actionCheckAgain => 'Check again';

  @override
  String get supportContactLabel => 'Support';

  @override
  String forceUpdateRequiredVersion(String version) {
    return 'Required version: $version';
  }

  @override
  String get authPhoneTitle => 'Enter your phone number';

  @override
  String get authPhoneSubtitle =>
      'We will send you a verification code by SMS.';

  @override
  String get authPhoneLabel => 'Phone number';

  @override
  String get authPhoneInvalid => 'Enter a valid phone number';

  @override
  String get authSendCode => 'Send code';

  @override
  String get otpTitle => 'Verification code';

  @override
  String otpSubtitle(int length, String phone) {
    return 'Enter the $length-digit code sent to $phone';
  }

  @override
  String otpResendIn(int seconds) {
    return 'Resend code in $seconds s';
  }

  @override
  String get otpResend => 'Resend code';

  @override
  String get otpChangeNumber => 'Change number';

  @override
  String get otpErrorInvalid => 'The code is incorrect.';

  @override
  String get otpErrorExpired => 'The code has expired. Request a new one.';

  @override
  String get otpErrorLocked => 'Too many wrong attempts. Request a new code.';

  @override
  String otpErrorTooManyRequests(int seconds) {
    return 'Too many attempts. Try again in $seconds seconds.';
  }

  @override
  String get otpErrorDeliveryFailed =>
      'We could not send the code. Try again later.';

  @override
  String get statusSuspendedTitle => 'Account suspended';

  @override
  String get statusSuspendedBody =>
      'Your account is suspended. Please contact support.';

  @override
  String get statusRestrictedTitle => 'Account restricted';

  @override
  String get statusRestrictedBody =>
      'Some features are limited on your account. Contact support for details.';

  @override
  String get statusDeletionPendingTitle => 'Deletion requested';

  @override
  String get statusDeletionPendingBody =>
      'Your account is waiting to be deleted. You can cancel the request while it is pending.';

  @override
  String get statusVerificationTitle => 'Verification required';

  @override
  String get statusVerificationBody =>
      'Your account needs to be verified before you can continue. Contact support.';

  @override
  String get statusDeletedTitle => 'Account deleted';

  @override
  String get statusDeletedBody => 'This account has been deleted.';

  @override
  String get statusCancelDeletion => 'Cancel deletion request';

  @override
  String get homeLogout => 'Sign out';

  @override
  String get providerOnboardingTitle => 'Driver application';

  @override
  String get providerOnboardingBadge => 'Coming soon';

  @override
  String get providerOnboardingHeadline => 'Your phone number is verified';

  @override
  String get providerOnboardingBody =>
      'The driver application steps (documents, vehicle and review) open in an upcoming update. There is nothing more to do for now.';

  @override
  String providerOnboardingVerifiedPhone(String phone) {
    return 'Verified number: $phone';
  }
}
