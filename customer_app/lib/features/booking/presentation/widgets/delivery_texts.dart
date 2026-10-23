import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

String custodyText(AppLocalizations l10n, CustodyStatus s) => switch (s) {
  CustodyStatus.pickedUp => l10n.dcCustodyPickedUp,
  CustodyStatus.inTransit => l10n.dcCustodyInTransit,
  CustodyStatus.arrivedDropoff => l10n.dcCustodyArrived,
  CustodyStatus.attemptFailed => l10n.dcCustodyFailed,
  CustodyStatus.returning => l10n.dcCustodyReturning,
  CustodyStatus.delivered => l10n.dcCustodyDelivered,
  CustodyStatus.returned => l10n.dcCustodyReturned,
  CustodyStatus.unknown => l10n.dcCustodyUnknown,
};

String attemptOutcomeText(AppLocalizations l10n, AttemptOutcome o) =>
    switch (o) {
      AttemptOutcome.recipientUnavailable => l10n.dcOutUnavailable,
      AttemptOutcome.recipientRefused => l10n.dcOutRefused,
      AttemptOutcome.wrongAddress => l10n.dcOutWrongAddress,
      AttemptOutcome.otpFailed => l10n.dcOutOtpFailed,
      AttemptOutcome.restrictedItem => l10n.dcOutRestricted,
      AttemptOutcome.other || AttemptOutcome.unknown => l10n.dcOutOther,
    };

String resolutionText(AppLocalizations l10n, AttemptResolution r) =>
    switch (r) {
      AttemptResolution.retry => l10n.dcResRetry,
      AttemptResolution.returnToSender => l10n.dcResReturn,
      AttemptResolution.operations => l10n.dcResOperations,
      AttemptResolution.unknown => l10n.dcResUnknown,
    };

String proofTypeText(AppLocalizations l10n, ProofType t) => switch (t) {
  ProofType.photo => l10n.dcProofPhoto,
  ProofType.signature => l10n.dcProofSignature,
  ProofType.recipientOtp => l10n.dcProofOtp,
  ProofType.pickupPin => l10n.dcProofPin,
  ProofType.checklist => l10n.dcProofChecklist,
  ProofType.weight => l10n.dcProofWeight,
  ProofType.unknown => l10n.dcProofOther,
};

String proofStageText(AppLocalizations l10n, String stage) => switch (stage) {
  'pickup' => l10n.dcStagePickup,
  'delivery' => l10n.dcStageDelivery,
  'return' => l10n.dcStageReturn,
  _ => stage,
};

String checklistCodeText(AppLocalizations l10n, String code) => switch (code) {
  'package_sealed' => l10n.dcChkSealed,
  'package_undamaged' => l10n.dcChkUndamaged,
  'label_matches' => l10n.dcChkLabel,
  'recipient_checked' => l10n.dcChkRecipient,
  _ => code,
};

String contactRoleText(AppLocalizations l10n, String role) => switch (role) {
  'sender' => l10n.dcRoleSender,
  'recipient' => l10n.dcRoleRecipient,
  'pickup_contact' => l10n.dcRolePickup,
  'dropoff_contact' => l10n.dcRoleDropoff,
  _ => role,
};

String scopeItemText(AppLocalizations l10n, String item) => switch (item) {
  'helpers' => l10n.scHelpers,
  'stairs' => l10n.scStairs,
  'extra_stops' => l10n.scExtraStops,
  'waiting_allowance' => l10n.scWaiting,
  'tolls' => l10n.scTolls,
  'parking' => l10n.scParking,
  'packing' => l10n.scPacking,
  'assembly' => l10n.scAssembly,
  'loading_equipment' => l10n.scEquipment,
  _ => item,
};

DeliveryStrings deliveryStrings(AppLocalizations l10n) => DeliveryStrings(
  custodyTitle: l10n.dcCustodyTitle,
  custody: (s) => custodyText(l10n, s),
  otpTitle: l10n.dcOtpTitle,
  otpWaitingForCode: l10n.dcOtpWaiting,
  otpSent: l10n.dcOtpSent,
  otpSmsFailed: l10n.dcOtpSmsFailed,
  otpVerified: l10n.dcOtpVerified,
  otpLocked: l10n.dcOtpLocked,
  otpAttemptsLeft: l10n.dcOtpAttemptsLeft,
  otpShareHint: l10n.dcOtpShareHint,
  proofsTitle: l10n.dcProofsTitle,
  proofType: (t) => proofTypeText(l10n, t),
  proofStage: (s) => proofStageText(l10n, s),
  noProofs: l10n.dcNoProofs,
  proofLoadError: l10n.dcProofLoadError,
  checklistItem: (code, checked) =>
      '${checked ? '✓' : '✗'} ${checklistCodeText(l10n, code)}',
  weightValue: l10n.dcWeightValue,
  attemptsTitle: l10n.dcAttemptsTitle,
  attemptNumber: l10n.dcAttemptNumber,
  outcome: (o) => attemptOutcomeText(l10n, o),
  resolution: (r) => resolutionText(l10n, r),
  attemptEvidence: l10n.dcAttemptEvidence,
  pending: l10n.dcPending,
);

ScopeStrings scopeStrings(AppLocalizations l10n) => ScopeStrings(
  included: l10n.scLegendIncluded,
  excluded: l10n.scLegendExcluded,
  atCost: l10n.scLegendAtCost,
  title: l10n.scSummaryTitle,
);
