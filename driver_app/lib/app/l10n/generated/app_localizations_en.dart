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
  String get actionBack => 'Back';

  @override
  String get actionSave => 'Save';

  @override
  String get actionClose => 'Close';

  @override
  String get actionRefresh => 'Refresh';

  @override
  String get actionRemove => 'Remove';

  @override
  String get actionEdit => 'Edit';

  @override
  String get actionDelete => 'Delete';

  @override
  String get actionCancel => 'Cancel';

  @override
  String get photoCamera => 'Take a photo';

  @override
  String get photoGallery => 'Choose from gallery';

  @override
  String get obStepProfile => 'Profile';

  @override
  String get obStepServices => 'Services';

  @override
  String get obStepVehicles => 'Vehicles';

  @override
  String get obStepDocuments => 'Documents';

  @override
  String get obStepDeclarations => 'Declarations';

  @override
  String get obStepReview => 'Review';

  @override
  String obStepCounter(int current, int total) {
    return 'Step $current of $total';
  }

  @override
  String get obStepDone => 'Done';

  @override
  String get obProfileTitle => 'Tell us about yourself';

  @override
  String get obProfileIntro =>
      'We need this to review your application. You can change it before you submit.';

  @override
  String get obProfileName => 'Full name';

  @override
  String get obProfileDob => 'Date of birth';

  @override
  String get obProfileDobPick => 'Choose a date';

  @override
  String get obProfileType => 'Provider type';

  @override
  String get obProfileCity => 'City';

  @override
  String get obProfileCityPick => 'Select your city';

  @override
  String get obProfileSave => 'Save and continue';

  @override
  String get obProfileCitiesEmpty => 'No cities are available right now.';

  @override
  String get obTypeIndividualDriver => 'Individual driver';

  @override
  String get obTypeCourier => 'Courier';

  @override
  String get obTypeMovingCrew => 'Moving crew / driver';

  @override
  String get obTypeCommercialTransport => 'Commercial transport provider';

  @override
  String get obTypeCompanyFleet => 'Company / fleet';

  @override
  String get obServicesTitle => 'What do you want to offer?';

  @override
  String get obServicesIntro =>
      'Choose at least one service. You can add more later; each service is reviewed before it is approved.';

  @override
  String get obServicesEmpty => 'No services are available right now.';

  @override
  String get obServicesSave => 'Save and continue';

  @override
  String get obServiceStatusApproved => 'Approved';

  @override
  String get obServiceStatusRejected => 'Rejected';

  @override
  String get obServiceStatusRequested => 'Pending review';

  @override
  String get obVehiclesTitle => 'Your vehicles';

  @override
  String get obVehiclesIntro =>
      'Add at least one vehicle with its photos and documents, then submit it for review.';

  @override
  String get obVehiclesEmpty => 'You have not added a vehicle yet.';

  @override
  String get obVehicleAdd => 'Add a vehicle';

  @override
  String get obVehicleFormTitle => 'Vehicle details';

  @override
  String get obVehicleCategory => 'Vehicle category';

  @override
  String get obVehicleCategoryPick => 'Select a category';

  @override
  String get obVehicleMake => 'Make';

  @override
  String get obVehicleModel => 'Model';

  @override
  String get obVehicleYear => 'Year';

  @override
  String get obVehicleColor => 'Colour';

  @override
  String get obVehiclePlate => 'Plate number';

  @override
  String get obVehiclePassengers => 'Passenger seats';

  @override
  String get obVehiclePayload => 'Payload (kg)';

  @override
  String get obVehicleVolume => 'Cargo volume (m³)';

  @override
  String get obVehicleCapabilities => 'Special capabilities';

  @override
  String get obVehicleSave => 'Save vehicle';

  @override
  String get obVehiclePhotos => 'Photos';

  @override
  String obVehiclePhotosHint(int count) {
    return 'Add at least $count photos (front and back).';
  }

  @override
  String get obVehiclePhotoAdd => 'Add a photo';

  @override
  String get obVehiclePhotoKindTitle => 'Which side of the vehicle?';

  @override
  String get obPhotoKindFront => 'Front';

  @override
  String get obPhotoKindBack => 'Back';

  @override
  String get obPhotoKindSide => 'Side';

  @override
  String get obPhotoKindInterior => 'Interior';

  @override
  String get obPhotoKindOther => 'Other';

  @override
  String get obVehicleDocuments => 'Vehicle documents';

  @override
  String get obVehicleSubmit => 'Submit vehicle for review';

  @override
  String get obVehicleDeleteTitle => 'Remove this vehicle?';

  @override
  String get obVehicleDeleteBody =>
      'The vehicle, its photos and uploaded documents are deleted.';

  @override
  String get obVehicleSubmitted => 'The vehicle was submitted for review.';

  @override
  String get obVehicleLockedHint =>
      'The vehicle cannot be changed while it is being reviewed.';

  @override
  String get obVehicleStatusDraft => 'Draft';

  @override
  String get obVehicleStatusUnderReview => 'Under review';

  @override
  String get obVehicleStatusApproved => 'Approved';

  @override
  String get obVehicleStatusRejected => 'Rejected';

  @override
  String get obVehicleStatusSuspended => 'Suspended';

  @override
  String get obVehicleStatusInactive => 'Inactive';

  @override
  String obVehicleRejectedReason(String reason) {
    return 'Rejected: $reason';
  }

  @override
  String get obCapAirConditioning => 'Air conditioning';

  @override
  String get obCapChildSeat => 'Child seat';

  @override
  String get obCapWheelchair => 'Wheelchair accessible';

  @override
  String get obCapPets => 'Pets allowed';

  @override
  String get obCapLargeLuggage => 'Large luggage';

  @override
  String get obCapCoveredCargo => 'Covered cargo area';

  @override
  String get obCapTailLift => 'Tail lift';

  @override
  String get obCapRefrigerated => 'Refrigerated';

  @override
  String get obCapMovingEquipment => 'Moving equipment';

  @override
  String get obDocumentsTitle => 'Required documents';

  @override
  String get obDocumentsIntro =>
      'Upload a clear photo of each document. Documents are reviewed by hand and the status of each one shows here.';

  @override
  String get obDocumentsEmpty =>
      'No documents are required from you right now.';

  @override
  String get obDocOptional => 'Optional';

  @override
  String get obDocStateMissing => 'Not uploaded';

  @override
  String get obDocStatePending => 'Waiting for review';

  @override
  String get obDocStateApproved => 'Approved';

  @override
  String get obDocStateRejected => 'Rejected';

  @override
  String get obDocStateExpired => 'Expired';

  @override
  String obDocExpires(String date) {
    return 'Expires $date';
  }

  @override
  String obDocReason(String reason) {
    return 'Reason: $reason';
  }

  @override
  String get obDocUpload => 'Upload';

  @override
  String get obDocReplace => 'Upload again';

  @override
  String get obDocRenew => 'Renew';

  @override
  String get obDocFormTitle => 'Upload a document';

  @override
  String get obDocPhoto => 'Photo of the document';

  @override
  String get obDocChoosePhoto => 'Take or choose a photo';

  @override
  String get obDocChangePhoto => 'Change photo';

  @override
  String get obDocNumber => 'Document number';

  @override
  String get obDocIssuedAt => 'Issue date';

  @override
  String get obDocExpiresAt => 'Expiry date';

  @override
  String get obDocSend => 'Send the document';

  @override
  String get obDocSent =>
      'The document was uploaded and will be reviewed soon.';

  @override
  String obDocHint(String types, int size) {
    return 'Allowed: $types. Maximum $size KB.';
  }

  @override
  String get obDocPhotoRequired => 'Choose the photo of the document first.';

  @override
  String get obDeclTitle => 'Declarations and terms';

  @override
  String get obDeclIntro =>
      'Read and accept the terms to complete your application. The version and the time you accept are recorded.';

  @override
  String obDeclAccept(String title) {
    return 'I have read and accept: $title';
  }

  @override
  String get obDeclAcceptAll => 'Accept and continue';

  @override
  String get obDeclRead => 'Read the text';

  @override
  String get obDeclNone => 'There is nothing waiting for your acceptance.';

  @override
  String get obDeclAccepted => 'You accepted all the terms.';

  @override
  String get obPolicyTerms => 'Terms of use';

  @override
  String get obPolicyPrivacy => 'Privacy policy';

  @override
  String get obPolicyDriverTerms => 'Driver terms';

  @override
  String get obPolicyOther => 'Policy';

  @override
  String get obReviewTitle => 'Review and submit';

  @override
  String get obReviewReady =>
      'Everything is ready. Submit your application for our team to review.';

  @override
  String get obReviewTodo => 'Still to do:';

  @override
  String get obReviewSubmit => 'Submit application';

  @override
  String get obReviewSubmitted => 'Your application was submitted for review.';

  @override
  String get obGoToStep => 'Go';

  @override
  String get obTaskCompleteProfile => 'Complete your profile';

  @override
  String get obTaskSelectServices => 'Choose the services you offer';

  @override
  String get obTaskAddVehicle => 'Add a vehicle';

  @override
  String get obTaskAddVehiclePhotos => 'Add vehicle photos';

  @override
  String get obTaskSubmitVehicle => 'Submit the vehicle for review';

  @override
  String obTaskUploadDocument(String name) {
    return 'Upload: $name';
  }

  @override
  String obTaskReplaceDocument(String name) {
    return 'Replace the rejected document: $name';
  }

  @override
  String obTaskRenewDocument(String name) {
    return 'Renew the expired document: $name';
  }

  @override
  String get obTaskFixVehicle => 'Fix the vehicle and submit it again';

  @override
  String get obTaskAcceptPolicies => 'Accept the driver terms';

  @override
  String get obTaskSubmitApplication => 'Submit your application';

  @override
  String get vsTitle => 'Verification status';

  @override
  String get vsUnderReviewTitle => 'We are reviewing your application';

  @override
  String get vsUnderReviewBody =>
      'Our team is checking your documents and vehicle. We will tell you the result; there is nothing to do now.';

  @override
  String get vsMoreInfoTitle => 'We need more information';

  @override
  String get vsMoreInfoBody =>
      'Fix the items below, then submit your application again.';

  @override
  String get vsRejectedTitle => 'Your application was not accepted';

  @override
  String get vsRejectedBody =>
      'You can fix the reasons below and submit your application again.';

  @override
  String get vsApprovedTitle => 'Your application is approved';

  @override
  String get vsApprovedBody =>
      'You are approved as a provider. Keep your documents valid.';

  @override
  String get vsSuspendedTitle => 'Your account is temporarily suspended';

  @override
  String get vsSuspendedBody =>
      'You cannot work until the suspension ends. Contact support to learn more.';

  @override
  String get vsPermanentTitle => 'Your account is permanently suspended';

  @override
  String get vsPermanentBody =>
      'You cannot work with this account. Contact support to learn more.';

  @override
  String get vsDeletionTitle => 'Your account deletion is being processed';

  @override
  String get vsDeletionBody =>
      'Your account deletion request was received and is being processed.';

  @override
  String get vsDraftTitle => 'Finish your application';

  @override
  String get vsDraftBody =>
      'You have not submitted your application yet. Continue where you left off.';

  @override
  String get vsReviewerReason => 'Message from the review team';

  @override
  String get vsTasksTitle => 'What to do next';

  @override
  String get vsNoTasks => 'Nothing is required from you.';

  @override
  String get vsDocumentsTitle => 'Documents';

  @override
  String get vsVehiclesTitle => 'Vehicles';

  @override
  String get vsExpiringTitle => 'Documents expiring soon';

  @override
  String get vsEligibilityTitle => 'Ready to work';

  @override
  String get vsEligible => 'You meet every requirement to work.';

  @override
  String get vsNotEligible => 'You are not ready to work yet:';

  @override
  String get vsContinue => 'Continue the application';

  @override
  String get vsUpdate => 'Update the application';

  @override
  String get vsSupportTitle => 'Need help?';

  @override
  String vsSupportPhone(String phone) {
    return 'Call support: $phone';
  }

  @override
  String vsSupportEmail(String email) {
    return 'Email support: $email';
  }

  @override
  String vsSubmittedOn(String date) {
    return 'Submitted on $date';
  }

  @override
  String get erNotApproved => 'Your application is not approved yet.';

  @override
  String get erProviderSuspended => 'Your account is suspended.';

  @override
  String get erAccountBlocked => 'The account is blocked or restricted.';

  @override
  String get erDocumentMissing => 'A required document is missing.';

  @override
  String get erDocumentNotApproved =>
      'A required document is not approved yet.';

  @override
  String get erDocumentExpired => 'A required document has expired.';

  @override
  String get erNoApprovedVehicle => 'There is no approved vehicle.';

  @override
  String get erVehicleNotApproved => 'The vehicle is not approved.';

  @override
  String get erServiceNotApproved => 'No approved service for this category.';

  @override
  String get erVehicleCategoryMismatch =>
      'The vehicle category does not fit this service.';

  @override
  String get erUnknown => 'A requirement is not met.';

  @override
  String get obErrGeneric => 'We could not save the change. Please try again.';

  @override
  String get obErrValidation => 'Check the details you entered and try again.';

  @override
  String obErrUnderage(int age) {
    return 'You must be at least $age years old.';
  }

  @override
  String get obErrCityUnavailable => 'We do not operate in this city yet.';

  @override
  String get obErrServiceUnavailable =>
      'One of the selected services is not available.';

  @override
  String get obErrApplicationLocked =>
      'The application cannot be changed in its current status.';

  @override
  String get obErrApplicationExists => 'You already started an application.';

  @override
  String get obErrIncomplete =>
      'The application is not complete yet. Finish the remaining tasks.';

  @override
  String get obErrDocTypeNotAllowed =>
      'This file type is not allowed for this document.';

  @override
  String get obErrDocTooLarge =>
      'The file is too large. Choose a smaller photo.';

  @override
  String get obErrDocUnreadable => 'The file is empty or cannot be read.';

  @override
  String get obErrDocTooSmall =>
      'The image is too small. Take a clearer photo.';

  @override
  String get obErrDocInfected => 'The file was rejected by the security scan.';

  @override
  String get obErrScannerDown =>
      'Files cannot be checked right now. Try again shortly.';

  @override
  String get obErrNumberRequired => 'The document number is required.';

  @override
  String get obErrExpiryRequired =>
      'The expiry date is required for this document.';

  @override
  String get obErrAlreadyExpired => 'This document has already expired.';

  @override
  String get obErrDatesInconsistent =>
      'The expiry date must be after the issue date.';

  @override
  String get obErrIssueFuture => 'The issue date cannot be in the future.';

  @override
  String get obErrPlateTaken =>
      'A vehicle with this plate is already registered.';

  @override
  String get obErrPlateInvalid => 'The plate number is not valid.';

  @override
  String get obErrYearInvalid => 'The model year is not valid.';

  @override
  String get obErrVehicleNotEditable =>
      'The vehicle cannot be changed in its current status.';

  @override
  String get obErrVehicleIncomplete =>
      'The vehicle is not complete: add the missing photos and documents.';

  @override
  String get obErrPhotoLimit => 'You reached the maximum number of photos.';

  @override
  String get obErrVehicleLimit => 'You reached the maximum number of vehicles.';

  @override
  String get obErrPolicyStale =>
      'The terms changed. Refresh the page and try again.';

  @override
  String get erLocationStale =>
      'Your location is missing or out of date. Turn on location and try again.';

  @override
  String get erActiveJobExists => 'You already have an active job.';

  @override
  String get erCashDebtLimitExceeded =>
      'Your unsettled commission is over the allowed limit. Settle it to go online.';

  @override
  String get avTitle => 'Availability';

  @override
  String get avOnlineTitle => 'You are online';

  @override
  String get avOfflineTitle => 'You are offline';

  @override
  String get avOnlineBody => 'New requests near you will appear here.';

  @override
  String get avOfflineBody => 'Turn on availability to receive requests.';

  @override
  String get avSwitchLabel => 'Receive requests';

  @override
  String get avReasonsTitle => 'You cannot go online yet:';

  @override
  String get avVehicleLabel => 'Vehicle';

  @override
  String get avNoLocationNote =>
      'Your location is shared only while you are online or on a job.';

  @override
  String get avTrackingOff => 'Location sharing is off';

  @override
  String get avTrackingIdle =>
      'Sharing your location while you wait for requests';

  @override
  String get avTrackingToPickup =>
      'Sharing your location on the way to the pickup';

  @override
  String get avTrackingActive => 'Sharing your location during the job';

  @override
  String avTrackingEvery(int seconds) {
    return 'Updates every $seconds s';
  }

  @override
  String get avUploadFailed => 'Could not send your location. Retrying.';

  @override
  String get avRealtimeConnected => 'Live';

  @override
  String get avRealtimeConnecting => 'Connecting';

  @override
  String get avRealtimeDisconnected => 'No live connection, reconnecting';

  @override
  String get avPermissionDenied => 'Allow location access to go online.';

  @override
  String get avPermissionForever =>
      'Location is blocked for this app. Open settings to allow it.';

  @override
  String get avServiceDisabled =>
      'Location services are turned off on this device.';

  @override
  String get avOpenSettings => 'Open settings';

  @override
  String get avPositionUnavailable =>
      'We could not get your position. Try again where the signal is better.';

  @override
  String get avOutsideArea => 'You are outside the service areas.';

  @override
  String get avVehicleNotUsable => 'This vehicle cannot be used right now.';

  @override
  String get avOfflineStale =>
      'You were taken offline because your location stopped updating.';

  @override
  String get avOfflineIneligible =>
      'You were taken offline because you no longer meet the requirements.';

  @override
  String get avOfflineAccount =>
      'You were taken offline because your account status changed.';

  @override
  String get avRationaleTitle => 'Why we need your location';

  @override
  String get avRationaleBody =>
      'We use your location only while you are online and during jobs: to show you to nearby customers, guide you to the pickup, track the trip and keep it safe. We stop as soon as you go offline. While you are online a notification stays on the screen and location may keep updating when the app is in the background.';

  @override
  String get avRationaleContinue => 'Continue';

  @override
  String get avRationaleCancel => 'Not now';

  @override
  String get avOfflineConfirmTitle => 'You have a job in progress';

  @override
  String get avOfflineConfirmBody =>
      'Going offline stops new requests. Your current job continues and keeps sharing your location until it ends.';

  @override
  String get avOfflineConfirmAction => 'Go offline';

  @override
  String get avStayOnline => 'Stay online';

  @override
  String get avActiveJobBanner => 'A job is in progress';

  @override
  String get avNotificationTitle => 'You are online';

  @override
  String get avNotificationText => 'Sharing your location to receive requests';

  @override
  String get actionDone => 'Done';

  @override
  String get jbTabHome => 'Home';

  @override
  String get jbTabRequests => 'Requests';

  @override
  String get jbTabOffers => 'My offers';

  @override
  String get jbTabHistory => 'History';

  @override
  String get jbApplicationEntry => 'Application and documents';

  @override
  String get jbApplicationEntryBody =>
      'Check your review status, documents and vehicles.';

  @override
  String get jbAlertSound => 'Sound for new requests';

  @override
  String get jbAlertVibration => 'Vibrate for new requests';

  @override
  String get jbGoOnlineTitle => 'You are offline';

  @override
  String get jbGoOnlineBody =>
      'Go online from Home to receive nearby requests.';

  @override
  String get jbGoOnlineAction => 'Go to Home';

  @override
  String get jbNotEligibleTitle => 'You cannot receive requests right now';

  @override
  String get jbNoRequestsTitle => 'No requests right now';

  @override
  String get jbNoRequestsBody =>
      'We will alert you as soon as a nearby request arrives. Keep the app open.';

  @override
  String get jbRequestExpired => 'Expired';

  @override
  String jbExpiresIn(String time) {
    return 'Expires in $time';
  }

  @override
  String get jbCustomerFare => 'Customer fare';

  @override
  String jbPickupArea(String area) {
    return 'Pickup area: $area';
  }

  @override
  String jbDropoffArea(String area) {
    return 'Destination area: $area';
  }

  @override
  String get jbAreaUnknown => 'Not specified';

  @override
  String jbAwayKm(String distance) {
    return '$distance away';
  }

  @override
  String jbKm(String value) {
    return '$value km';
  }

  @override
  String jbMinutes(int count) {
    return '$count min';
  }

  @override
  String jbPassengers(int count) {
    return '$count passengers';
  }

  @override
  String jbStops(int count) {
    return '$count stops';
  }

  @override
  String jbLuggage(int count) {
    return '$count bags';
  }

  @override
  String jbWeightKg(String kg) {
    return '$kg kg';
  }

  @override
  String jbScheduledAt(String time) {
    return 'Scheduled: $time';
  }

  @override
  String get jbRestrictedPickup => 'Restricted pickup zone';

  @override
  String get jbAcceptFare => 'Accept customer fare';

  @override
  String get jbCounterOffer => 'Counter-offer';

  @override
  String get jbSkip => 'Skip';

  @override
  String get jbReviseOffer => 'Revise offer';

  @override
  String get jbWithdraw => 'Withdraw offer';

  @override
  String get jbWithdrawn => 'Offer withdrawn.';

  @override
  String get jbOfferSent => 'Your offer was sent. Waiting for the customer.';

  @override
  String jbMyOfferAmount(String amount) {
    return 'Your offer: $amount';
  }

  @override
  String get jbCounterTitle => 'Your counter-offer';

  @override
  String jbCustomerFareIs(String fare) {
    return 'Customer fare: $fare';
  }

  @override
  String jbAllowedRange(String min, String max) {
    return 'Allowed from $min to $max';
  }

  @override
  String jbAmountBelow(String min) {
    return 'Below the minimum ($min)';
  }

  @override
  String jbAmountAbove(String max) {
    return 'Above the maximum ($max)';
  }

  @override
  String get jbAmountSame =>
      'This is the customer fare. Use the accept button instead.';

  @override
  String get jbSendOffer => 'Send offer';

  @override
  String get jbKeypadBackspace => 'Delete';

  @override
  String get jbKeypadDecimal => 'Decimal point';

  @override
  String get jbPreviewGross => 'Offered amount';

  @override
  String get jbPreviewCommission => 'Platform commission';

  @override
  String get jbPreviewNet => 'Your net earning';

  @override
  String get jbNoOffersTitle => 'No offers yet';

  @override
  String get jbNoOffersBody => 'Your offers on requests appear here.';

  @override
  String get jbOfferPending => 'Pending';

  @override
  String get jbOfferSelected => 'Selected';

  @override
  String get jbOfferRejected => 'Not chosen';

  @override
  String get jbOfferExpired => 'Expired';

  @override
  String get jbOfferInvalidated => 'No longer valid';

  @override
  String get jbOfferWithdrawn => 'Withdrawn';

  @override
  String get jbOfferUnknown => 'Unknown';

  @override
  String get jbReasonRejected => 'The customer declined this offer.';

  @override
  String get jbReasonLost => 'The customer chose another driver.';

  @override
  String get jbReasonExpired => 'The offer expired without an answer.';

  @override
  String get jbReasonRequestChanged =>
      'The customer changed the request, so the offer was cancelled.';

  @override
  String get jbReasonUnavailable =>
      'The offer was cancelled because you became unavailable.';

  @override
  String get jbReasonWithdrawn => 'You withdrew this offer.';

  @override
  String get jbKindFare => 'Accepted the customer fare';

  @override
  String get jbKindCounter => 'Your counter-offer';

  @override
  String get jbOpenJob => 'Open job';

  @override
  String get jbNoHistoryTitle => 'No jobs yet';

  @override
  String get jbNoHistoryBody => 'Your finished jobs appear here.';

  @override
  String get jbHistoryJob => 'Job';

  @override
  String get jbJobTitle => 'Job';

  @override
  String get jbActiveJob => 'You have an active job';

  @override
  String get jbJobLostTitle => 'You no longer hold this job';

  @override
  String get jbJobLostBody =>
      'The request was re-dispatched, cancelled or given to another driver.';

  @override
  String get jbBackHome => 'Back to Home';

  @override
  String get jbStatusSelected => 'You were selected';

  @override
  String get jbStatusHeading => 'Heading to pickup';

  @override
  String get jbStatusArrived => 'Arrived at pickup';

  @override
  String get jbStatusInProgress => 'Trip in progress';

  @override
  String get jbStatusCompleted => 'Completed';

  @override
  String get jbStatusCancelledCustomer => 'Cancelled by customer';

  @override
  String get jbStatusCancelledMe => 'Cancelled by you';

  @override
  String get jbStatusCancelledOps => 'Cancelled by operations';

  @override
  String get jbStatusDisputed => 'Disputed';

  @override
  String get jbStatusFailed => 'Delivery attempt failed';

  @override
  String get jbStatusSearching => 'Searching for a driver';

  @override
  String get jbStatusOther => 'Other status';

  @override
  String get jbCollectCash => 'Collect this amount in cash';

  @override
  String get jbPayCash => 'Cash payment';

  @override
  String get jbPayOther => 'Online payment';

  @override
  String get jbSupportFlagged => 'Support is following this trip.';

  @override
  String jbFreeWaitLeft(String time) {
    return 'Free waiting time left: $time';
  }

  @override
  String get jbFreeWaitOver => 'Free waiting time is over.';

  @override
  String get jbPinNeeded => 'Ask the customer for the trip PIN and enter it.';

  @override
  String get jbPinVerified => 'PIN verified.';

  @override
  String get jbRouteTitle => 'Route';

  @override
  String get jbPickup => 'Pickup';

  @override
  String get jbDropoff => 'Destination';

  @override
  String get jbStopLabel => 'Stop';

  @override
  String get jbCustomerTitle => 'Customer';

  @override
  String get jbCustomerUnknown => 'Customer';

  @override
  String jbCustomerRating(String avg, int count) {
    return 'Rating $avg ($count)';
  }

  @override
  String get jbCallCustomer => 'Call customer';

  @override
  String get jbCallFailed => 'Could not start the call.';

  @override
  String get jbContactMasked =>
      'The customer number is hidden. Contact support if needed.';

  @override
  String get jbInstructionsTitle => 'Customer details and instructions';

  @override
  String get jbActionAcknowledge => 'I am heading to the customer';

  @override
  String get jbActionArrived => 'I have arrived';

  @override
  String get jbActionPin => 'Enter trip PIN';

  @override
  String get jbPinLockedButton => 'PIN locked';

  @override
  String get jbActionStart => 'Start trip';

  @override
  String get jbComplete => 'Complete trip';

  @override
  String get jbCompleteConfirmTitle => 'Complete this trip?';

  @override
  String get jbCompleteConfirmBody =>
      'Make sure you have reached the destination.';

  @override
  String jbCompleteConfirmCash(String amount) {
    return 'Collect $amount in cash from the customer, then complete the trip.';
  }

  @override
  String get jbNavigatePickup => 'Navigate to pickup';

  @override
  String get jbNavigateDropoff => 'Navigate to destination';

  @override
  String get jbNavFailed => 'Could not open a maps app.';

  @override
  String get jbCancelJob => 'Cancel job';

  @override
  String get jbReportIssue => 'Report an issue';

  @override
  String get jbPinTitle => 'Trip PIN';

  @override
  String get jbPinHint => 'Ask the customer for the code and enter it here.';

  @override
  String get jbPinConfirm => 'Confirm PIN';

  @override
  String jbPinAttemptsLeft(int count) {
    return 'Attempts left: $count';
  }

  @override
  String get jbSummaryDone => 'Trip completed';

  @override
  String get jbSummaryEnded => 'Trip ended';

  @override
  String get jbSummaryAmount => 'Trip amount';

  @override
  String jbSummaryCollectCash(String amount) {
    return 'Collect $amount in cash from the customer.';
  }

  @override
  String get jbRateTitle => 'Rate the customer';

  @override
  String jbStarsLabel(int count) {
    return '$count stars';
  }

  @override
  String get jbRateComment => 'Comment (optional)';

  @override
  String get jbRateSubmit => 'Send rating';

  @override
  String get jbRatingThanks => 'Thanks, your rating was sent.';

  @override
  String get jbTagRespectful => 'Respectful';

  @override
  String get jbTagClear => 'Clear instructions';

  @override
  String get jbTagReady => 'Ready at pickup';

  @override
  String get jbTagDisrespectful => 'Disrespectful';

  @override
  String get jbTagNotReady => 'Not ready at pickup';

  @override
  String get jbTagLate => 'Late';

  @override
  String get jbCancelTitle => 'Cancel job';

  @override
  String get jbCancelPrompt => 'Why do you want to cancel?';

  @override
  String get jbCancelNoShow => 'Customer did not show up';

  @override
  String get jbCancelUnreachable => 'Customer unreachable';

  @override
  String get jbCancelVehicle => 'Vehicle problem';

  @override
  String get jbCancelEmergency => 'Emergency';

  @override
  String get jbCancelWrongDetails => 'Wrong request details';

  @override
  String get jbCancelUnsafe => 'Unsafe pickup';

  @override
  String get jbCancelOther => 'Other reason';

  @override
  String get jbCancelNote => 'Write the reason';

  @override
  String get jbCancelShowCost => 'Show cancellation cost';

  @override
  String get jbCancelFree => 'Cancelling now is free.';

  @override
  String jbCancelFee(String fee) {
    return 'Cancellation fee: $fee';
  }

  @override
  String get jbCancelRedispatchNote =>
      'The request will be sent to other drivers.';

  @override
  String get jbCancelConfirmAction => 'Confirm cancellation';

  @override
  String get jbCancelConfirmTitle => 'Cancel this job?';

  @override
  String get jbCancelConfirmBody => 'Cancelling cannot be undone.';

  @override
  String get jbCancelConfirmRedispatch =>
      'The request goes back to other drivers and you will not get it back.';

  @override
  String get jbCancelledDone => 'The job was cancelled.';

  @override
  String get jbCancelledRedispatch =>
      'Cancelled. The request was sent to other drivers.';

  @override
  String get jbReportTitle => 'Report an issue';

  @override
  String get jbReportPrompt => 'What kind of issue?';

  @override
  String get jbReportSafety => 'Safety';

  @override
  String get jbReportBehaviour => 'Customer behaviour';

  @override
  String get jbReportFare => 'Fare dispute';

  @override
  String get jbReportRoute => 'Route or delay';

  @override
  String get jbReportOther => 'Other';

  @override
  String get jbReportDescription => 'Describe the issue';

  @override
  String get jbReportDescriptionHelp => 'At least 10 characters';

  @override
  String get jbReportSubmit => 'Send report';

  @override
  String get jbReportSentTitle => 'Report sent';

  @override
  String get jbReportSentBody => 'The support team will review it.';

  @override
  String get jbErrLost =>
      'This request is no longer available (it expired or went to another driver). The list was refreshed.';

  @override
  String get jbErrNotEligible =>
      'You cannot send offers right now. Check the reasons on Home.';

  @override
  String get jbErrVehicle => 'Choose an approved vehicle on Home first.';

  @override
  String get jbErrCounterOff => 'This request does not accept counter-offers.';

  @override
  String get jbErrAmountInvalid => 'The amount is not valid.';

  @override
  String get jbErrAmountBounds => 'The amount is outside the allowed range.';

  @override
  String get jbErrCurrency => 'The currency does not match.';

  @override
  String get jbErrSameAsFare =>
      'That is the customer fare. Use the accept button instead.';

  @override
  String get jbErrOfferLimit => 'You reached the limit of open offers.';

  @override
  String get jbErrRevisions => 'You used all revisions of this offer.';

  @override
  String get jbErrNotWithdrawable => 'This offer can no longer be withdrawn.';

  @override
  String get jbErrWithdrawFirst => 'Withdraw your offer before skipping.';

  @override
  String get jbErrNotYours => 'This offer is not yours.';

  @override
  String get jbErrTransition => 'The job state changed. It was refreshed.';

  @override
  String get jbErrPinRequired => 'Enter the trip PIN first.';

  @override
  String get jbErrProofRequired => 'A proof is required before continuing.';

  @override
  String get jbErrPinLocked =>
      'The PIN is locked after wrong attempts. Contact support to start the trip.';

  @override
  String get jbErrPinInvalid => 'The PIN is wrong.';

  @override
  String get jbErrPinNotRequired => 'This trip does not need a PIN.';

  @override
  String get jbErrNotCancellable => 'This job cannot be cancelled now.';

  @override
  String get jbErrReasonNotAllowed =>
      'This cancellation reason is not allowed.';

  @override
  String get jbErrNoteRequired => 'Write the reason for cancelling.';

  @override
  String get jbErrNoShowEarly =>
      'No-show can be chosen only after the free waiting time.';

  @override
  String get jbErrReportLimit => 'You cannot send another report now.';

  @override
  String get jbErrRatingClosed => 'This job cannot be rated now.';

  @override
  String get jbErrRatingDone => 'You already rated this job.';

  @override
  String get jbErrRateLimited =>
      'Too many attempts. Wait a moment and try again.';

  @override
  String get jbErrAccount => 'Your account cannot do this right now.';

  @override
  String get jbErrInProgress =>
      'The request is still being processed. Wait a moment.';

  @override
  String get jbErrNotFound => 'We could not find this job.';

  @override
  String jbErrPinInvalidLeft(int count) {
    return 'The PIN is wrong. Attempts left: $count';
  }

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

  @override
  String get chatTitle => 'Chat';

  @override
  String get chatInputHint => 'Write a message';

  @override
  String get chatSend => 'Send';

  @override
  String get chatAttach => 'Attach a photo';

  @override
  String get attachCamera => 'Take a photo';

  @override
  String get attachGallery => 'Choose from gallery';

  @override
  String get attachRemove => 'Remove';

  @override
  String get chatEmptyTitle => 'No messages yet';

  @override
  String get chatEmptyBody =>
      'Say hello. Messages are only visible to you and the other side of this booking.';

  @override
  String get chatClosedTitle => 'This conversation is closed';

  @override
  String get chatClosedBody =>
      'You can still read the messages. For anything else, contact support.';

  @override
  String chatClosesAt(String time) {
    return 'You can chat until $time';
  }

  @override
  String get chatSending => 'Sending…';

  @override
  String get chatFailed => 'Not sent';

  @override
  String get chatDiscard => 'Discard';

  @override
  String get chatRead => 'Read';

  @override
  String get chatDelivered => 'Sent';

  @override
  String get chatLoadOlder => 'Earlier messages';

  @override
  String get chatImageFailed => 'Image could not be loaded. Tap to retry.';

  @override
  String get chatImageLabel => 'Photo in the conversation';

  @override
  String get chatRemoved => 'Removed after the retention period';

  @override
  String chatErrTooLongMax(int max) {
    return 'Message is too long (maximum $max characters)';
  }

  @override
  String get chatPhoneNotice =>
      'Phone numbers in messages are hidden. Use the call button instead.';

  @override
  String get chatUnavailableTitle => 'Chat is not available';

  @override
  String get chatUnavailableBody =>
      'You can chat once a driver is confirmed for this booking.';

  @override
  String get chatErrClosed => 'The conversation for this booking is closed.';

  @override
  String get chatErrForbidden => 'You are not part of this conversation.';

  @override
  String get chatErrTooLong => 'The message is too long.';

  @override
  String get supportTitle => 'Help and support';

  @override
  String get supportTicketTitle => 'Request';

  @override
  String get supportEmptyTitle => 'No requests yet';

  @override
  String get supportEmptyBody =>
      'Open a request if you need help with a trip, a payment or your account.';

  @override
  String get supportNew => 'Contact support';

  @override
  String get supportFilterActive => 'Open';

  @override
  String get supportFilterFinished => 'Resolved';

  @override
  String get supportFilterAll => 'All';

  @override
  String get supportLoadMore => 'Show more';

  @override
  String get supportCategory => 'What is it about?';

  @override
  String get supportCategoryHint => 'Choose a category';

  @override
  String get supportSubject => 'Subject';

  @override
  String get supportSubjectHint => 'A short title';

  @override
  String get supportBody => 'Describe the problem';

  @override
  String get supportBodyHint =>
      'What happened? Add details that help us check it.';

  @override
  String supportBooking(String code) {
    return 'Booking $code';
  }

  @override
  String get supportSubmit => 'Send request';

  @override
  String get supportRequired => 'This field is required';

  @override
  String get supportTooShort => 'Please write a little more';

  @override
  String get supportSent => 'Your request was sent.';

  @override
  String get supportReplyHint => 'Write a reply';

  @override
  String get supportReplySend => 'Send reply';

  @override
  String get supportReplyClosed =>
      'This request is closed. Open a new request if you still need help.';

  @override
  String get supportResolution => 'Resolution';

  @override
  String get supportFromSupport => 'Support';

  @override
  String get supportFromMe => 'You';

  @override
  String get supportNoInternal => 'Only replies meant for you are shown.';

  @override
  String get supportAttachment => 'Attachment';

  @override
  String supportTicketNumber(String number) {
    return 'Request $number';
  }

  @override
  String supportOpenedOn(String date) {
    return 'Opened $date';
  }

  @override
  String get supportErrLimit =>
      'You have too many open requests. Wait for an answer or reply to an existing one.';

  @override
  String get supportErrRate =>
      'You sent too many requests today. Please try again tomorrow.';

  @override
  String get supportErrClosed => 'This request is closed.';

  @override
  String get supportErrAppealOpen => 'You already appealed this booking.';

  @override
  String get supportErrAppealNone =>
      'There is no fee on this booking that can be appealed.';

  @override
  String get supportErrInfected =>
      'The file was rejected by the security scan.';

  @override
  String get supportErrFileType =>
      'This file type is not allowed. Use a photo or a PDF.';

  @override
  String get supportErrFileSize => 'The file is too large.';

  @override
  String get supportAppealTitle => 'Appeal the fee';

  @override
  String get supportAppealIntro =>
      'Tell us why the fee should be reviewed. Support will check the trip and the fee decision, and answer in this request.';

  @override
  String get supportAppealReason => 'Why do you disagree?';

  @override
  String get supportAppealShort => 'Please explain in at least 10 characters.';

  @override
  String get supportAppealSubmit => 'Send appeal';

  @override
  String get supportAppealSent => 'Your appeal was sent to support.';

  @override
  String get supportOpenChat => 'Chat';

  @override
  String get supportOpenChatHistory => 'Chat history';

  @override
  String get supportHelp => 'Help and support';

  @override
  String get supportHelpAboutTrip => 'Get help with this trip';

  @override
  String get supportAppealButton => 'Appeal the fee';

  @override
  String get ernTab => 'Earnings';

  @override
  String get ernDay => 'Day';

  @override
  String get ernWeek => 'Week';

  @override
  String get ernMonth => 'Month';

  @override
  String get ernEarned => 'You earned';

  @override
  String ernJobs(int count) {
    return '$count jobs';
  }

  @override
  String get ernGross => 'Fares';

  @override
  String get ernCommission => 'Platform commission';

  @override
  String get ernProviderDiscount => 'Discount you funded';

  @override
  String get ernPlatformDiscount => 'Discount funded by the platform';

  @override
  String get ernTips => 'Tips';

  @override
  String get ernNet => 'Net';

  @override
  String get ernCash => 'Cash collected';

  @override
  String get ernDaily => 'Earnings per day';

  @override
  String get ernByCategory => 'By service';

  @override
  String get ernNoJobs => 'No completed jobs in this period.';

  @override
  String get ernWalletBalance => 'Wallet balance';

  @override
  String get ernWalletReserved => 'Reserved for payouts';

  @override
  String get ernWalletAvailable => 'Available to withdraw';

  @override
  String get ernBreakdownLink => 'Earnings per job';

  @override
  String get ernOpenJob => 'Open job';

  @override
  String get walLedgerTitle => 'Wallet ledger';

  @override
  String get walLedgerEmpty => 'No wallet entries yet.';

  @override
  String get walLoadMore => 'Load more';

  @override
  String walBalanceAfter(String amount) {
    return 'Balance: $amount';
  }

  @override
  String cashDebtTitle(String amount) {
    return 'You owe $amount in cash commission';
  }

  @override
  String cashDebtWarning(String limit) {
    return 'Your limit is $limit. Above it you stop receiving requests until you settle.';
  }

  @override
  String cashDebtBlocked(String limit) {
    return 'You are over the $limit limit, so you do not receive new requests until you settle.';
  }

  @override
  String get cashDebtHowTo =>
      'Settle the cash commission with our finance team.';

  @override
  String get cashDebtContact => 'Contact support';

  @override
  String get cashCollectTitle => 'Confirm the cash';

  @override
  String cashCollectBody(String amount) {
    return 'Confirm you collected $amount in cash from the customer.';
  }

  @override
  String get cashCollectBodyNoAmount =>
      'Confirm you collected the cash from the customer.';

  @override
  String get cashConfirm => 'I collected the cash';

  @override
  String get cashConfirmedTitle => 'Cash confirmed';

  @override
  String get cashConfirmedBody => 'Thank you. The job is settled.';

  @override
  String get cashErrNotCollectable =>
      'The cash of this job cannot be confirmed now.';

  @override
  String get poTitle => 'Payouts';

  @override
  String get poAvailable => 'Available to withdraw';

  @override
  String poReservedNote(String amount) {
    return '$amount is reserved for open payouts';
  }

  @override
  String poLimits(String min, String max) {
    return 'Minimum $min, maximum $max per payout';
  }

  @override
  String get poRequest => 'Request a payout';

  @override
  String get poHistory => 'History';

  @override
  String get poEmptyTitle => 'No payouts yet';

  @override
  String get poEmptyBody =>
      'Your payout requests and their status appear here.';

  @override
  String poAmountLabel(String currency) {
    return 'Amount ($currency)';
  }

  @override
  String get poAll => 'All';

  @override
  String get poMethod => 'Payout method';

  @override
  String get poNoMethods => 'Add a payout method first.';

  @override
  String get poNoVerifiedMethods =>
      'Your payout methods are waiting for verification by our finance team.';

  @override
  String get poFeeNote =>
      'The amount is set aside in your wallet now. The fee and approval are decided by our finance team.';

  @override
  String get poSubmit => 'Send request';

  @override
  String get poNoBalance => 'Your balance is not available. Try again.';

  @override
  String get poErrAmountInvalid => 'Enter a valid amount.';

  @override
  String poErrBelowMinAmount(String min) {
    return 'The minimum payout is $min.';
  }

  @override
  String poErrAboveMaxAmount(String max) {
    return 'The maximum payout is $max.';
  }

  @override
  String get poErrChooseMethod => 'Choose a payout method.';

  @override
  String get poErrBelowMin => 'The amount is below the minimum payout.';

  @override
  String get poErrAboveMax => 'The amount is above the maximum payout.';

  @override
  String get poErrInsufficient =>
      'The amount is more than your available balance.';

  @override
  String get poErrNotVerified => 'This payout method is not verified yet.';

  @override
  String get poErrNotApproved => 'Your account cannot request payouts now.';

  @override
  String get poErrMethodInUse => 'This method is used by an open payout.';

  @override
  String get poErrNotCancellable => 'This payout can no longer be cancelled.';

  @override
  String get poErrKeyReused =>
      'Something changed in this request. Start it again.';

  @override
  String get poDetailTitle => 'Payout';

  @override
  String get poAmount => 'Amount';

  @override
  String get poFee => 'Fee';

  @override
  String get poNet => 'You receive';

  @override
  String get poRequestedAt => 'Requested';

  @override
  String get poPaidAt => 'Paid';

  @override
  String get poReference => 'Transfer reference';

  @override
  String get poReason => 'Reason';

  @override
  String get poMoneyBack => 'The amount went back to your wallet.';

  @override
  String get poCancel => 'Cancel request';

  @override
  String get poCancelTitle => 'Cancel this payout?';

  @override
  String get poCancelBody => 'The amount returns to your wallet.';

  @override
  String get poCancelConfirm => 'Cancel payout';

  @override
  String get poCancelled => 'Payout cancelled.';

  @override
  String get poAskSupport => 'Ask support about this payout';

  @override
  String get poStatusRequested => 'Requested';

  @override
  String get poStatusReview => 'Under review';

  @override
  String get poStatusApproved => 'Approved';

  @override
  String get poStatusProcessing => 'Processing';

  @override
  String get poStatusPaid => 'Paid';

  @override
  String get poStatusFailed => 'Failed';

  @override
  String get poStatusRejected => 'Rejected';

  @override
  String get poStatusCancelled => 'Cancelled';

  @override
  String get poStatusReversed => 'Reversed';

  @override
  String get poStatusUnknown => 'Other';

  @override
  String get poExplainReview => 'Our finance team is reviewing your request.';

  @override
  String get poExplainApproved => 'Approved. The transfer will start soon.';

  @override
  String get poExplainProcessing => 'The transfer is on its way.';

  @override
  String get poExplainPaid => 'The money was sent to your payout method.';

  @override
  String get poExplainRejected => 'The request was rejected.';

  @override
  String get poExplainFailed => 'The transfer failed.';

  @override
  String get poExplainCancelled => 'You cancelled this request.';

  @override
  String get poExplainReversed => 'The bank returned the transfer.';

  @override
  String get pomTitle => 'Payout methods';

  @override
  String get pomManage => 'Manage payout methods';

  @override
  String get pomAdd => 'Add a method';

  @override
  String get pomEdit => 'Edit';

  @override
  String get pomEditNote =>
      'For your security the saved details are not shown. Enter them again to change this method. It will need verification again.';

  @override
  String get pomEmptyTitle => 'No payout methods';

  @override
  String get pomEmptyBody =>
      'Add a bank account or mobile wallet to receive payouts.';

  @override
  String get pomVerified => 'Verified';

  @override
  String get pomUnverified => 'Not verified';

  @override
  String get pomUnverifiedNote =>
      'Finance verifies a method before you can use it.';

  @override
  String get pomDefault => 'Default method';

  @override
  String get pomMakeDefault => 'Make default';

  @override
  String get pomDefaultSet => 'Default method updated.';

  @override
  String get pomDelete => 'Delete';

  @override
  String get pomDeleteTitle => 'Delete this method?';

  @override
  String get pomDeleteBody => 'You can add it again later.';

  @override
  String get pomDeleted => 'Method deleted.';

  @override
  String get pomBank => 'Bank account';

  @override
  String get pomWallet => 'Mobile wallet';

  @override
  String get pomLabel => 'Name for this method';

  @override
  String get pomHolder => 'Account holder name';

  @override
  String get pomBankName => 'Bank name';

  @override
  String get pomAccount => 'Account number or IBAN';

  @override
  String get pomAccountInvalid => 'Enter a valid account number.';

  @override
  String get pomWalletNumber => 'Wallet phone number';

  @override
  String get pomWalletInvalid => 'Enter a valid phone number.';

  @override
  String get pomVerifyNote =>
      'Details are stored encrypted and checked by our finance team.';

  @override
  String get pomSave => 'Save';

  @override
  String get pomSaved => 'Payout method saved.';

  @override
  String get fieldRequired => 'This field is required.';

  @override
  String get rcpTitle => 'Receipt';

  @override
  String get rcpViewFull => 'Full receipt';

  @override
  String rcpNumber(String number) {
    return 'Receipt $number';
  }

  @override
  String get rcpCustomer => 'Customer';

  @override
  String get jbReceiptCode => 'Booking code';

  @override
  String get rcpAgreed => 'Agreed fare';

  @override
  String rcpAdjustment(String label) {
    return 'Added: $label';
  }

  @override
  String get rcpSubtotal => 'Fare total';

  @override
  String get rcpTax => 'Tax';

  @override
  String get rcpOpenPrintable => 'Open printable receipt';

  @override
  String get rcpErrNotAvailable =>
      'The receipt is available after the trip is completed.';

  @override
  String get adjApprove => 'Approve';

  @override
  String get adjReject => 'Reject';

  @override
  String get adjWithdraw => 'Withdraw';

  @override
  String adjExpires(String time) {
    return 'Answer before $time';
  }

  @override
  String adjNewTotal(String amount) {
    return 'New total: $amount';
  }

  @override
  String get adjNote => 'Note';

  @override
  String get dadjTitle => 'Fare changes';

  @override
  String get dadjRequest => 'Request a fare change';

  @override
  String get dadjRequestedByYou => 'Requested by you';

  @override
  String get dadjRequestedByCustomer => 'Requested by the customer';

  @override
  String get dadjTypeExtraStop => 'Extra stop';

  @override
  String get dadjTypeWaiting => 'Waiting time';

  @override
  String get dadjTypeToll => 'Toll or parking';

  @override
  String get dadjTypeScope => 'Change of scope';

  @override
  String get dadjAmount => 'Amount';

  @override
  String get dadjWaitingHelp => 'Leave empty to use the waiting rate.';

  @override
  String get dadjReason => 'Reason';

  @override
  String get dadjCustomerApproves =>
      'The fare changes only if the customer approves.';

  @override
  String get dadjSend => 'Send request';

  @override
  String get dadjSent => 'Request sent to the customer.';

  @override
  String get dadjErrNotNow => 'A fare change cannot be requested now.';

  @override
  String get dadjErrOpenLimit =>
      'You already have the maximum number of open requests.';

  @override
  String get dadjErrAmount => 'Enter a valid amount.';

  @override
  String get dadjErrReason => 'Describe the reason.';

  @override
  String get dadjErrEvidence =>
      'This type needs evidence. Add details in the reason.';

  @override
  String get dadjErrType =>
      'This type of change is not allowed for this service.';

  @override
  String get dadjErrNotPending =>
      'This request was already answered or has expired.';

  @override
  String get dadjErrCounterpart =>
      'Only the other side can answer this request.';
}
