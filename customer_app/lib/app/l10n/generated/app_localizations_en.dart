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

  @override
  String get navHome => 'Home';

  @override
  String get navTrips => 'My trips';

  @override
  String get navAccount => 'Account';

  @override
  String get homeRequest => 'Request now';

  @override
  String get homeRequestHintPickup => 'Choose your pickup first.';

  @override
  String get homeRequestHintDestination =>
      'Choose your destination to continue.';

  @override
  String get homeRequestHintService => 'Choose a service to continue.';

  @override
  String get homeRequestHintActive =>
      'You already have an active request. Finish or cancel it before requesting again.';

  @override
  String get homeActiveBooking => 'Your current request';

  @override
  String get homeActiveBookingOpen => 'View';

  @override
  String get bkFormTitle => 'Confirm your request';

  @override
  String get bkFormIncompleteTitle => 'Trip details are incomplete';

  @override
  String get bkFormIncompleteBody =>
      'Choose pickup, destination and service on the home screen.';

  @override
  String get bkFormBackHome => 'Go back';

  @override
  String get bkFormRouteLabel => 'Route map';

  @override
  String get bkEstimateFailed => 'Could not calculate the route right now.';

  @override
  String bkRouteFacts(String km, int minutes) {
    return '$km km · about $minutes min';
  }

  @override
  String get bkPassengers => 'Passengers';

  @override
  String get bkGuidanceTitle => 'Reference price (guidance only)';

  @override
  String bkGuidanceRange(String low, String high) {
    return 'From $low to $high';
  }

  @override
  String bkGuidanceRecommended(String fare) {
    return 'Suggested price: $fare';
  }

  @override
  String get bkGuidanceFactors => 'How it is calculated';

  @override
  String get bkGuidanceNotice =>
      'This is a reference estimate only; you choose the fare you offer.';

  @override
  String get bkGuidanceUnavailable =>
      'No reference price is available for this service. Set the fare that suits you.';

  @override
  String get bkFactorBase => 'Base fare';

  @override
  String get bkFactorDistance => 'Distance';

  @override
  String get bkFactorDuration => 'Duration';

  @override
  String get bkFactorStops => 'Stops';

  @override
  String get bkFactorMinimum => 'Minimum fare';

  @override
  String get bkFareTitle => 'Your offered fare';

  @override
  String bkFareLabel(String currency) {
    return 'Fare ($currency)';
  }

  @override
  String get bkFareRequired => 'Enter the fare you offer.';

  @override
  String get bkFareInvalid => 'The fare is not valid.';

  @override
  String bkFareBelowMin(String min) {
    return 'The fare is below the minimum ($min).';
  }

  @override
  String bkFareAboveMax(String max) {
    return 'The fare is above the maximum ($max).';
  }

  @override
  String bkFareBoundsBoth(String min, String max) {
    return 'Allowed from $min to $max';
  }

  @override
  String bkFareBoundsMin(String min) {
    return 'Minimum $min';
  }

  @override
  String bkFareBoundsMax(String max) {
    return 'Maximum $max';
  }

  @override
  String get bkPaymentTitle => 'Payment method';

  @override
  String get bkPaymentCash => 'Cash';

  @override
  String get bkPaymentOther => 'Other method';

  @override
  String get bkPaymentCashUnavailable =>
      'Cash is not available for this service.';

  @override
  String get bkSummaryTitle => 'Request summary';

  @override
  String bkSummaryFare(String fare) {
    return 'Fare: $fare';
  }

  @override
  String get bkSummaryFareNone => 'not set yet';

  @override
  String bkSummaryPayment(String method) {
    return 'Payment: $method';
  }

  @override
  String get bkSubmit => 'Request now';

  @override
  String get bkStatusDraft => 'Draft';

  @override
  String get bkStatusSearching => 'Looking for offers';

  @override
  String get bkStatusOffersReceived => 'Offers received';

  @override
  String get bkStatusExpired => 'No provider found';

  @override
  String get bkStatusProviderSelected => 'Provider selected';

  @override
  String get bkStatusHeading => 'Provider is on the way';

  @override
  String get bkStatusArrived => 'Provider has arrived';

  @override
  String get bkStatusInProgress => 'Trip in progress';

  @override
  String get bkStatusDeliveryFailed => 'Delivery attempt failed';

  @override
  String get bkStatusCompletedPending => 'Completed, payment pending';

  @override
  String get bkStatusCompleted => 'Completed';

  @override
  String get bkStatusDisputed => 'Disputed';

  @override
  String get bkStatusCancelledCustomer => 'Cancelled by you';

  @override
  String get bkStatusCancelledProvider => 'Cancelled by the provider';

  @override
  String get bkStatusCancelledOps => 'Cancelled by operations';

  @override
  String get bkStatusUnknown => 'Unknown status';

  @override
  String get bkScreenTitle => 'Request';

  @override
  String get bkYourFare => 'Your offered fare';

  @override
  String get bkAgreedFare => 'Agreed fare';

  @override
  String get bkSearching => 'Looking for nearby providers…';

  @override
  String bkSearchingLeft(String time) {
    return 'Searching… $time left';
  }

  @override
  String get bkSearchEnding => 'The search is ending…';

  @override
  String get bkOffersTitle => 'Offers';

  @override
  String get bkSortPrice => 'Lowest price';

  @override
  String get bkSortEta => 'Fastest';

  @override
  String get bkSortRating => 'Top rated';

  @override
  String get bkEditFare => 'Edit fare';

  @override
  String get bkCancelBooking => 'Cancel request';

  @override
  String get bkCancelTrip => 'Cancel trip';

  @override
  String get bkWaitingOffersTitle => 'Waiting for offers';

  @override
  String get bkWaitingOffersBody =>
      'We sent your request to nearby providers. Offers appear here as soon as they arrive.';

  @override
  String get bkNoOffersTitle => 'No offers yet';

  @override
  String get bkNoOffersBody =>
      'The search window ended without offers. You can edit the fare or cancel.';

  @override
  String get bkNoOffersNoGuarantee =>
      'Raising the fare does not guarantee offers.';

  @override
  String get bkOfferProviderFallback => 'Provider';

  @override
  String get bkOfferCounter => 'Counter-offer';

  @override
  String get bkOfferAcceptsFare => 'Accepts your fare';

  @override
  String get bkOfferAccept => 'Accept';

  @override
  String get bkOfferReject => 'Decline';

  @override
  String bkOfferEta(int minutes) {
    return 'Arrives in $minutes min';
  }

  @override
  String bkOfferDistance(String km) {
    return '$km km away';
  }

  @override
  String bkOfferTrips(int count) {
    return '$count trips';
  }

  @override
  String bkOfferRating(String avg, int count) {
    return 'Rating $avg ($count)';
  }

  @override
  String bkOfferRatingNew(String avg, int count) {
    return '$avg ($count) early rating';
  }

  @override
  String bkOfferExpiresIn(String time) {
    return 'Offer expires in $time';
  }

  @override
  String get bkOfferExpired => 'This offer has expired';

  @override
  String get bkAcceptTitle => 'Accept this offer?';

  @override
  String get bkAcceptBody => 'This provider will be assigned to your trip.';

  @override
  String bkAcceptBodyPrice(String price, String name) {
    return 'Accept $name\'s offer at $price?';
  }

  @override
  String get bkEditFareTitle => 'Edit your fare';

  @override
  String get bkEditFareWarn => 'The search will restart with the new fare.';

  @override
  String get bkEditFareWarnOffers =>
      'Note: changing the fare invalidates the current offers and restarts the search.';

  @override
  String get bkEditFareNoGuarantee =>
      'Raising the fare does not guarantee offers.';

  @override
  String get bkEditFareSave => 'Save fare';

  @override
  String get bkFareUpdated => 'Fare updated and search restarted.';

  @override
  String get bkLiveMapLabel => 'Live trip map';

  @override
  String get bkLocationWaiting => 'Waiting for the provider\'s location…';

  @override
  String bkLocationUpdated(int seconds) {
    return 'Location updated $seconds s ago';
  }

  @override
  String bkLocationStale(int minutes) {
    return 'The provider\'s location is old ($minutes min or more). The connection may be weak.';
  }

  @override
  String get bkSupportFlagged =>
      'We noticed something that needs review and our support team is aware.';

  @override
  String get bkPinTitle => 'Trip start PIN';

  @override
  String get bkPinHint =>
      'Tell this PIN to the provider when you board. Do not share it with anyone else.';

  @override
  String get bkPinLocked =>
      'Starting is locked after wrong PIN attempts. Contact support.';

  @override
  String bkFreeWaitLeft(String time) {
    return 'Free waiting time left $time';
  }

  @override
  String get bkFreeWaitOver => 'Free waiting time is over';

  @override
  String get bkStepSelected => 'Provider selected';

  @override
  String get bkStepHeading => 'On the way to you';

  @override
  String get bkStepArrived => 'Arrived at pickup';

  @override
  String get bkStepStarted => 'Trip started';

  @override
  String get bkTimelineTitle => 'Trip progress';

  @override
  String get bkShareTrip => 'Share trip';

  @override
  String get bkShareStop => 'Stop sharing';

  @override
  String get bkShareStopped => 'Trip sharing stopped.';

  @override
  String bkShareMessage(String url) {
    return 'Follow my trip live: $url';
  }

  @override
  String get bkSafety => 'Safety';

  @override
  String get bkSafetyTitle => 'Safety and help';

  @override
  String get bkSafetyNotice =>
      'The full SOS feature is coming soon. In danger, call your local emergency services directly. These are the contacts available now.';

  @override
  String get bkSafetyYourContact => 'Your emergency contact';

  @override
  String get bkSafetySupport => 'Platform support';

  @override
  String get bkSafetyWhatsapp => 'Support WhatsApp';

  @override
  String get bkSafetyNothingConfigured =>
      'No contacts are set up yet. You can add an emergency contact in your profile.';

  @override
  String get bkCancelTitle => 'Cancel request';

  @override
  String get bkCancelReasonTitle => 'Reason for cancelling';

  @override
  String get bkCancelNote => 'Note (optional)';

  @override
  String get bkCancelPickReason => 'Choose a reason.';

  @override
  String get bkCancelContinue => 'Continue';

  @override
  String get bkCancelKeep => 'Keep my request';

  @override
  String get bkCancelFree => 'Cancelling now is free.';

  @override
  String bkCancelFeeDue(String fee) {
    return 'Cancellation fee: $fee';
  }

  @override
  String get bkCancelConfirm => 'Confirm cancellation';

  @override
  String bkCancelConfirmFee(String fee) {
    return 'Confirm and accept $fee fee';
  }

  @override
  String get bkCancelledTitle => 'This request was cancelled';

  @override
  String bkCancelledFee(String fee) {
    return 'Cancellation fee: $fee';
  }

  @override
  String get bkCancelledNoFee => 'No fee.';

  @override
  String get bkReasonChangedMind => 'I changed my mind';

  @override
  String get bkReasonWrongPickup => 'Wrong pickup point';

  @override
  String get bkReasonFoundAnother => 'I found another ride';

  @override
  String get bkReasonDriverTooFar => 'Provider is too far';

  @override
  String get bkReasonDriverAsked => 'Provider asked me to cancel';

  @override
  String get bkReasonWaitedTooLong => 'I waited too long';

  @override
  String get bkReasonOther => 'Other reason';

  @override
  String get bkNoProviderTitle => 'We could not find a provider';

  @override
  String get bkNoProviderBody =>
      'The search ended without offers. You can try again or change the fare.';

  @override
  String get bkTryAgain => 'Try again';

  @override
  String get bkEditPriceAgain => 'Edit fare and retry';

  @override
  String get bkRebook => 'Book again';

  @override
  String get bkReceiptTitle => 'Receipt';

  @override
  String get bkReceiptCode => 'Request number';

  @override
  String get bkReceiptTotal => 'Total';

  @override
  String get bkReceiptPayment => 'Payment method';

  @override
  String get bkReceiptPaymentStatus => 'Payment status';

  @override
  String get bkReceiptDuration => 'Trip duration';

  @override
  String get bkReceiptDistance => 'Planned distance';

  @override
  String bkMinutes(int count) {
    return '$count min';
  }

  @override
  String bkKilometres(String km) {
    return '$km km';
  }

  @override
  String get bkPaymentStatusCashDue => 'Cash due to the provider';

  @override
  String get bkPaymentStatusPaid => 'Paid';

  @override
  String get bkPaymentStatusPending => 'Pending';

  @override
  String get bkPaymentStatusOther => 'Not specified';

  @override
  String get bkRatingTitle => 'Rate your provider';

  @override
  String get bkRatingPickStars => 'Choose a star rating.';

  @override
  String get bkRatingComment => 'Private comment (optional)';

  @override
  String get bkRatingSubmit => 'Submit rating';

  @override
  String get bkRatingThanks => 'Thanks for your rating.';

  @override
  String get bkRating1 => 'Poor';

  @override
  String get bkRating2 => 'Fair';

  @override
  String get bkRating3 => 'Good';

  @override
  String get bkRating4 => 'Very good';

  @override
  String get bkRating5 => 'Excellent';

  @override
  String get bkTagProfessional => 'Professional';

  @override
  String get bkTagCleanVehicle => 'Clean vehicle';

  @override
  String get bkTagSafeDriving => 'Safe driving';

  @override
  String get bkTagGoodCommunication => 'Good communication';

  @override
  String get bkTagOnTime => 'On time';

  @override
  String get bkTagCarefulWithItems => 'Careful with items';

  @override
  String get bkTagLate => 'Late';

  @override
  String get bkTagUnsafeDriving => 'Unsafe driving';

  @override
  String get bkTagRude => 'Rude';

  @override
  String get bkTagDirtyVehicle => 'Dirty vehicle';

  @override
  String get bkReportIssue => 'Report an issue';

  @override
  String get bkReportTitle => 'Report an issue';

  @override
  String get bkReportDescription => 'Describe what happened';

  @override
  String get bkReportDescriptionShort =>
      'Write a description of at least 10 characters.';

  @override
  String get bkReportPickCategory => 'Choose the type of issue.';

  @override
  String get bkReportSubmit => 'Send report';

  @override
  String get bkReportSent => 'Your report was sent and will be reviewed.';

  @override
  String get bkReportSafety => 'Safety';

  @override
  String get bkReportBehaviour => 'Behaviour';

  @override
  String get bkReportFareDispute => 'Fare dispute';

  @override
  String get bkReportRoute => 'Route or delay';

  @override
  String get bkReportLostItem => 'Lost item';

  @override
  String get bkReportOther => 'Other';

  @override
  String get bkErrOutsideArea => 'This location is outside the service area.';

  @override
  String get bkErrCategoryUnavailable =>
      'This service is not available at your location right now.';

  @override
  String get bkErrDropoffRequired =>
      'A destination is required for this service.';

  @override
  String get bkErrFareBelow => 'The fare is below the allowed minimum.';

  @override
  String get bkErrFareAbove => 'The fare is above the allowed maximum.';

  @override
  String get bkErrCapacity =>
      'Passengers or load exceed what this service allows.';

  @override
  String get bkErrPayment =>
      'This payment method is not available for this service.';

  @override
  String get bkErrActiveBooking => 'You already have an active request.';

  @override
  String get bkErrAccount =>
      'This cannot be done because of your account status. Contact support.';

  @override
  String get bkErrMaps =>
      'Maps are temporarily unavailable. Try again shortly.';

  @override
  String get bkErrNotEditable =>
      'The request cannot be edited once a provider is chosen or the search ended.';

  @override
  String get bkErrChangedMeanwhile =>
      'The request changed in the meantime. We refreshed it; try again.';

  @override
  String get bkErrAlreadyAssigned =>
      'A provider was already assigned to this request. We refreshed the screen.';

  @override
  String get bkErrOfferExpired =>
      'This offer expired. We refreshed the offers.';

  @override
  String get bkErrOfferGone =>
      'This offer is no longer available. We refreshed the offers.';

  @override
  String get bkErrSearchEnded =>
      'The search for offers has ended for this request.';

  @override
  String get bkErrNotCancellable => 'This request can no longer be cancelled.';

  @override
  String get bkErrFeeChanged =>
      'The cancellation fee changed. Review the new fee and confirm again.';

  @override
  String get bkErrCancelNote => 'Add a note explaining the reason.';

  @override
  String get bkErrShare => 'This trip cannot be shared right now.';

  @override
  String get bkErrRatingDone => 'You already rated this trip.';

  @override
  String get bkErrRatingClosed => 'This trip cannot be rated now.';

  @override
  String get bkErrReportClosed => 'This trip cannot be reported now.';

  @override
  String get bkErrReportLimit => 'You reached the report limit for this trip.';

  @override
  String get bkErrInProgress =>
      'The previous action is still running. Wait a moment.';

  @override
  String get tripsTitle => 'My trips';

  @override
  String get tripsEmptyTitle => 'No trips yet';

  @override
  String get tripsEmptyBody => 'Your requests and past trips will appear here.';

  @override
  String get tripsEmptyFilteredTitle => 'No trips match these filters';

  @override
  String get tripsEmptyFilteredBody => 'Try changing or clearing the filters.';

  @override
  String get tripsFilter => 'Filter';

  @override
  String get tripsFilterStatus => 'Status';

  @override
  String get tripsFilterService => 'Service';

  @override
  String get tripsFilterAllServices => 'All services';

  @override
  String get tripsFilterFrom => 'From date';

  @override
  String get tripsFilterTo => 'To date';

  @override
  String get tripsStatusCompleted => 'Completed';

  @override
  String get tripsStatusCancelled => 'Cancelled';

  @override
  String get tripsStatusActive => 'In progress';

  @override
  String get tripsApply => 'Apply';

  @override
  String get tripsClearFilters => 'Clear filters';

  @override
  String get notificationsTitle => 'Notifications';

  @override
  String get notificationsMarkAllRead => 'Mark all as read';

  @override
  String get notificationUnavailable =>
      'This is no longer available. You can find the details in your notifications.';

  @override
  String get notificationSettingsTitle => 'Notification settings';

  @override
  String get notificationChannelPush => 'Push notifications';

  @override
  String get notificationChannelSms => 'Text messages';

  @override
  String get notificationChannelEmail => 'Email';

  @override
  String get notificationChannelInApp => 'Inbox';

  @override
  String get notificationEssentialExplain =>
      'Notifications about your trips, account, payments and safety are essential: push stays on for them so you never miss something important. You can choose other channels and the optional categories below.';

  @override
  String get notificationEssentialBadge => 'Essential: push always on';

  @override
  String get notificationOptInBadge => 'Off until you turn it on';

  @override
  String get notificationLockedOn => 'Always on for this category';

  @override
  String get pushStatusReady => 'Push notifications are on for this device.';

  @override
  String get pushStatusPermissionNeeded =>
      'Turn on push notifications to hear about your trips right away.';

  @override
  String get pushStatusDenied =>
      'Push notifications are blocked. Allow them in your phone settings.';

  @override
  String get pushStatusFailed =>
      'We could not set up push notifications. Try again later.';

  @override
  String get pushStatusNotConfigured =>
      'Push notifications are not available in this version. You still get everything in your inbox.';

  @override
  String get pushEnable => 'Turn on';

  @override
  String get pushRationaleTitle => 'Stay informed';

  @override
  String get pushRationaleBody =>
      'We will notify you about trip updates, payments and safety. Notifications on the lock screen stay short and never show personal details.';

  @override
  String get pushRationaleLater => 'Not now';

  @override
  String get accountNotifications => 'Notifications';
}
