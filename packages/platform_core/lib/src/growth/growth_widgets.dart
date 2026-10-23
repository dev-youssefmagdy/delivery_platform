import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:share_plus/share_plus.dart';

import '../api/api_exception.dart';
import '../money/money_formatter.dart';
import '../ui/components.dart';
import '../ui/tokens.dart';
import 'growth_models.dart';
import 'growth_strings.dart';
import '../ui/isolated_text.dart';

String _date(BuildContext context, DateTime? at) =>
    at == null ? '—' : MaterialLocalizations.of(context).formatMediumDate(at);

/// The referral screen body: the user's code with copy and share, the active
/// offer, a field to enter a code received from someone else (only while the
/// server says one can still be applied), the progress counters and the list.
/// Pure presentation: data and texts come from the app.
class ReferralShareView extends StatefulWidget {
  const ReferralShareView({
    super.key,
    required this.overview,
    required this.formatter,
    required this.strings,
    required this.onApply,
    this.onShare,
  });

  final ReferralOverview overview;
  final MoneyFormatter formatter;
  final ReferralStrings strings;

  /// Applies a typed code; throws on failure (shown with `strings.errorText`).
  final Future<void> Function(String code) onApply;

  /// Overridden in tests; the default opens the system share sheet.
  final Future<void> Function(String text)? onShare;

  @override
  State<ReferralShareView> createState() => _ReferralShareViewState();
}

class _ReferralShareViewState extends State<ReferralShareView> {
  final _controller = TextEditingController();
  bool _busy = false;
  String? _error;

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _share() async {
    final text = widget.overview.shareText;
    if (widget.onShare != null) return widget.onShare!(text);
    await SharePlus.instance.share(ShareParams(text: text));
  }

  Future<void> _copy() async {
    final messenger = ScaffoldMessenger.of(context);
    final text = widget.strings.codeCopied;
    await Clipboard.setData(ClipboardData(text: widget.overview.code));
    messenger.showSnackBar(SnackBar(content: Text(text)));
  }

  Future<void> _apply() async {
    final code = _controller.text.trim();
    if (code.isEmpty || _busy) return;
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await widget.onApply(code);
      if (!mounted) return;
      _controller.clear();
      messenger.showSnackBar(SnackBar(content: Text(widget.strings.applied)));
    } on Object catch (e) {
      if (mounted) setState(() => _error = widget.strings.errorText(e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.strings;
    final o = widget.overview;
    final theme = Theme.of(context);
    final campaign = o.campaign;
    return ListView(
      key: const Key('referral-list'),
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Text(s.yourCode, style: theme.textTheme.bodyMedium),
                const SizedBox(height: AppSpacing.xs),
                Directionality(
                  textDirection: TextDirection.ltr,
                  child: SelectableText(
                    o.code,
                    key: const Key('referral-code'),
                    textAlign: TextAlign.center,
                    style: theme.textTheme.headlineMedium?.copyWith(
                      fontWeight: FontWeight.w700,
                      letterSpacing: 2,
                    ),
                  ),
                ),
                const SizedBox(height: AppSpacing.md),
                Row(
                  children: [
                    Expanded(
                      child: AppButton(
                        key: const Key('referral-copy'),
                        label: s.copyCode,
                        icon: Icons.copy_outlined,
                        secondary: true,
                        onPressed: _copy,
                      ),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Expanded(
                      child: AppButton(
                        key: const Key('referral-share'),
                        label: s.share,
                        icon: Icons.ios_share,
                        onPressed: _share,
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: campaign == null
                ? Text(s.noOffer, key: const Key('referral-no-offer'))
                : Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(s.offerTitle, style: theme.textTheme.titleMedium),
                      const SizedBox(height: AppSpacing.xs),
                      Text(
                        s.offerLine(
                          moneyOrDash(
                            campaign.referrerReward,
                            widget.formatter.format,
                          ),
                          moneyOrDash(
                            campaign.refereeReward,
                            widget.formatter.format,
                          ),
                        ),
                        key: const Key('referral-offer'),
                      ),
                      Text(s.milestoneLine(campaign.milestoneJobs)),
                      if (campaign.creditValidDays != null)
                        Text(s.validityLine(campaign.creditValidDays!)),
                    ],
                  ),
          ),
        ),
        if (o.canApplyCode) ...[
          const SizedBox(height: AppSpacing.md),
          Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(s.haveCode, style: theme.textTheme.titleMedium),
                  const SizedBox(height: AppSpacing.sm),
                  AppTextField(
                    key: const Key('referral-input'),
                    label: s.codeHint,
                    controller: _controller,
                    textDirection: TextDirection.ltr,
                    errorText: _error,
                    textInputAction: TextInputAction.done,
                    onSubmitted: (_) => unawaited(_apply()),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  AppButton(
                    key: const Key('referral-apply'),
                    label: s.apply,
                    loading: _busy,
                    onPressed: _apply,
                  ),
                ],
              ),
            ),
          ),
        ],
        const SizedBox(height: AppSpacing.md),
        Row(
          children: [
            _Stat(label: s.statReferred, value: o.stats.referred),
            _Stat(label: s.statRewarded, value: o.stats.qualified),
            _Stat(label: s.statPending, value: o.stats.pending),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        Text(s.listTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (o.referrals.isEmpty)
          Padding(
            padding: const EdgeInsetsDirectional.symmetric(
              vertical: AppSpacing.md,
            ),
            child: Text(s.listEmpty, key: const Key('referral-empty')),
          )
        else
          for (final r in o.referrals)
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(r.name ?? '—'),
              subtitle: Text(_date(context, r.createdAt)),
              trailing: StatusChip(
                label: switch (r.status) {
                  ReferralStatus.rewarded => s.statusRewarded,
                  ReferralStatus.closed => s.statusClosed,
                  _ => s.statusPending,
                },
                tone: switch (r.status) {
                  ReferralStatus.rewarded => ChipTone.success,
                  ReferralStatus.closed => ChipTone.neutral,
                  _ => ChipTone.info,
                },
              ),
            ),
      ],
    );
  }
}

class _Stat extends StatelessWidget {
  const _Stat({required this.label, required this.value});

  final String label;
  final int value;

  @override
  Widget build(BuildContext context) => Expanded(
    child: Column(
      children: [
        Text(
          '$value',
          style: Theme.of(context).textTheme.headlineSmall
              ?.copyWith(fontWeight: FontWeight.w700),
        ),
        Text(
          label,
          textAlign: TextAlign.center,
          style: Theme.of(context).textTheme.bodySmall,
        ),
      ],
    ),
  );
}

/// Promotional credit in the wallet: available amount and every credit with its
/// expiry.
class CreditsView extends StatelessWidget {
  const CreditsView({
    super.key,
    required this.overview,
    required this.formatter,
    required this.strings,
  });

  final CreditsOverview overview;
  final MoneyFormatter formatter;
  final CreditsStrings strings;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return ListView(
      key: const Key('credits-list'),
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(strings.available, style: theme.textTheme.bodyMedium),
                IsolatedText(
                  moneyOrDash(overview.available, formatter.format),
                  key: const Key('credits-available'),
                  style: theme.textTheme.headlineMedium?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                if (overview.note != null) ...[
                  const SizedBox(height: AppSpacing.sm),
                  Text(overview.note!, style: theme.textTheme.bodySmall),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Text(strings.historyTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        if (overview.credits.isEmpty)
          Padding(
            padding: const EdgeInsetsDirectional.symmetric(
              vertical: AppSpacing.md,
            ),
            child: Text(strings.none, key: const Key('credits-empty')),
          )
        else
          for (final c in overview.credits)
            ListTile(
              contentPadding: EdgeInsets.zero,
              title: Text(
                c.source == CreditSource.loyalty
                    ? strings.sourceLoyalty
                    : strings.sourceReferral,
              ),
              subtitle: Text(
                c.state == CreditState.active
                    ? '${strings.remaining(moneyOrDash(c.remaining, formatter.format))} · ${strings.expiresOn(_date(context, c.expiresAt))}'
                    : _date(context, c.expiresAt ?? c.grantedAt),
              ),
              trailing: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  IsolatedText(moneyOrDash(c.amount, formatter.format)),
                  StatusChip(
                    label: switch (c.state) {
                      CreditState.expired => strings.stateExpired,
                      CreditState.reversed => strings.stateReversed,
                      _ => strings.stateActive,
                    },
                    tone: c.state == CreditState.active
                        ? ChipTone.success
                        : ChipTone.neutral,
                  ),
                ],
              ),
            ),
      ],
    );
  }
}

/// Loyalty status: completed jobs, the current level, progress to the next and
/// the ladder of levels.
class LoyaltyView extends StatelessWidget {
  const LoyaltyView({
    super.key,
    required this.status,
    required this.formatter,
    required this.strings,
  });

  final LoyaltyStatus status;
  final MoneyFormatter formatter;
  final LoyaltyStrings strings;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final next = status.nextLevel;
    final current = status.level;
    final progress = next == null || next.minCompletedJobs == 0
        ? 1.0
        : (status.completedJobs / next.minCompletedJobs).clamp(0, 1).toDouble();
    return ListView(
      key: const Key('loyalty-list'),
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Card(
          margin: EdgeInsets.zero,
          child: Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(strings.currentLevel, style: theme.textTheme.bodyMedium),
                Text(
                  current?.name ?? strings.noLevel,
                  key: const Key('loyalty-level'),
                  style: theme.textTheme.headlineSmall?.copyWith(
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(strings.completedJobs(status.completedJobs)),
                const SizedBox(height: AppSpacing.md),
                LinearProgressIndicator(
                  value: progress,
                  minHeight: 8,
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                ),
                const SizedBox(height: AppSpacing.xs),
                Text(
                  next == null
                      ? strings.topLevel
                      : strings.toNextLevel(
                          status.jobsToNextLevel ?? 0,
                          next.name,
                        ),
                  style: theme.textTheme.bodySmall,
                ),
              ],
            ),
          ),
        ),
        const SizedBox(height: AppSpacing.md),
        Text(strings.levelsTitle, style: theme.textTheme.titleMedium),
        const SizedBox(height: AppSpacing.sm),
        for (final level in status.levels)
          ListTile(
            contentPadding: EdgeInsets.zero,
            leading: Icon(
              level.reached ? Icons.check_circle : Icons.radio_button_unchecked,
              color: level.reached ? AppColors.success : null,
            ),
            title: Text(level.name),
            subtitle: Text(
              [
                strings.levelJobs(level.minCompletedJobs),
                if (level.reward != null)
                  strings.levelReward(formatter.format(level.reward!)),
              ].join(' · '),
            ),
            trailing: level.reached
                ? Text(strings.reached, style: theme.textTheme.labelMedium)
                : null,
          ),
      ],
    );
  }
}

/// The provider's open incentive campaigns with quest progress.
class IncentivesView extends StatelessWidget {
  const IncentivesView({
    super.key,
    required this.quests,
    required this.formatter,
    required this.strings,
  });

  final List<IncentiveQuest> quests;
  final MoneyFormatter formatter;
  final IncentiveStrings strings;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (quests.isEmpty) {
      return ListView(
        physics: const AlwaysScrollableScrollPhysics(),
        children: [
          SizedBox(
            height: 320,
            child: EmptyState(
              key: const Key('incentives-empty'),
              icon: Icons.emoji_events_outlined,
              title: strings.none,
            ),
          ),
        ],
      );
    }
    return ListView(
      key: const Key('incentives-list'),
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        for (final q in quests) ...[
          Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(q.name, style: theme.textTheme.titleMedium),
                      ),
                      StatusChip(
                        label: switch (q.state) {
                          QuestState.completed => strings.stateCompleted,
                          QuestState.pending => strings.statePending,
                          QuestState.closed => strings.stateClosed,
                          _ => strings.stateInProgress,
                        },
                        tone: switch (q.state) {
                          QuestState.completed => ChipTone.success,
                          QuestState.pending => ChipTone.warning,
                          QuestState.closed => ChipTone.neutral,
                          _ => ChipTone.info,
                        },
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    strings.questLine(
                      q.targetJobs,
                      moneyOrDash(q.bonus, formatter.format),
                    ),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  LinearProgressIndicator(
                    value: q.fraction,
                    minHeight: 8,
                    borderRadius: BorderRadius.circular(AppRadius.sm),
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    strings.progress(q.jobsInCycle, q.targetJobs),
                    style: theme.textTheme.bodySmall,
                  ),
                  if (q.maxAwards > 1)
                    Text(
                      strings.awardsLine(q.awards, q.maxAwards),
                      style: theme.textTheme.bodySmall,
                    ),
                  if (q.minRating != null)
                    Text(
                      strings.minRating(q.minRating!),
                      style: theme.textTheme.bodySmall,
                    ),
                  Text(
                    strings.endsOn(_date(context, q.endsAt)),
                    style: theme.textTheme.bodySmall,
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: AppSpacing.md),
        ],
      ],
    );
  }
}

/// True when [error] is the server's "feature switched off" answer.
bool isFeatureDisabled(Object error) =>
    error is ApiException && error.code == 'feature_disabled';
