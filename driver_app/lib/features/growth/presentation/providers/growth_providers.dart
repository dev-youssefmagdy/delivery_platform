import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../auth/presentation/providers/auth_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

/// Server flag keys of the growth features (docs/API.md 8.4).
abstract final class GrowthFlags {
  static const referrals = 'referrals';
  static const incentives = 'incentives';
}

final growthRepositoryProvider = Provider<GrowthRepository>(
  (ref) =>
      ApiGrowthRepository(GrowthRemoteDataSource(ref.watch(apiClientProvider))),
);

/// Whether a growth feature is on for this customer. Everything about it (menu
/// entries, screens) is hidden while this is false: no flag, no UI.
final growthFeatureProvider = Provider.family<bool, String>((ref, key) {
  final config = ref.watch(appConfigProvider).value;
  final me = ref.watch(meProvider).value;
  return config?.featureEnabled(key, subject: me?.id) ?? false;
});

/// The referral code, offer and progress. Refetched after a code is applied.
class ReferralNotifier extends AsyncNotifier<ReferralOverview> {
  String? _applyKey;
  String? _applyCode;

  @override
  Future<ReferralOverview> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(growthRepositoryProvider).referral();
  }

  Future<void> reload() async {
    state = const AsyncLoading<ReferralOverview>();
    state = await AsyncValue.guard(
      () => ref.read(growthRepositoryProvider).referral(),
    );
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(growthRepositoryProvider).referral());
    } on Object {
      // Keep what is on screen.
    }
  }

  /// Applies a received code. One Idempotency-Key per typed code: a retry of the same code reuses it.
  Future<void> apply(String code) async {
    if (_applyCode != code || _applyKey == null) {
      _applyCode = code;
      _applyKey = newIdempotencyKey();
    }
    await ref
        .read(growthRepositoryProvider)
        .applyReferral(code, key: _applyKey!);
    _applyKey = null;
    _applyCode = null;
    await refresh();
  }
}

final referralProvider =
    AsyncNotifierProvider<ReferralNotifier, ReferralOverview>(
      ReferralNotifier.new,
      retry: _noAutoRetry,
    );

/// The provider's open incentive campaigns with quest progress.
class IncentivesNotifier extends AsyncNotifier<List<IncentiveQuest>> {
  @override
  Future<List<IncentiveQuest>> build() {
    ref.watch(sessionStatusProvider);
    return ref.read(growthRepositoryProvider).incentives();
  }

  Future<void> reload() async {
    state = const AsyncLoading<List<IncentiveQuest>>();
    state = await AsyncValue.guard(
      () => ref.read(growthRepositoryProvider).incentives(),
    );
  }

  Future<void> refresh() async {
    try {
      state = AsyncData(await ref.read(growthRepositoryProvider).incentives());
    } on Object {
      // Keep what is on screen.
    }
  }
}

final incentivesProvider =
    AsyncNotifierProvider<IncentivesNotifier, List<IncentiveQuest>>(
      IncentivesNotifier.new,
      retry: _noAutoRetry,
    );

/// Maps a failed code application to ARB text; the stable error code decides.
String referralErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.refErrGeneric;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  return switch (error.code) {
    'referral_code_invalid' => l10n.refErrInvalid,
    'referral_self' => l10n.refErrSelf,
    'referral_not_new' => l10n.refErrNotNew,
    'referral_campaign_unavailable' => l10n.refErrUnavailable,
    'referral_cap_reached' => l10n.refErrCap,
    'referral_already_applied' => l10n.refErrApplied,
    _ => l10n.refErrGeneric,
  };
}

ReferralStrings referralStrings(AppLocalizations l10n) => ReferralStrings(
  yourCode: l10n.refYourCode,
  copyCode: l10n.refCopy,
  codeCopied: l10n.refCopied,
  share: l10n.refShare,
  noOffer: l10n.refNoOffer,
  offerTitle: l10n.refOfferTitle,
  offerLine: (referrer, referee) => l10n.refOfferLine(referrer, referee),
  milestoneLine: l10n.refMilestone,
  validityLine: l10n.refValidity,
  haveCode: l10n.refHaveCode,
  codeHint: l10n.refCodeHint,
  apply: l10n.refApply,
  applied: l10n.refApplied,
  statReferred: l10n.refStatReferred,
  statRewarded: l10n.refStatRewarded,
  statPending: l10n.refStatPending,
  listTitle: l10n.refListTitle,
  listEmpty: l10n.refListEmpty,
  statusPending: l10n.refStatusPending,
  statusRewarded: l10n.refStatusRewarded,
  statusClosed: l10n.refStatusClosed,
  jobsProgress: (done, needed) => '$done / $needed',
  errorText: (e) => referralErrorText(l10n, e),
);

IncentiveStrings incentiveStrings(AppLocalizations l10n) => IncentiveStrings(
  none: l10n.incNone,
  questLine: l10n.incQuestLine,
  progress: l10n.incProgress,
  endsOn: l10n.incEndsOn,
  awardsLine: l10n.incAwards,
  stateInProgress: l10n.incStateInProgress,
  stateCompleted: l10n.incStateCompleted,
  statePending: l10n.incStatePending,
  stateClosed: l10n.incStateClosed,
  minRating: l10n.incMinRating,
);
