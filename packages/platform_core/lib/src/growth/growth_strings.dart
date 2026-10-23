import '../money/money.dart';

/// Texts of the referral screen. The apps build it from their ARB files, so no
/// user-visible string lives in this package (Plan 6.2).
class ReferralStrings {
  const ReferralStrings({
    required this.yourCode,
    required this.copyCode,
    required this.codeCopied,
    required this.share,
    required this.noOffer,
    required this.offerTitle,
    required this.offerLine,
    required this.milestoneLine,
    required this.validityLine,
    required this.haveCode,
    required this.codeHint,
    required this.apply,
    required this.applied,
    required this.statReferred,
    required this.statRewarded,
    required this.statPending,
    required this.listTitle,
    required this.listEmpty,
    required this.statusPending,
    required this.statusRewarded,
    required this.statusClosed,
    required this.jobsProgress,
    required this.errorText,
  });

  final String yourCode;
  final String copyCode;
  final String codeCopied;
  final String share;
  final String noOffer;
  final String offerTitle;

  /// "Give [referee reward], get [referrer reward]" with formatted amounts.
  final String Function(String referrer, String referee) offerLine;
  final String Function(int jobs) milestoneLine;
  final String Function(int days) validityLine;
  final String haveCode;
  final String codeHint;
  final String apply;
  final String applied;
  final String statReferred;
  final String statRewarded;
  final String statPending;
  final String listTitle;
  final String listEmpty;
  final String statusPending;
  final String statusRewarded;
  final String statusClosed;
  final String Function(int done, int needed) jobsProgress;

  /// Maps a failed apply to text (the stable error code decides).
  final String Function(Object error) errorText;
}

class CreditsStrings {
  const CreditsStrings({
    required this.available,
    required this.none,
    required this.sourceReferral,
    required this.sourceLoyalty,
    required this.stateActive,
    required this.stateExpired,
    required this.stateReversed,
    required this.expiresOn,
    required this.remaining,
    required this.historyTitle,
  });

  final String available;
  final String none;
  final String sourceReferral;
  final String sourceLoyalty;
  final String stateActive;
  final String stateExpired;
  final String stateReversed;
  final String Function(String date) expiresOn;
  final String Function(String amount) remaining;
  final String historyTitle;
}

class LoyaltyStrings {
  const LoyaltyStrings({
    required this.completedJobs,
    required this.currentLevel,
    required this.noLevel,
    required this.toNextLevel,
    required this.topLevel,
    required this.levelsTitle,
    required this.levelJobs,
    required this.levelReward,
    required this.reached,
  });

  final String Function(int jobs) completedJobs;
  final String currentLevel;
  final String noLevel;
  final String Function(int jobs, String level) toNextLevel;
  final String topLevel;
  final String levelsTitle;
  final String Function(int jobs) levelJobs;
  final String Function(String amount) levelReward;
  final String reached;
}

class IncentiveStrings {
  const IncentiveStrings({
    required this.none,
    required this.questLine,
    required this.progress,
    required this.endsOn,
    required this.awardsLine,
    required this.stateInProgress,
    required this.stateCompleted,
    required this.statePending,
    required this.stateClosed,
    required this.minRating,
  });

  final String none;

  /// "Complete [jobs] jobs, earn [bonus]".
  final String Function(int jobs, String bonus) questLine;
  final String Function(int done, int target) progress;
  final String Function(String date) endsOn;
  final String Function(int earned, int max) awardsLine;
  final String stateInProgress;
  final String stateCompleted;
  final String statePending;
  final String stateClosed;
  final String Function(String rating) minRating;
}

/// Formats an optional [Money] with a fallback dash.
String moneyOrDash(Money? money, String Function(Money) format) => money == null ? '—' : format(money);
