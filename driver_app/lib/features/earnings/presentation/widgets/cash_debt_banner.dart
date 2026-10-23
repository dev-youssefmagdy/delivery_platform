import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';

/// The cash commission the driver owes the platform and what it means for
/// getting requests: over the limit the server stops sending them
/// (eligibility reason `cash_debt_limit_exceeded`). Shows nothing without debt.
class CashDebtBanner extends ConsumerWidget {
  const CashDebtBanner({super.key, required this.position});

  final ProviderWalletPosition position;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    if (!position.hasCashDebt) return const SizedBox.shrink();
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final f = ref.watch(moneyFormatterProvider);
    final blocked = position.cashDebtBlocksWork;
    final color = blocked ? AppColors.danger : AppColors.warning;
    return Semantics(
      liveRegion: true,
      child: DecoratedBox(
        key: const Key('cash-debt-banner'),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.1),
          borderRadius: BorderRadius.circular(AppRadius.md),
        ),
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Icon(
                    blocked ? Icons.block : Icons.warning_amber_rounded,
                    color: color,
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  Expanded(
                    child: Text(
                      l10n.cashDebtTitle(f.format(position.cashDebt)),
                      style: theme.textTheme.titleMedium,
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                blocked
                    ? l10n.cashDebtBlocked(f.format(position.cashDebtLimit))
                    : l10n.cashDebtWarning(f.format(position.cashDebtLimit)),
                key: Key(blocked ? 'cash-debt-blocked' : 'cash-debt-warning'),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(l10n.cashDebtHowTo, style: theme.textTheme.bodySmall),
              TextButton(
                key: const Key('cash-debt-help'),
                onPressed: () => context.push(Routes.support),
                child: Text(l10n.cashDebtContact),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
