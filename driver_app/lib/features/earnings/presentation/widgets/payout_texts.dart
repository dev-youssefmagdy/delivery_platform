import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// Payout status as ARB text (the server label is only a fallback).
String payoutStatusText(
  AppLocalizations l10n,
  PayoutStatus status, {
  String? fallback,
}) => switch (status) {
  PayoutStatus.requested => l10n.poStatusRequested,
  PayoutStatus.underReview => l10n.poStatusReview,
  PayoutStatus.approved => l10n.poStatusApproved,
  PayoutStatus.processing => l10n.poStatusProcessing,
  PayoutStatus.paid => l10n.poStatusPaid,
  PayoutStatus.failed => l10n.poStatusFailed,
  PayoutStatus.rejected => l10n.poStatusRejected,
  PayoutStatus.cancelled => l10n.poStatusCancelled,
  PayoutStatus.reversed => l10n.poStatusReversed,
  PayoutStatus.unknown => fallback ?? l10n.poStatusUnknown,
};

ChipTone payoutStatusTone(PayoutStatus status) => switch (status) {
  PayoutStatus.paid => ChipTone.success,
  PayoutStatus.failed ||
  PayoutStatus.rejected ||
  PayoutStatus.reversed => ChipTone.danger,
  PayoutStatus.cancelled || PayoutStatus.unknown => ChipTone.neutral,
  PayoutStatus.requested || PayoutStatus.underReview => ChipTone.warning,
  PayoutStatus.approved || PayoutStatus.processing => ChipTone.info,
};

/// What the status means for the driver's money, in plain words.
String payoutStatusExplain(AppLocalizations l10n, PayoutRequest p) =>
    switch (p.status) {
      PayoutStatus.requested ||
      PayoutStatus.underReview => l10n.poExplainReview,
      PayoutStatus.approved => l10n.poExplainApproved,
      PayoutStatus.processing => l10n.poExplainProcessing,
      PayoutStatus.paid => l10n.poExplainPaid,
      PayoutStatus.rejected => l10n.poExplainRejected,
      PayoutStatus.failed => l10n.poExplainFailed,
      PayoutStatus.cancelled => l10n.poExplainCancelled,
      PayoutStatus.reversed => l10n.poExplainReversed,
      PayoutStatus.unknown => '',
    };
