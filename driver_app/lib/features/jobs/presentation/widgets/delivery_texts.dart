import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

String custodyText(AppLocalizations l10n, CustodyStatus s) => switch (s) {
  CustodyStatus.pickedUp => l10n.dlCustodyPickedUp,
  CustodyStatus.inTransit => l10n.dlCustodyInTransit,
  CustodyStatus.arrivedDropoff => l10n.dlCustodyArrived,
  CustodyStatus.attemptFailed => l10n.dlCustodyFailed,
  CustodyStatus.returning => l10n.dlCustodyReturning,
  CustodyStatus.delivered => l10n.dlCustodyDelivered,
  CustodyStatus.returned => l10n.dlCustodyReturned,
  CustodyStatus.unknown => l10n.dlCustodyUnknown,
};

String attemptOutcomeText(AppLocalizations l10n, AttemptOutcome o) =>
    switch (o) {
      AttemptOutcome.recipientUnavailable => l10n.dlOutUnavailable,
      AttemptOutcome.recipientRefused => l10n.dlOutRefused,
      AttemptOutcome.wrongAddress => l10n.dlOutWrongAddress,
      AttemptOutcome.otpFailed => l10n.dlOutOtpFailed,
      AttemptOutcome.restrictedItem => l10n.dlOutRestricted,
      AttemptOutcome.other || AttemptOutcome.unknown => l10n.dlOutOther,
    };

String resolutionText(AppLocalizations l10n, AttemptResolution r) =>
    switch (r) {
      AttemptResolution.retry => l10n.dlResRetry,
      AttemptResolution.returnToSender => l10n.dlResReturn,
      AttemptResolution.operations => l10n.dlResOperations,
      AttemptResolution.unknown => l10n.dlResUnknown,
    };

String proofTypeText(AppLocalizations l10n, ProofType t) => switch (t) {
  ProofType.photo => l10n.dlProofPhoto,
  ProofType.signature => l10n.dlProofSignature,
  ProofType.recipientOtp => l10n.dlProofOtp,
  ProofType.pickupPin => l10n.dlProofPin,
  ProofType.checklist => l10n.dlProofChecklist,
  ProofType.weight => l10n.dlProofWeight,
  ProofType.unknown => l10n.dlProofOther,
};

String proofStageText(AppLocalizations l10n, String stage) => switch (stage) {
  'pickup' => l10n.dlStagePickup,
  'delivery' => l10n.dlStageDelivery,
  'return' => l10n.dlStageReturn,
  _ => stage,
};

String checklistCodeText(AppLocalizations l10n, String code) => switch (code) {
  'package_sealed' => l10n.dlChkSealed,
  'package_undamaged' => l10n.dlChkUndamaged,
  'label_matches' => l10n.dlChkLabel,
  'recipient_checked' => l10n.dlChkRecipient,
  _ => code,
};

DeliveryStrings deliveryStrings(AppLocalizations l10n) => DeliveryStrings(
  custodyTitle: l10n.dlCustodyTitle,
  custody: (s) => custodyText(l10n, s),
  otpTitle: l10n.dlOtpTitle,
  otpWaitingForCode: l10n.dlOtpWaiting,
  otpSent: l10n.dlOtpSent,
  otpSmsFailed: l10n.dlOtpSmsFailed,
  otpVerified: l10n.dlOtpVerified,
  otpLocked: l10n.dlOtpLocked,
  otpAttemptsLeft: l10n.dlOtpAttemptsLeft,
  otpShareHint: l10n.dlOtpAskRecipient,
  proofsTitle: l10n.dlProofsTitle,
  proofType: (t) => proofTypeText(l10n, t),
  proofStage: (s) => proofStageText(l10n, s),
  noProofs: l10n.dlNoProofs,
  proofLoadError: l10n.dlProofLoadError,
  checklistItem: (code, checked) =>
      '${checked ? '✓' : '✗'} ${checklistCodeText(l10n, code)}',
  weightValue: l10n.dlWeightValue,
  attemptsTitle: l10n.dlAttemptsTitle,
  attemptNumber: l10n.dlAttemptNumber,
  outcome: (o) => attemptOutcomeText(l10n, o),
  resolution: (r) => resolutionText(l10n, r),
  attemptEvidence: l10n.dlAttemptEvidence,
  pending: l10n.dlPending,
);
