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
  String get authUnavailableTitle => 'Sign-in is not available yet';

  @override
  String get authUnavailableBody =>
      'Phone sign-in and driver registration will arrive in an upcoming update.';

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
}
