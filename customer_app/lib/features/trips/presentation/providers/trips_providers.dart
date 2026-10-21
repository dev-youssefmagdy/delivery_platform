import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../booking/domain/entities/booking_entities.dart';
import '../../../booking/presentation/providers/booking_providers.dart';

Duration? _noAutoRetry(int retryCount, Object error) => null;

class TripsState {
  const TripsState({
    required this.items,
    required this.filter,
    required this.page,
    required this.hasMore,
    this.loadingMore = false,
    this.loadMoreFailed = false,
  });

  final List<BookingSummary> items;
  final TripFilter filter;
  final int page;
  final bool hasMore;
  final bool loadingMore;
  final bool loadMoreFailed;

  TripsState copyWith({
    List<BookingSummary>? items,
    int? page,
    bool? hasMore,
    bool? loadingMore,
    bool? loadMoreFailed,
  }) => TripsState(
    items: items ?? this.items,
    filter: filter,
    page: page ?? this.page,
    hasMore: hasMore ?? this.hasMore,
    loadingMore: loadingMore ?? this.loadingMore,
    loadMoreFailed: loadMoreFailed ?? this.loadMoreFailed,
  );
}

class TripFilterNotifier extends Notifier<TripFilter> {
  @override
  TripFilter build() => const TripFilter();

  void set(TripFilter filter) => state = filter;

  void clear() => state = const TripFilter();
}

final tripFilterProvider = NotifierProvider<TripFilterNotifier, TripFilter>(
  TripFilterNotifier.new,
);

/// History list (My Trips): newest first, paginated, filtered by service,
/// status and date range on the server.
class TripsNotifier extends AsyncNotifier<TripsState> {
  @override
  Future<TripsState> build() async {
    final filter = ref.watch(tripFilterProvider);
    final page = await ref
        .read(bookingRepositoryProvider)
        .list(page: 1, filter: filter);
    return TripsState(
      items: page.items,
      filter: filter,
      page: page.currentPage,
      hasMore: page.hasMore,
    );
  }

  Future<void> loadMore() async {
    final current = state.value;
    if (current == null || !current.hasMore || current.loadingMore) return;
    state = AsyncData(
      current.copyWith(loadingMore: true, loadMoreFailed: false),
    );
    try {
      final page = await ref
          .read(bookingRepositoryProvider)
          .list(page: current.page + 1, filter: current.filter);
      state = AsyncData(
        current.copyWith(
          items: [...current.items, ...page.items],
          page: page.currentPage,
          hasMore: page.hasMore,
          loadingMore: false,
        ),
      );
    } on ApiException {
      state = AsyncData(
        current.copyWith(loadingMore: false, loadMoreFailed: true),
      );
    }
  }
}

final tripsProvider = AsyncNotifierProvider<TripsNotifier, TripsState>(
  TripsNotifier.new,
  retry: _noAutoRetry,
);
