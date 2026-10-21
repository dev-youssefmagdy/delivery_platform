import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../providers/booking_providers.dart';
import '../widgets/closed_view.dart';
import '../widgets/live_trip_view.dart';
import '../widgets/searching_view.dart';

/// One screen for a booking at any stage (also deep links and My Trips
/// details): searching with offers, live trip, or the ended view. What it
/// shows follows the server's snapshot, kept current by realtime hints, a
/// polling fallback and pull to refresh.
class BookingScreen extends ConsumerWidget {
  const BookingScreen({super.key, required this.bookingId});

  final String bookingId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final tracked = ref.watch(bookingTrackerProvider(bookingId));
    final notifier = ref.read(bookingTrackerProvider(bookingId).notifier);
    final code = tracked.value?.booking.code;
    return Scaffold(
      appBar: AppBar(title: Text(code ?? l10n.bkScreenTitle)),
      body: SafeArea(
        child: AsyncStateView<TrackedBooking>(
          value: tracked,
          onRetry: notifier.reload,
          onSignIn: () => context.go(Routes.auth),
          builder: (context, data) => RefreshIndicator(
            onRefresh: notifier.refresh,
            child: _body(data),
          ),
        ),
      ),
    );
  }

  Widget _body(TrackedBooking data) {
    final booking = data.booking;
    if (booking.isSearching) {
      return SearchingView(key: ValueKey(booking.id), booking: booking);
    }
    if (booking.isAssigned) {
      return LiveTripView(tracked: data);
    }
    return ClosedView(booking: booking);
  }
}
