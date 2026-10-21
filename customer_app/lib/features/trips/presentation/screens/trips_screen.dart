import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../booking/domain/entities/booking_entities.dart';
import '../../../booking/presentation/widgets/booking_sections.dart';
import '../../../booking/presentation/widgets/booking_texts.dart';
import '../../../catalog/presentation/providers/catalog_providers.dart';
import '../providers/trips_providers.dart';

/// My Trips: history with service / status / date filters, newest first.
class TripsScreen extends ConsumerWidget {
  const TripsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final trips = ref.watch(tripsProvider);
    final filter = ref.watch(tripFilterProvider);
    return Scaffold(
      appBar: AppBar(
        title: Text(l10n.tripsTitle),
        actions: [
          IconButton(
            key: const Key('trips-filter-button'),
            tooltip: l10n.tripsFilter,
            icon: Badge(
              isLabelVisible: !filter.isEmpty,
              child: const Icon(Icons.filter_list),
            ),
            onPressed: () => _showFilters(context, ref),
          ),
        ],
      ),
      body: AsyncStateView<TripsState>(
        value: trips,
        onRetry: () => ref.invalidate(tripsProvider),
        onSignIn: () => context.go(Routes.auth),
        builder: (context, state) {
          if (state.items.isEmpty) {
            return EmptyState(
              icon: Icons.history,
              title: filter.isEmpty ? l10n.tripsEmptyTitle : l10n.tripsEmptyFilteredTitle,
              message: filter.isEmpty ? l10n.tripsEmptyBody : l10n.tripsEmptyFilteredBody,
              actionLabel: filter.isEmpty ? null : l10n.tripsClearFilters,
              onAction: filter.isEmpty
                  ? null
                  : ref.read(tripFilterProvider.notifier).clear,
            );
          }
          return RefreshIndicator(
            onRefresh: () async => ref.refresh(tripsProvider.future),
            child: NotificationListener<ScrollNotification>(
              onNotification: (n) {
                if (n.metrics.pixels >= n.metrics.maxScrollExtent - 200) {
                  ref.read(tripsProvider.notifier).loadMore();
                }
                return false;
              },
              child: ListView.builder(
                physics: const AlwaysScrollableScrollPhysics(),
                padding: const EdgeInsetsDirectional.all(AppSpacing.md),
                itemCount: state.items.length + (state.hasMore ? 1 : 0),
                itemBuilder: (context, i) {
                  if (i >= state.items.length) {
                    return state.loadMoreFailed
                        ? TextButton(
                            onPressed: ref.read(tripsProvider.notifier).loadMore,
                            child: Text(l10n.actionRetry),
                          )
                        : const Padding(
                            padding: EdgeInsets.all(AppSpacing.md),
                            child: Center(child: CircularProgressIndicator()),
                          );
                  }
                  return _TripTile(summary: state.items[i]);
                },
              ),
            ),
          );
        },
      ),
    );
  }

  Future<void> _showFilters(BuildContext context, WidgetRef ref) =>
      showModalBottomSheet<void>(
        context: context,
        isScrollControlled: true,
        showDragHandle: true,
        builder: (_) => const _FilterSheet(),
      );
}

class _TripTile extends ConsumerWidget {
  const _TripTile({required this.summary});

  final BookingSummary summary;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final formatter = ref.watch(moneyFormatterProvider);
    return Card(
      key: Key('trip-${summary.id}'),
      child: ListTile(
        minTileHeight: context.density.minTouchTarget,
        title: Text(summary.serviceName.resolve(language)),
        subtitle: Text(
          '${summary.code} · ${formatDateTime(context, summary.createdAt)}',
        ),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            MoneyText(
              money: summary.proposedFare,
              formatter: formatter,
              style: Theme.of(context).textTheme.titleSmall,
            ),
            StatusChip(
              label: bookingStatusText(
                l10n,
                summary.status,
                fallback: summary.statusLabel,
              ),
              tone: bookingStatusTone(summary.status),
            ),
          ],
        ),
        onTap: () => context.push(Routes.booking(summary.id)),
      ),
    );
  }
}

/// Status groups offered in the filter (server statuses behind each).
const _statusGroups = <(String, List<BookingStatus>)>[
  ('completed', [BookingStatus.completed, BookingStatus.completedPendingPayment]),
  (
    'cancelled',
    [
      BookingStatus.cancelledByCustomer,
      BookingStatus.cancelledByProvider,
      BookingStatus.cancelledByOperations,
      BookingStatus.expiredNoProvider,
    ],
  ),
  (
    'active',
    [
      BookingStatus.searchingOffers,
      BookingStatus.offersReceived,
      BookingStatus.providerSelected,
      BookingStatus.providerHeadingToPickup,
      BookingStatus.providerArrived,
      BookingStatus.inProgress,
    ],
  ),
];

class _FilterSheet extends ConsumerStatefulWidget {
  const _FilterSheet();

  @override
  ConsumerState<_FilterSheet> createState() => _FilterSheetState();
}

class _FilterSheetState extends ConsumerState<_FilterSheet> {
  late int? _service;
  late String? _group;
  DateTime? _from;
  DateTime? _to;

  @override
  void initState() {
    super.initState();
    final f = ref.read(tripFilterProvider);
    _service = f.serviceCategoryId;
    _from = f.from;
    _to = f.to;
    _group = null;
    for (final g in _statusGroups) {
      if (f.statuses.isNotEmpty && g.$2.length == f.statuses.length &&
          g.$2.every(f.statuses.contains)) {
        _group = g.$1;
      }
    }
  }

  Future<void> _pickDate(bool from) async {
    final now = DateTime.now();
    final picked = await showDatePicker(
      context: context,
      initialDate: (from ? _from : _to) ?? now,
      firstDate: DateTime(now.year - 3),
      lastDate: now,
    );
    if (picked != null) setState(() => from ? _from = picked : _to = picked);
  }

  String _groupLabel(AppLocalizations l10n, String key) => switch (key) {
    'completed' => l10n.tripsStatusCompleted,
    'cancelled' => l10n.tripsStatusCancelled,
    _ => l10n.tripsStatusActive,
  };

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final categories = ref.watch(catalogProvider).value?.categories ?? const [];
    return SafeArea(
      child: SingleChildScrollView(
        padding: EdgeInsetsDirectional.only(
          start: AppSpacing.md,
          end: AppSpacing.md,
          bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.md,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Text(l10n.tripsFilter, style: Theme.of(context).textTheme.titleLarge),
            const SizedBox(height: AppSpacing.md),
            Text(l10n.tripsFilterStatus, style: Theme.of(context).textTheme.titleSmall),
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                for (final g in _statusGroups)
                  ChoiceChip(
                    key: Key('trips-status-${g.$1}'),
                    label: Text(_groupLabel(l10n, g.$1)),
                    selected: _group == g.$1,
                    onSelected: (on) => setState(() => _group = on ? g.$1 : null),
                  ),
              ],
            ),
            if (categories.isNotEmpty) ...[
              const SizedBox(height: AppSpacing.md),
              DropdownButtonFormField<int?>(
                key: const Key('trips-service'),
                initialValue: _service,
                decoration: InputDecoration(labelText: l10n.tripsFilterService),
                items: [
                  DropdownMenuItem<int?>(
                    value: null,
                    child: Text(l10n.tripsFilterAllServices),
                  ),
                  for (final c in categories)
                    DropdownMenuItem<int?>(
                      value: c.id,
                      child: Text(c.name.resolve(language)),
                    ),
                ],
                onChanged: (v) => setState(() => _service = v),
              ),
            ],
            const SizedBox(height: AppSpacing.md),
            Row(
              children: [
                Expanded(
                  child: OutlinedButton(
                    key: const Key('trips-from'),
                    onPressed: () => _pickDate(true),
                    child: Text(
                      _from == null
                          ? l10n.tripsFilterFrom
                          : MaterialLocalizations.of(context).formatShortDate(_from!),
                    ),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: OutlinedButton(
                    key: const Key('trips-to'),
                    onPressed: () => _pickDate(false),
                    child: Text(
                      _to == null
                          ? l10n.tripsFilterTo
                          : MaterialLocalizations.of(context).formatShortDate(_to!),
                    ),
                  ),
                ),
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              key: const Key('trips-apply'),
              label: l10n.tripsApply,
              onPressed: () {
                final statuses = _group == null
                    ? const <BookingStatus>[]
                    : _statusGroups.firstWhere((g) => g.$1 == _group).$2;
                ref.read(tripFilterProvider.notifier).set(
                  TripFilter(
                    serviceCategoryId: _service,
                    statuses: statuses,
                    from: _from,
                    to: _to,
                  ),
                );
                Navigator.of(context).pop();
              },
            ),
            const SizedBox(height: AppSpacing.sm),
            AppButton(
              label: l10n.tripsClearFilters,
              secondary: true,
              onPressed: () {
                ref.read(tripFilterProvider.notifier).clear();
                Navigator.of(context).pop();
              },
            ),
          ],
        ),
      ),
    );
  }
}
