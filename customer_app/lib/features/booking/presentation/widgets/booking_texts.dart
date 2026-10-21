import 'dart:async';

import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/booking_entities.dart';

/// Booking status as ARB text (the server label is only a fallback).
String bookingStatusText(
  AppLocalizations l10n,
  BookingStatus status, {
  String? fallback,
}) => switch (status) {
  BookingStatus.draft => l10n.bkStatusDraft,
  BookingStatus.searchingOffers => l10n.bkStatusSearching,
  BookingStatus.offersReceived => l10n.bkStatusOffersReceived,
  BookingStatus.expiredNoProvider => l10n.bkStatusExpired,
  BookingStatus.providerSelected => l10n.bkStatusProviderSelected,
  BookingStatus.providerHeadingToPickup => l10n.bkStatusHeading,
  BookingStatus.providerArrived => l10n.bkStatusArrived,
  BookingStatus.inProgress => l10n.bkStatusInProgress,
  BookingStatus.deliveryAttemptFailed => l10n.bkStatusDeliveryFailed,
  BookingStatus.completedPendingPayment => l10n.bkStatusCompletedPending,
  BookingStatus.completed => l10n.bkStatusCompleted,
  BookingStatus.disputed => l10n.bkStatusDisputed,
  BookingStatus.cancelledByCustomer => l10n.bkStatusCancelledCustomer,
  BookingStatus.cancelledByProvider => l10n.bkStatusCancelledProvider,
  BookingStatus.cancelledByOperations => l10n.bkStatusCancelledOps,
  BookingStatus.unknown => fallback ?? l10n.bkStatusUnknown,
};

ChipTone bookingStatusTone(BookingStatus status) => switch (status) {
  BookingStatus.completed || BookingStatus.completedPendingPayment =>
    ChipTone.success,
  BookingStatus.cancelledByCustomer ||
  BookingStatus.cancelledByProvider ||
  BookingStatus.cancelledByOperations ||
  BookingStatus.expiredNoProvider ||
  BookingStatus.deliveryAttemptFailed ||
  BookingStatus.disputed => ChipTone.danger,
  BookingStatus.searchingOffers ||
  BookingStatus.offersReceived => ChipTone.warning,
  BookingStatus.unknown || BookingStatus.draft => ChipTone.neutral,
  _ => ChipTone.info,
};

String cancelReasonText(AppLocalizations l10n, CancelReason reason) =>
    switch (reason) {
      CancelReason.changedMind => l10n.bkReasonChangedMind,
      CancelReason.wrongPickup => l10n.bkReasonWrongPickup,
      CancelReason.foundAnotherRide => l10n.bkReasonFoundAnother,
      CancelReason.driverTooFar => l10n.bkReasonDriverTooFar,
      CancelReason.driverAskedToCancel => l10n.bkReasonDriverAsked,
      CancelReason.waitedTooLong => l10n.bkReasonWaitedTooLong,
      CancelReason.other || CancelReason.unknown => l10n.bkReasonOther,
    };

String reportCategoryText(AppLocalizations l10n, ReportCategory c) =>
    switch (c) {
      ReportCategory.safety => l10n.bkReportSafety,
      ReportCategory.behaviour => l10n.bkReportBehaviour,
      ReportCategory.fareDispute => l10n.bkReportFareDispute,
      ReportCategory.routeOrDelay => l10n.bkReportRoute,
      ReportCategory.lostItem => l10n.bkReportLostItem,
      ReportCategory.other => l10n.bkReportOther,
    };

String ratingTagText(AppLocalizations l10n, ProviderRatingTag t) =>
    switch (t) {
      ProviderRatingTag.professional => l10n.bkTagProfessional,
      ProviderRatingTag.cleanVehicle => l10n.bkTagCleanVehicle,
      ProviderRatingTag.safeDriving => l10n.bkTagSafeDriving,
      ProviderRatingTag.goodCommunication => l10n.bkTagGoodCommunication,
      ProviderRatingTag.onTime => l10n.bkTagOnTime,
      ProviderRatingTag.carefulWithItems => l10n.bkTagCarefulWithItems,
      ProviderRatingTag.late => l10n.bkTagLate,
      ProviderRatingTag.unsafeDriving => l10n.bkTagUnsafeDriving,
      ProviderRatingTag.rude => l10n.bkTagRude,
      ProviderRatingTag.dirtyVehicle => l10n.bkTagDirtyVehicle,
    };

String priceFactorText(AppLocalizations l10n, PriceFactor f) =>
    switch (f.code) {
      'base' => l10n.bkFactorBase,
      'distance' => l10n.bkFactorDistance,
      'duration' => l10n.bkFactorDuration,
      'stops' => l10n.bkFactorStops,
      'minimum_fare' => l10n.bkFactorMinimum,
      _ => f.label,
    };

/// Maps an API failure of the booking flows to ARB text. The stable error
/// code decides; the server message is only the last fallback.
String bookingErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  switch (error.code) {
    case 'outside_service_area':
    case 'dropoff_outside_service_area':
      return l10n.bkErrOutsideArea;
    case 'category_unavailable':
      return l10n.bkErrCategoryUnavailable;
    case 'dropoff_required':
      return l10n.bkErrDropoffRequired;
    case 'fare_below_minimum':
      return l10n.bkErrFareBelow;
    case 'fare_above_maximum':
      return l10n.bkErrFareAbove;
    case 'capacity_exceeded':
    case 'passengers_required':
    case 'passengers_mismatch':
    case 'luggage_exceeded':
    case 'weight_exceeded':
      return l10n.bkErrCapacity;
    case 'payment_method_unavailable':
      return l10n.bkErrPayment;
    case 'customer_has_active_booking':
      return l10n.bkErrActiveBooking;
    case 'account_restricted':
    case 'account_suspended':
      return l10n.bkErrAccount;
    case 'maps_unavailable':
      return l10n.bkErrMaps;
    case 'booking_not_editable':
      return l10n.bkErrNotEditable;
    case 'booking_request_changed_meanwhile':
    case 'stale_version':
      return l10n.bkErrChangedMeanwhile;
    case 'booking_already_assigned':
      return l10n.bkErrAlreadyAssigned;
    case 'offer_expired':
      return l10n.bkErrOfferExpired;
    case 'offer_not_active':
    case 'offer_provider_unavailable':
      return l10n.bkErrOfferGone;
    case 'booking_search_expired':
    case 'booking_not_open_for_offers':
      return l10n.bkErrSearchEnded;
    case 'booking_not_cancellable':
      return l10n.bkErrNotCancellable;
    case 'cancellation_fee_changed':
      return l10n.bkErrFeeChanged;
    case 'cancellation_reason_not_allowed':
    case 'cancellation_note_required':
      return l10n.bkErrCancelNote;
    case 'share_not_available':
      return l10n.bkErrShare;
    case 'rating_already_submitted':
      return l10n.bkErrRatingDone;
    case 'rating_not_allowed':
    case 'rating_window_closed':
    case 'rating_disabled':
    case 'rating_tag_invalid':
      return l10n.bkErrRatingClosed;
    case 'report_not_available':
      return l10n.bkErrReportClosed;
    case 'report_limit_reached':
      return l10n.bkErrReportLimit;
    case 'too_many_requests':
      return l10n.otpErrorTooManyRequests(error.retryAfterSeconds ?? 60);
    case 'idempotency_request_in_progress':
      return l10n.bkErrInProgress;
  }
  return error.message ?? l10n.errorGenericBody;
}

/// mm:ss for a countdown; hours appear only when needed.
String formatClock(Duration d) {
  final total = d.isNegative ? 0 : d.inSeconds;
  final h = total ~/ 3600;
  final m = (total % 3600) ~/ 60;
  final s = total % 60;
  String two(int v) => v.toString().padLeft(2, '0');
  return h > 0 ? '$h:${two(m)}:${two(s)}' : '${two(m)}:${two(s)}';
}

/// Rebuilds every second with the time left until [until].
class Countdown extends StatefulWidget {
  const Countdown({super.key, required this.until, required this.builder});

  final DateTime until;
  final Widget Function(BuildContext context, Duration remaining) builder;

  @override
  State<Countdown> createState() => _CountdownState();
}

class _CountdownState extends State<Countdown> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final left = widget.until.difference(DateTime.now());
    return widget.builder(context, left.isNegative ? Duration.zero : left);
  }
}

/// Rebuilds every second (relative "last updated" labels, stale warnings).
class SecondTicker extends StatefulWidget {
  const SecondTicker({super.key, required this.builder});

  final Widget Function(BuildContext context, DateTime now) builder;

  @override
  State<SecondTicker> createState() => _SecondTickerState();
}

class _SecondTickerState extends State<SecondTicker> {
  Timer? _timer;

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) => widget.builder(context, DateTime.now());
}

String? placeLabel(PlaceInfo? place) => place?.label;
