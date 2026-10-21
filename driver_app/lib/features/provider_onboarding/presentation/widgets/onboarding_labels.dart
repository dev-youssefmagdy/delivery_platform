import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

extension ProviderTypeLabels on ProviderType {
  String label(AppLocalizations l10n) => switch (this) {
    ProviderType.individualDriver => l10n.obTypeIndividualDriver,
    ProviderType.courier => l10n.obTypeCourier,
    ProviderType.movingCrew => l10n.obTypeMovingCrew,
    ProviderType.commercialTransport => l10n.obTypeCommercialTransport,
    ProviderType.companyFleet => l10n.obTypeCompanyFleet,
    ProviderType.unknown => value,
  };
}

extension VehicleCapabilityLabels on VehicleCapability {
  String label(AppLocalizations l10n) => switch (this) {
    VehicleCapability.airConditioning => l10n.obCapAirConditioning,
    VehicleCapability.childSeat => l10n.obCapChildSeat,
    VehicleCapability.wheelchairAccessible => l10n.obCapWheelchair,
    VehicleCapability.petsAllowed => l10n.obCapPets,
    VehicleCapability.largeLuggage => l10n.obCapLargeLuggage,
    VehicleCapability.coveredCargo => l10n.obCapCoveredCargo,
    VehicleCapability.tailLift => l10n.obCapTailLift,
    VehicleCapability.refrigerated => l10n.obCapRefrigerated,
    VehicleCapability.movingEquipment => l10n.obCapMovingEquipment,
    VehicleCapability.unknown => value,
  };
}

extension VehiclePhotoKindLabels on VehiclePhotoKind {
  String label(AppLocalizations l10n) => switch (this) {
    VehiclePhotoKind.front => l10n.obPhotoKindFront,
    VehiclePhotoKind.back => l10n.obPhotoKindBack,
    VehiclePhotoKind.side => l10n.obPhotoKindSide,
    VehiclePhotoKind.interior => l10n.obPhotoKindInterior,
    VehiclePhotoKind.other => l10n.obPhotoKindOther,
    VehiclePhotoKind.unknown => value,
  };
}

extension VehicleStatusLabels on VehicleStatus {
  String label(AppLocalizations l10n) => switch (this) {
    VehicleStatus.draft => l10n.obVehicleStatusDraft,
    VehicleStatus.underReview => l10n.obVehicleStatusUnderReview,
    VehicleStatus.approved => l10n.obVehicleStatusApproved,
    VehicleStatus.rejected => l10n.obVehicleStatusRejected,
    VehicleStatus.suspended => l10n.obVehicleStatusSuspended,
    VehicleStatus.inactive => l10n.obVehicleStatusInactive,
    VehicleStatus.unknown => value,
  };

  ChipTone get tone => switch (this) {
    VehicleStatus.approved => ChipTone.success,
    VehicleStatus.underReview => ChipTone.info,
    VehicleStatus.rejected => ChipTone.danger,
    VehicleStatus.suspended => ChipTone.warning,
    _ => ChipTone.neutral,
  };
}

extension ServiceApprovalLabels on ServiceApprovalStatus {
  String label(AppLocalizations l10n) => switch (this) {
    ServiceApprovalStatus.approved => l10n.obServiceStatusApproved,
    ServiceApprovalStatus.rejected => l10n.obServiceStatusRejected,
    ServiceApprovalStatus.requested => l10n.obServiceStatusRequested,
    ServiceApprovalStatus.unknown => value,
  };

  ChipTone get tone => switch (this) {
    ServiceApprovalStatus.approved => ChipTone.success,
    ServiceApprovalStatus.rejected => ChipTone.danger,
    _ => ChipTone.info,
  };
}

/// `null` means nothing was uploaded yet.
String documentStateLabel(AppLocalizations l10n, DocumentStatus? state) =>
    switch (state) {
      null => l10n.obDocStateMissing,
      DocumentStatus.pending => l10n.obDocStatePending,
      DocumentStatus.approved => l10n.obDocStateApproved,
      DocumentStatus.rejected => l10n.obDocStateRejected,
      DocumentStatus.expired => l10n.obDocStateExpired,
      DocumentStatus.unknown => state.value,
    };

ChipTone documentStateTone(DocumentStatus? state) => switch (state) {
  DocumentStatus.approved => ChipTone.success,
  DocumentStatus.pending => ChipTone.info,
  DocumentStatus.rejected => ChipTone.danger,
  DocumentStatus.expired => ChipTone.warning,
  _ => ChipTone.neutral,
};

String eligibilityReasonLabel(AppLocalizations l10n, EligibilityReason r) =>
    switch (r) {
      EligibilityReason.notApproved => l10n.erNotApproved,
      EligibilityReason.providerSuspended => l10n.erProviderSuspended,
      EligibilityReason.accountBlocked => l10n.erAccountBlocked,
      EligibilityReason.documentMissing => l10n.erDocumentMissing,
      EligibilityReason.documentNotApproved => l10n.erDocumentNotApproved,
      EligibilityReason.documentExpired => l10n.erDocumentExpired,
      EligibilityReason.noApprovedVehicle => l10n.erNoApprovedVehicle,
      EligibilityReason.vehicleNotApproved => l10n.erVehicleNotApproved,
      EligibilityReason.serviceNotApproved => l10n.erServiceNotApproved,
      EligibilityReason.vehicleCategoryMismatch =>
        l10n.erVehicleCategoryMismatch,
      EligibilityReason.locationStale => l10n.erLocationStale,
      EligibilityReason.activeJobExists => l10n.erActiveJobExists,
      EligibilityReason.cashDebtLimitExceeded => l10n.erCashDebtLimitExceeded,
      EligibilityReason.unknown => l10n.erUnknown,
    };

/// Text of a pending task. A document task names the document by the type
/// code found in the application's checklist.
String taskLabel(
  AppLocalizations l10n,
  OnboardingTaskInfo item,
  ProviderApplication application,
  String languageCode,
) {
  String documentName() {
    for (final d in application.requiredDocuments) {
      if (d.type.code == item.reference) {
        return d.type.name.resolve(languageCode);
      }
    }
    return item.reference ?? '';
  }

  return switch (item.task) {
    OnboardingTask.completeProfile => l10n.obTaskCompleteProfile,
    OnboardingTask.selectServices => l10n.obTaskSelectServices,
    OnboardingTask.addVehicle => l10n.obTaskAddVehicle,
    OnboardingTask.addVehiclePhotos => l10n.obTaskAddVehiclePhotos,
    OnboardingTask.submitVehicle => l10n.obTaskSubmitVehicle,
    OnboardingTask.uploadDocument => l10n.obTaskUploadDocument(documentName()),
    OnboardingTask.replaceRejectedDocument => l10n.obTaskReplaceDocument(
      documentName(),
    ),
    OnboardingTask.renewExpiredDocument => l10n.obTaskRenewDocument(
      documentName(),
    ),
    OnboardingTask.fixVehicle => l10n.obTaskFixVehicle,
    OnboardingTask.acceptPolicies => l10n.obTaskAcceptPolicies,
    OnboardingTask.submitApplication => l10n.obTaskSubmitApplication,
    OnboardingTask.unknown => l10n.erUnknown,
  };
}

/// Maps a failure to ARB text by the server's stable code; server strings are
/// never shown. Unknown codes fall back to a generic text.
String onboardingErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.obErrGeneric;
  if (error.isOffline) return l10n.errorOfflineBody;
  switch (error.code) {
    case 'provider_underage':
      return l10n.obErrUnderage(18);
    case 'provider_city_unavailable':
      return l10n.obErrCityUnavailable;
    case 'provider_service_unavailable':
      return l10n.obErrServiceUnavailable;
    case 'application_locked':
      return l10n.obErrApplicationLocked;
    case 'application_exists':
      return l10n.obErrApplicationExists;
    case 'application_incomplete':
      return l10n.obErrIncomplete;
    case 'document_type_not_allowed':
      return l10n.obErrDocTypeNotAllowed;
    case 'document_too_large':
      return l10n.obErrDocTooLarge;
    case 'document_unreadable':
      return l10n.obErrDocUnreadable;
    case 'document_image_too_small':
    case 'document_image_too_large':
      return l10n.obErrDocTooSmall;
    case 'document_infected':
      return l10n.obErrDocInfected;
    case 'file_scanner_unavailable':
      return l10n.obErrScannerDown;
    case 'document_number_required':
      return l10n.obErrNumberRequired;
    case 'document_expiry_required':
      return l10n.obErrExpiryRequired;
    case 'document_already_expired':
      return l10n.obErrAlreadyExpired;
    case 'document_dates_inconsistent':
      return l10n.obErrDatesInconsistent;
    case 'document_issue_date_future':
      return l10n.obErrIssueFuture;
    case 'vehicle_plate_taken':
      return l10n.obErrPlateTaken;
    case 'vehicle_plate_invalid':
      return l10n.obErrPlateInvalid;
    case 'vehicle_year_invalid':
      return l10n.obErrYearInvalid;
    case 'vehicle_not_editable':
      return l10n.obErrVehicleNotEditable;
    case 'vehicle_incomplete':
      return l10n.obErrVehicleIncomplete;
    case 'vehicle_photo_limit_reached':
      return l10n.obErrPhotoLimit;
    case 'vehicle_limit_reached':
      return l10n.obErrVehicleLimit;
    case 'policy_version_not_current':
      return l10n.obErrPolicyStale;
    case 'validation_failed':
      return l10n.obErrValidation;
    case 'too_many_requests':
      return l10n.otpErrorTooManyRequests(error.retryAfterSeconds ?? 60);
    case 'account_suspended':
      return l10n.statusSuspendedBody;
  }
  return l10n.obErrGeneric;
}

String formatDay(BuildContext context, DateTime date) =>
    DateFormat.yMMMd(Localizations.localeOf(context).toString()).format(date);

/// Date in the `Y-m-d` form of the API, handy for tests and keys.
String isoDay(DateTime date) =>
    '${date.year.toString().padLeft(4, '0')}-${date.month.toString().padLeft(2, '0')}-${date.day.toString().padLeft(2, '0')}';
