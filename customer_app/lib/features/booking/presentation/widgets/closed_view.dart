import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../domain/entities/booking_entities.dart';
import 'booking_sections.dart';
import 'rating_card.dart';

/// Completed, cancelled, expired and other ended bookings (also used for trip
/// details in My Trips): outcome, receipt, rating, report and rebook.
class ClosedView extends ConsumerWidget {
  const ClosedView({super.key, required this.booking});

  final Booking booking;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final formatter = ref.watch(moneyFormatterProvider);
    final cancellation = booking.cancellation;
    final noProvider = booking.status == BookingStatus.expiredNoProvider;

    return ListView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        BookingSummaryCard(
          booking: booking,
          formatter: formatter,
          languageCode: language,
        ),
        const SizedBox(height: AppSpacing.md),
        if (noProvider)
          Card(
            key: const Key('no-provider-card'),
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  Text(
                    l10n.bkNoProviderTitle,
                    style: theme.textTheme.titleMedium,
                  ),
                  const SizedBox(height: AppSpacing.xs),
                  Text(l10n.bkNoProviderBody),
                  const SizedBox(height: AppSpacing.xs),
                  Text(
                    l10n.bkNoOffersNoGuarantee,
                    style: theme.textTheme.bodySmall,
                  ),
                  const SizedBox(height: AppSpacing.md),
                  AppButton(
                    key: const Key('try-again'),
                    label: l10n.bkTryAgain,
                    onPressed: () => rebookFrom(context, ref, booking),
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  AppButton(
                    key: const Key('edit-price-again'),
                    label: l10n.bkEditPriceAgain,
                    secondary: true,
                    onPressed: () => rebookFrom(context, ref, booking),
                  ),
                ],
              ),
            ),
          ),
        if (booking.isCancelled && cancellation != null) ...[
          Card(
            key: const Key('cancellation-card'),
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    l10n.bkCancelledTitle,
                    style: theme.textTheme.titleMedium,
                  ),
                  if (cancellation.fee != null && !cancellation.fee!.isZero)
                    Text(
                      l10n.bkCancelledFee(formatter.format(cancellation.fee!)),
                      key: const Key('cancellation-fee'),
                    )
                  else
                    Text(l10n.bkCancelledNoFee),
                  if (cancellation.cancelledAt != null)
                    Text(
                      formatDateTime(context, cancellation.cancelledAt),
                      style: theme.textTheme.bodySmall,
                    ),
                ],
              ),
            ),
          ),
        ],
        if (booking.receipt != null) ...[
          const SizedBox(height: AppSpacing.md),
          ReceiptSection(receipt: booking.receipt!, formatter: formatter),
        ],
        if (booking.can(BookingAction.rate)) ...[
          const SizedBox(height: AppSpacing.md),
          RatingCard(bookingId: booking.id),
        ],
        const SizedBox(height: AppSpacing.md),
        Wrap(
          spacing: AppSpacing.sm,
          runSpacing: AppSpacing.sm,
          children: [
            if (booking.can(BookingAction.report))
              OutlinedButton.icon(
                key: const Key('report-button'),
                icon: const Icon(Icons.flag_outlined),
                label: Text(l10n.bkReportIssue),
                onPressed: () => context.push(Routes.bookingReport(booking.id)),
              ),
            if (!noProvider &&
                (booking.isFinished ||
                    booking.isCancelled))
              FilledButton.icon(
                key: const Key('rebook-button'),
                icon: const Icon(Icons.replay),
                label: Text(l10n.bkRebook),
                onPressed: () => rebookFrom(context, ref, booking),
              ),
          ],
        ),
        const SizedBox(height: AppSpacing.md),
        BookingTimelineSection(booking: booking),
      ],
    );
  }
}
