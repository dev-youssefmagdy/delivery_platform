// ignore: unused_import
import 'package:intl/intl.dart' as intl;

import 'app_localizations.dart';

// ignore_for_file: type=lint

/// The translations for English (`en`).
class AppLocalizationsEn extends AppLocalizations {
  AppLocalizationsEn([String locale = 'en']) : super(locale);

  @override
  String get appName => 'Platform Customer';

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
  String get onboarding1Title => 'Name your fare';

  @override
  String get onboarding1Body =>
      'Request your destination and propose the price that suits you.';

  @override
  String get onboarding2Title => 'Compare offers';

  @override
  String get onboarding2Body =>
      'Nearby drivers send you offers; pick the one you like.';

  @override
  String get onboarding3Title => 'Travel safely';

  @override
  String get onboarding3Body =>
      'Follow your trip live and share it with someone you trust.';

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
  String get profileTitle => 'Complete your profile';

  @override
  String get profileSubtitle =>
      'Tell us your name so drivers know who they are meeting.';

  @override
  String get profileNameLabel => 'Full name';

  @override
  String get profileNameInvalid => 'Enter your name (at least 2 characters)';

  @override
  String get profileEmailLabel => 'Email (optional)';

  @override
  String get profileEmailInvalid => 'Enter a valid email address';

  @override
  String get consentTitle => 'Terms and privacy';

  @override
  String get consentSubtitle => 'Please read and accept to continue.';

  @override
  String get consentRead => 'Read';

  @override
  String get consentAccept =>
      'I accept the terms of use and the privacy policy';

  @override
  String get consentMarketing => 'Send me offers and news (optional)';

  @override
  String get consentPolicyTerms => 'Terms of use';

  @override
  String get consentPolicyPrivacy => 'Privacy policy';

  @override
  String get consentPolicyOther => 'Policy';

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
  String homeGreeting(String name) {
    return 'Hello, $name';
  }

  @override
  String get homeLogout => 'Sign out';

  @override
  String get homeDeleteAccount => 'Delete my account';

  @override
  String get deleteAccountTitle => 'Delete your account?';

  @override
  String get deleteAccountBody =>
      'We will review your request. Some data may be kept where the law requires it. You can cancel while the request is pending.';

  @override
  String get deleteAccountConfirm => 'Request deletion';

  @override
  String get actionCancel => 'Cancel';

  @override
  String get catalogTitle => 'Choose a service';

  @override
  String get catalogEmptyTitle => 'No services available';

  @override
  String get catalogEmptyBody =>
      'Services will appear here as soon as they are available in your area.';

  @override
  String catalogCapacityPassengers(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count passengers',
      one: '1 passenger',
    );
    return '$_temp0';
  }

  @override
  String catalogCapacityLuggage(int count) {
    String _temp0 = intl.Intl.pluralLogic(
      count,
      locale: localeName,
      other: '$count bags',
      one: '1 bag',
    );
    return '$_temp0';
  }

  @override
  String catalogCapacityWeight(String kg) {
    return 'Up to $kg kg';
  }

  @override
  String catalogFareFrom(String amount) {
    return 'From $amount';
  }

  @override
  String get catalogNegotiable => 'Name your fare';

  @override
  String get catalogSelectedLabel => 'Selected service';

  @override
  String get catalogDetailsButton => 'Service details';

  @override
  String get catalogDetailsTitle => 'Service details';

  @override
  String get catalogDetailsIntro =>
      'Tell us about your request so drivers can offer the right price.';

  @override
  String get catalogDetailsNone => 'This service needs no extra details.';

  @override
  String get catalogDetailsSave => 'Save details';

  @override
  String get catalogDetailsSaved => 'Details saved';

  @override
  String get catalogDetailsFixErrors =>
      'Please correct the highlighted fields.';

  @override
  String catalogVehiclesLabel(String names) {
    return 'Vehicles: $names';
  }

  @override
  String formTooShort(num limit) {
    return 'Enter at least $limit characters';
  }

  @override
  String formTooLong(num limit) {
    return 'Enter at most $limit characters';
  }

  @override
  String formTooSmall(num limit) {
    return 'Must be at least $limit';
  }

  @override
  String formTooLarge(num limit) {
    return 'Must be at most $limit';
  }

  @override
  String get formNotANumber => 'Enter a valid number';

  @override
  String get formNotWhole => 'Enter a whole number';

  @override
  String get formInvalidChoice => 'Choose a valid option';

  @override
  String formTooFewItems(num limit) {
    return 'Choose at least $limit';
  }

  @override
  String formTooManyItems(num limit) {
    return 'Choose at most $limit';
  }

  @override
  String get formInvalidPhone =>
      'Enter a valid phone number with the country code';

  @override
  String get formInvalidDate => 'Enter a valid date or time';

  @override
  String get formIncomplete => 'Complete every part of this field';

  @override
  String get formYes => 'Yes';

  @override
  String get formNo => 'No';

  @override
  String get formChoose => 'Choose…';

  @override
  String get formContactName => 'Name';

  @override
  String get formContactPhone => 'Phone (with country code)';

  @override
  String get formLength => 'Length (cm)';

  @override
  String get formWidth => 'Width (cm)';

  @override
  String get formHeight => 'Height (cm)';

  @override
  String get formKilograms => 'kg';

  @override
  String get formQuantity => 'Quantity';

  @override
  String get formAddPhoto => 'Add photo';

  @override
  String get formRemovePhoto => 'Remove photo';

  @override
  String get formPhotoCamera => 'Camera';

  @override
  String get formPhotoGallery => 'Gallery';

  @override
  String get formPickDate => 'Select date';

  @override
  String get formPickTime => 'Select time';

  @override
  String get mapPickupLabel => 'Pickup';

  @override
  String get mapDestinationLabel => 'Destination';

  @override
  String get mapPickupHint => 'Where from?';

  @override
  String get mapDestinationHint => 'Where to?';

  @override
  String get mapSwap => 'Swap pickup and destination';

  @override
  String get mapMyLocation => 'My location';

  @override
  String get mapCurrentLocation => 'Current location';

  @override
  String get mapPinnedLocation => 'Pinned location';

  @override
  String get mapApproximate =>
      'Approximate position. The exact address is not confirmed.';

  @override
  String get mapLocating => 'Finding your location…';

  @override
  String get mapRationaleTitle => 'Use your location?';

  @override
  String get mapRationaleBody =>
      'We use your location to set your pickup point and show the services near you. You can also search for an address instead.';

  @override
  String get mapRationaleAllow => 'Allow location';

  @override
  String get mapRationaleNotNow => 'Not now';

  @override
  String get mapPermissionDenied =>
      'Location is off. Allow it, or choose your pickup manually.';

  @override
  String get mapPermissionBlocked =>
      'Location is blocked for this app. Open settings to allow it, or choose your pickup manually.';

  @override
  String get mapServiceDisabled =>
      'Your device location is switched off. Turn it on, or choose your pickup manually.';

  @override
  String get mapLocateFailed =>
      'We could not get your position. Choose your pickup manually.';

  @override
  String get mapOpenSettings => 'Open settings';

  @override
  String get mapChoosePickup => 'Choose pickup manually';

  @override
  String get mapOutsideTitle => 'Outside our service area';

  @override
  String get mapOutsideBody =>
      'We do not serve this pickup location yet. Choose another pickup point.';

  @override
  String get mapAreaCheckFailed =>
      'We could not check service availability here.';

  @override
  String get mapRestrictedPickup =>
      'Pickups here may be restricted. Follow the posted pickup points.';

  @override
  String mapServing(String city) {
    return 'Serving $city';
  }

  @override
  String get mapsUnavailable =>
      'Maps are temporarily unavailable. Please try again shortly.';

  @override
  String get searchPickupTitle => 'Choose pickup';

  @override
  String get searchDestinationTitle => 'Choose destination';

  @override
  String get searchPlaceTitle => 'Find a place';

  @override
  String get searchFieldHint => 'Search for a place or address';

  @override
  String get searchMinChars => 'Type at least 2 characters to search.';

  @override
  String get searchEmptyTitle => 'No places found';

  @override
  String get searchEmptyBody =>
      'Try another name, or choose the point on the map.';

  @override
  String get searchOnMap => 'Choose on map';

  @override
  String get searchUseCurrent => 'Use current location';

  @override
  String get searchSavedHeader => 'Saved places';

  @override
  String get searchResultsHeader => 'Results';

  @override
  String get searchUnavailable =>
      'Search is temporarily unavailable. You can still choose a point on the map.';

  @override
  String get pinInstruction => 'Move the map to place the pin';

  @override
  String get pinConfirm => 'Confirm location';

  @override
  String get pinResolving => 'Finding the address…';

  @override
  String get pinNoAddress => 'No address found for this point.';

  @override
  String get pinLookupFailed =>
      'Address lookup is unavailable. You can still use this point.';

  @override
  String get pinOutside => 'This point is outside our service area.';

  @override
  String get savedPlacesTitle => 'Saved places';

  @override
  String get savedPlacesAdd => 'Add a place';

  @override
  String get savedPlacesEmptyTitle => 'No saved places yet';

  @override
  String get savedPlacesEmptyBody => 'Save Home and Work to book faster.';

  @override
  String get savedPlaceTypeHome => 'Home';

  @override
  String get savedPlaceTypeWork => 'Work';

  @override
  String get savedPlaceTypeOther => 'Other';

  @override
  String get savedPlaceNameField => 'Name';

  @override
  String get savedPlaceTypeField => 'Type';

  @override
  String get savedPlaceNameRequired => 'Enter a name';

  @override
  String get savedPlaceSave => 'Save';

  @override
  String get savedPlaceDeleteTitle => 'Delete this place?';

  @override
  String get savedPlaceDeleteBody =>
      'It will be removed from your saved places.';

  @override
  String get savedPlaceDelete => 'Delete';

  @override
  String get savedPlaceTypeTaken =>
      'You already saved a place of this type. Delete it first.';

  @override
  String get savedPlaceLimit =>
      'You reached the maximum number of saved places.';

  @override
  String get homeSavedPlaces => 'Saved places';
}
