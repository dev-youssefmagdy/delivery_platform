import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../domain/entities/job_entities.dart';

String jobStatusText(AppLocalizations l10n, BookingStatus status) =>
    switch (status) {
      BookingStatus.providerSelected => l10n.jbStatusSelected,
      BookingStatus.providerHeadingToPickup => l10n.jbStatusHeading,
      BookingStatus.providerArrived => l10n.jbStatusArrived,
      BookingStatus.inProgress => l10n.jbStatusInProgress,
      BookingStatus.completed ||
      BookingStatus.completedPendingPayment => l10n.jbStatusCompleted,
      BookingStatus.cancelledByCustomer => l10n.jbStatusCancelledCustomer,
      BookingStatus.cancelledByProvider => l10n.jbStatusCancelledMe,
      BookingStatus.cancelledByOperations => l10n.jbStatusCancelledOps,
      BookingStatus.disputed => l10n.jbStatusDisputed,
      BookingStatus.deliveryAttemptFailed => l10n.jbStatusFailed,
      BookingStatus.searchingOffers ||
      BookingStatus.offersReceived => l10n.jbStatusSearching,
      _ => l10n.jbStatusOther,
    };

ChipTone jobStatusTone(BookingStatus status) => switch (status) {
  BookingStatus.completed || BookingStatus.completedPendingPayment =>
    ChipTone.success,
  BookingStatus.cancelledByCustomer ||
  BookingStatus.cancelledByProvider ||
  BookingStatus.cancelledByOperations ||
  BookingStatus.deliveryAttemptFailed ||
  BookingStatus.disputed => ChipTone.danger,
  BookingStatus.unknown || BookingStatus.draft => ChipTone.neutral,
  _ => ChipTone.info,
};

String offerStateText(AppLocalizations l10n, OfferState state) =>
    switch (state) {
      OfferState.pending => l10n.jbOfferPending,
      OfferState.selected => l10n.jbOfferSelected,
      OfferState.rejected => l10n.jbOfferRejected,
      OfferState.expired => l10n.jbOfferExpired,
      OfferState.invalidated => l10n.jbOfferInvalidated,
      OfferState.withdrawn => l10n.jbOfferWithdrawn,
      OfferState.unknown => l10n.jbOfferUnknown,
    };

ChipTone offerStateTone(OfferState state) => switch (state) {
  OfferState.pending => ChipTone.info,
  OfferState.selected => ChipTone.success,
  OfferState.rejected || OfferState.invalidated => ChipTone.danger,
  OfferState.expired || OfferState.withdrawn => ChipTone.warning,
  OfferState.unknown => ChipTone.neutral,
};

/// Why a closed offer ended, in the driver's words.
String? offerReasonText(AppLocalizations l10n, MyOffer offer) =>
    switch (offer.status) {
      OfferStatus.rejectedByCustomer => l10n.jbReasonRejected,
      OfferStatus.lostBookingAssigned => l10n.jbReasonLost,
      OfferStatus.expired => l10n.jbReasonExpired,
      OfferStatus.invalidatedRequestChanged => l10n.jbReasonRequestChanged,
      OfferStatus.invalidatedProviderUnavailable => l10n.jbReasonUnavailable,
      OfferStatus.withdrawnByProvider => l10n.jbReasonWithdrawn,
      _ => null,
    };

String driverCancelReasonText(AppLocalizations l10n, DriverCancelReason r) =>
    switch (r) {
      DriverCancelReason.customerNoShow => l10n.jbCancelNoShow,
      DriverCancelReason.customerUnreachable => l10n.jbCancelUnreachable,
      DriverCancelReason.vehicleProblem => l10n.jbCancelVehicle,
      DriverCancelReason.emergency => l10n.jbCancelEmergency,
      DriverCancelReason.wrongRequestDetails => l10n.jbCancelWrongDetails,
      DriverCancelReason.unsafePickup => l10n.jbCancelUnsafe,
      DriverCancelReason.other ||
      DriverCancelReason.unknown => l10n.jbCancelOther,
    };

String reportCategoryText(AppLocalizations l10n, DriverReportCategory c) =>
    switch (c) {
      DriverReportCategory.safety => l10n.jbReportSafety,
      DriverReportCategory.behaviour => l10n.jbReportBehaviour,
      DriverReportCategory.fareDispute => l10n.jbReportFare,
      DriverReportCategory.routeOrDelay => l10n.jbReportRoute,
      DriverReportCategory.other => l10n.jbReportOther,
    };

String customerTagText(AppLocalizations l10n, CustomerRatingTag t) =>
    switch (t) {
      CustomerRatingTag.respectful => l10n.jbTagRespectful,
      CustomerRatingTag.clearInstructions => l10n.jbTagClear,
      CustomerRatingTag.readyAtPickup => l10n.jbTagReady,
      CustomerRatingTag.disrespectful => l10n.jbTagDisrespectful,
      CustomerRatingTag.notReadyAtPickup => l10n.jbTagNotReady,
      CustomerRatingTag.late => l10n.jbTagLate,
    };

String paymentMethodText(AppLocalizations l10n, PaymentMethodType m) =>
    switch (m) {
      PaymentMethodType.cash => l10n.jbPayCash,
      _ => l10n.jbPayOther,
    };

/// Maps a failure of the request/offer/trip flows to ARB text by the stable
/// error code; server strings are never shown.
String jobErrorText(AppLocalizations l10n, Object error) {
  if (error is! ApiException) return l10n.errorGenericBody;
  if (error.isOffline) return l10n.errorOfflineBody;
  if (error.isSessionExpired) return l10n.errorSessionExpiredBody;
  switch (error.code) {
    case 'booking_already_assigned':
    case 'booking_not_open_for_offers':
    case 'booking_search_expired':
    case 'offer_expired':
    case 'offer_not_active':
    case 'offer_not_dispatched':
    case 'offer_provider_unavailable':
      return l10n.jbErrLost;
    case 'provider_not_eligible':
      return l10n.jbErrNotEligible;
    case 'offer_vehicle_missing':
      return l10n.jbErrVehicle;
    case 'counter_offers_disabled':
      return l10n.jbErrCounterOff;
    case 'offer_amount_invalid':
      return l10n.jbErrAmountInvalid;
    case 'offer_amount_out_of_bounds':
      return l10n.jbErrAmountBounds;
    case 'offer_currency_mismatch':
      return l10n.jbErrCurrency;
    case 'offer_amount_same_as_fare':
      return l10n.jbErrSameAsFare;
    case 'offer_limit_reached':
      return l10n.jbErrOfferLimit;
    case 'offer_revisions_exceeded':
      return l10n.jbErrRevisions;
    case 'offer_not_withdrawable':
      return l10n.jbErrNotWithdrawable;
    case 'offer_withdraw_first':
      return l10n.jbErrWithdrawFirst;
    case 'offer_not_yours':
      return l10n.jbErrNotYours;
    case 'booking_transition_invalid':
    case 'trip_pin_not_ready':
      return l10n.jbErrTransition;
    case 'booking_pin_required':
      return l10n.jbErrPinRequired;
    case 'booking_proof_required':
      return l10n.jbErrProofRequired;
    case 'trip_pin_locked':
      return l10n.jbErrPinLocked;
    case 'trip_pin_invalid':
      final left = error.meta['attempts_remaining'];
      return left is num
          ? l10n.jbErrPinInvalidLeft(left.toInt())
          : l10n.jbErrPinInvalid;
    case 'trip_pin_not_required':
      return l10n.jbErrPinNotRequired;
    case 'booking_not_cancellable':
      return l10n.jbErrNotCancellable;
    case 'cancellation_reason_not_allowed':
      return l10n.jbErrReasonNotAllowed;
    case 'cancellation_note_required':
      return l10n.jbErrNoteRequired;
    case 'no_show_too_early':
      return l10n.jbErrNoShowEarly;
    case 'report_not_available':
    case 'report_limit_reached':
      return l10n.jbErrReportLimit;
    case 'rating_not_allowed':
    case 'rating_window_closed':
    case 'rating_disabled':
    case 'rating_tag_invalid':
      return l10n.jbErrRatingClosed;
    case 'rating_already_submitted':
      return l10n.jbErrRatingDone;
    case 'too_many_requests':
      return l10n.jbErrRateLimited;
    case 'account_suspended':
    case 'account_restricted':
      return l10n.jbErrAccount;
    case 'idempotency_request_in_progress':
      return l10n.jbErrInProgress;
  }
  if (error.kind == ApiFailureKind.notFound) return l10n.jbErrNotFound;
  return l10n.errorGenericBody;
}

/// True when [error] means the request or offer is gone.
bool errorMeansLost(Object error) =>
    error is ApiException &&
    const {
      'booking_already_assigned',
      'booking_not_open_for_offers',
      'booking_search_expired',
      'offer_expired',
      'offer_not_active',
      'offer_not_dispatched',
      'offer_provider_unavailable',
    }.contains(error.code);

String formatKm(BuildContext context, int meters) {
  final l10n = AppLocalizations.of(context);
  final locale = Localizations.localeOf(context).toString();
  final km = NumberFormat('0.#', locale).format(meters / 1000);
  return l10n.jbKm(km);
}

String formatMinutes(BuildContext context, int seconds) {
  final l10n = AppLocalizations.of(context);
  final minutes = (seconds / 60).ceil();
  return l10n.jbMinutes(minutes < 1 ? 1 : minutes);
}

String formatClock(BuildContext context, Duration remaining) {
  final locale = Localizations.localeOf(context).toString();
  final total = remaining.isNegative ? 0 : remaining.inSeconds;
  final two = NumberFormat('00', locale);
  return '${two.format(total ~/ 60)}:${two.format(total % 60)}';
}

String formatDateTime(BuildContext context, DateTime time) =>
    DateFormat.yMMMd(Localizations.localeOf(context).toString())
        .add_jm()
        .format(time.toLocal());
