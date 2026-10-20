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
  String get vsEligible =>
      'You meet every requirement. Going online to receive requests arrives in the next update.';

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
}
