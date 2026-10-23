import '../money/money.dart';

Map<String, dynamic> _map(Object? v) => v is Map ? Map<String, dynamic>.from(v) : const {};

List<Map<String, dynamic>> _maps(Object? v) => [
  if (v is List)
    for (final e in v)
      if (e is Map) Map<String, dynamic>.from(e),
];

int _int(Object? v) => v is num ? v.toInt() : (v is String ? int.tryParse(v) ?? 0 : 0);

DateTime? _time(Object? v) => v is String ? DateTime.tryParse(v)?.toLocal() : null;

Money? _money(Object? v) => v is Map ? Money.fromJson(Map<String, dynamic>.from(v)) : null;

/// Status of one referral as the referrer sees it. A flagged referral is shown
/// as pending: the app never reveals fraud review. Unknown values degrade to [unknown].
enum ReferralStatus {
  pending,
  rewarded,
  closed,
  unknown;

  static ReferralStatus parse(Object? v) => switch (v) {
    'pending' => pending,
    'rewarded' => rewarded,
    'closed' => closed,
    _ => unknown,
  };
}

/// The active referral offer (`campaign` of `GET /me/referral`).
class ReferralCampaignInfo {
  const ReferralCampaignInfo({
    required this.name,
    required this.referrerReward,
    required this.refereeReward,
    required this.milestoneJobs,
    this.creditValidDays,
    this.endsAt,
  });

  factory ReferralCampaignInfo.fromJson(Map<String, dynamic> j) => ReferralCampaignInfo(
    name: (j['name'] as String?) ?? '',
    referrerReward: _money(j['referrer_reward']),
    refereeReward: _money(j['referee_reward']),
    milestoneJobs: _int(j['milestone_jobs']) == 0 ? 1 : _int(j['milestone_jobs']),
    creditValidDays: j['credit_valid_days'] is num ? _int(j['credit_valid_days']) : null,
    endsAt: _time(j['ends_at']),
  );

  final String name;
  final Money? referrerReward;
  final Money? refereeReward;
  final int milestoneJobs;

  /// Customer credit only; null for providers (their reward does not expire).
  final int? creditValidDays;
  final DateTime? endsAt;
}

class ReferralStats {
  const ReferralStats({this.referred = 0, this.qualified = 0, this.pending = 0});

  factory ReferralStats.fromJson(Map<String, dynamic> j) =>
      ReferralStats(referred: _int(j['referred']), qualified: _int(j['qualified']), pending: _int(j['pending']));

  final int referred;
  final int qualified;
  final int pending;
}

class ReferralEntry {
  const ReferralEntry({required this.id, required this.status, required this.jobsCompleted, this.name, this.createdAt});

  factory ReferralEntry.fromJson(Map<String, dynamic> j) => ReferralEntry(
    id: (j['id'] as String?) ?? '',
    name: j['name'] as String?,
    status: ReferralStatus.parse(j['status']),
    jobsCompleted: _int(j['jobs_completed']),
    createdAt: _time(j['created_at']),
  );

  final String id;
  final String? name;
  final ReferralStatus status;
  final int jobsCompleted;
  final DateTime? createdAt;
}

/// `GET /me/referral`: the user's code and link, the active offer and progress.
class ReferralOverview {
  const ReferralOverview({
    required this.code,
    required this.link,
    required this.shareText,
    required this.canApplyCode,
    required this.stats,
    required this.referrals,
    this.campaign,
  });

  factory ReferralOverview.fromJson(Map<String, dynamic> j) => ReferralOverview(
    code: (j['code'] as String?) ?? '',
    link: (j['link'] as String?) ?? '',
    shareText: (j['share_text'] as String?) ?? '',
    canApplyCode: j['can_apply_code'] == true,
    campaign: j['campaign'] is Map ? ReferralCampaignInfo.fromJson(_map(j['campaign'])) : null,
    stats: ReferralStats.fromJson(_map(j['stats'])),
    referrals: [for (final e in _maps(j['referrals'])) ReferralEntry.fromJson(e)],
  );

  final String code;
  final String link;
  final String shareText;
  final bool canApplyCode;
  final ReferralCampaignInfo? campaign;
  final ReferralStats stats;
  final List<ReferralEntry> referrals;
}

enum CreditSource {
  referral,
  loyalty,
  unknown;

  static CreditSource parse(Object? v) => switch (v) {
    'referral' => referral,
    'loyalty' => loyalty,
    _ => unknown,
  };
}

enum CreditState {
  active,
  expired,
  reversed,
  unknown;

  static CreditState parse(Object? v) => switch (v) {
    'granted' => active,
    'expired' => expired,
    'reversed' => reversed,
    _ => unknown,
  };
}

class CreditEntry {
  const CreditEntry({required this.source, required this.state, this.amount, this.remaining, this.grantedAt, this.expiresAt});

  factory CreditEntry.fromJson(Map<String, dynamic> j) => CreditEntry(
    source: CreditSource.parse(j['source']),
    state: CreditState.parse(j['status']),
    amount: _money(j['amount']),
    remaining: _money(j['remaining']),
    grantedAt: _time(j['granted_at']),
    expiresAt: _time(j['expires_at']),
  );

  final CreditSource source;
  final CreditState state;
  final Money? amount;
  final Money? remaining;
  final DateTime? grantedAt;
  final DateTime? expiresAt;
}

/// `GET /me/credits`: promotional credit in the wallet, with expiry.
class CreditsOverview {
  const CreditsOverview({required this.credits, this.available, this.note});

  factory CreditsOverview.fromJson(Map<String, dynamic> j) =>
      CreditsOverview(available: _money(j['available']), note: j['note'] as String?, credits: [for (final e in _maps(j['credits'])) CreditEntry.fromJson(e)]);

  final Money? available;
  final String? note;
  final List<CreditEntry> credits;
}

class LoyaltyLevelInfo {
  const LoyaltyLevelInfo({required this.id, required this.name, required this.minCompletedJobs, this.reward, this.reached = false});

  factory LoyaltyLevelInfo.fromJson(Map<String, dynamic> j) => LoyaltyLevelInfo(
    id: _int(j['id']),
    name: (j['name'] as String?) ?? '',
    minCompletedJobs: _int(j['min_completed_jobs']),
    reward: _money(j['reward']),
    reached: j['reached'] == true,
  );

  final int id;
  final String name;
  final int minCompletedJobs;
  final Money? reward;
  final bool reached;
}

/// `GET /me/loyalty`: completed jobs, current and next level.
class LoyaltyStatus {
  const LoyaltyStatus({required this.completedJobs, required this.levels, this.level, this.nextLevel, this.jobsToNextLevel});

  factory LoyaltyStatus.fromJson(Map<String, dynamic> j) => LoyaltyStatus(
    completedJobs: _int(j['completed_jobs']),
    level: j['level'] is Map ? LoyaltyLevelInfo.fromJson(_map(j['level'])) : null,
    nextLevel: j['next_level'] is Map ? LoyaltyLevelInfo.fromJson(_map(j['next_level'])) : null,
    jobsToNextLevel: j['jobs_to_next_level'] is num ? _int(j['jobs_to_next_level']) : null,
    levels: [for (final e in _maps(j['levels'])) LoyaltyLevelInfo.fromJson(e)],
  );

  final int completedJobs;
  final LoyaltyLevelInfo? level;
  final LoyaltyLevelInfo? nextLevel;
  final int? jobsToNextLevel;
  final List<LoyaltyLevelInfo> levels;
}

enum QuestState {
  inProgress,
  completed,
  pending,
  closed,
  unknown;

  static QuestState parse(Object? v) => switch (v) {
    'in_progress' => inProgress,
    'completed' => completed,
    'pending' => pending,
    'closed' => closed,
    _ => unknown,
  };
}

/// One open incentive campaign with the provider's own progress.
class IncentiveQuest {
  const IncentiveQuest({
    required this.id,
    required this.name,
    required this.targetJobs,
    required this.maxAwards,
    required this.jobsInCycle,
    required this.completedJobs,
    required this.awards,
    required this.state,
    this.bonus,
    this.startsAt,
    this.endsAt,
    this.minRating,
  });

  factory IncentiveQuest.fromJson(Map<String, dynamic> j) {
    final p = _map(j['progress']);
    return IncentiveQuest(
      id: (j['id'] as String?) ?? '',
      name: (j['name'] as String?) ?? '',
      targetJobs: _int(j['target_jobs']) == 0 ? 1 : _int(j['target_jobs']),
      maxAwards: _int(j['max_awards']) == 0 ? 1 : _int(j['max_awards']),
      bonus: _money(j['bonus']),
      startsAt: _time(j['starts_at']),
      endsAt: _time(j['ends_at']),
      minRating: j['min_rating']?.toString(),
      jobsInCycle: _int(p['jobs_in_cycle']),
      completedJobs: _int(p['completed_jobs']),
      awards: _int(p['awards']),
      state: QuestState.parse(p['status']),
    );
  }

  final String id;
  final String name;
  final int targetJobs;
  final int maxAwards;
  final Money? bonus;
  final DateTime? startsAt;
  final DateTime? endsAt;
  final String? minRating;
  final int jobsInCycle;
  final int completedJobs;
  final int awards;
  final QuestState state;

  double get fraction => targetJobs <= 0 ? 0 : (jobsInCycle / targetJobs).clamp(0, 1).toDouble();
}
