import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../provider_onboarding/presentation/widgets/onboarding_labels.dart';
import '../providers/availability_providers.dart';
import 'location_rationale_dialog.dart';
import 'realtime_indicator.dart';

/// The driver home's availability: the online/offline switch with the reasons
/// that block it, the tracking state and the live connection indicator.
class AvailabilityCard extends ConsumerStatefulWidget {
  const AvailabilityCard({super.key, this.vehicles = const []});

  /// Approved vehicles the driver may choose among (a picker shows from two).
  final List<ProviderVehicleInfo> vehicles;

  @override
  ConsumerState<AvailabilityCard> createState() => _AvailabilityCardState();
}

class _AvailabilityCardState extends ConsumerState<AvailabilityCard> {
  bool _busy = false;
  String? _vehicleId;
  GoOnlineFailure? _failure;
  Object? _error;

  @override
  void initState() {
    super.initState();
    // Tracking and the live connection follow the availability for as long as
    // the app is signed in; reading them here starts both.
    ref.read(trackingControllerProvider);
    ref.read(realtimeControllerProvider);
  }

  Future<T> _working<T>(Future<T> Function() action) async {
    setState(() => _busy = true);
    try {
      return await action();
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _toggle(bool wantOnline) async {
    if (_busy) return;
    setState(() {
      _failure = null;
      _error = null;
    });
    final notifier = ref.read(availabilityProvider.notifier);
    try {
      if (wantOnline) {
        // The rationale dialog is part of going online but is not "working":
        // the spinner only runs while the network is.
        final failure = await notifier.goOnline(
          vehicleId: _vehicleId,
          showRationale: () => showLocationRationale(context),
        );
        if (mounted) setState(() => _failure = failure);
      } else {
        final outcome = await _working(notifier.goOffline);
        if (outcome == OfflineOutcome.needsConfirmation &&
            mounted &&
            await confirmOfflineWithJob(context)) {
          await _working(() => notifier.goOffline(confirm: true));
        }
      }
    } on Object catch (e) {
      if (mounted) setState(() => _error = e);
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final snapshot = ref.watch(availabilityProvider);

    return Card(
      key: const Key('availability-card'),
      child: Padding(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: snapshot.when(
          skipLoadingOnReload: true,
          loading: () => LoadingView(semanticLabel: l10n.loadingLabel),
          error: (e, _) => ErrorState(
            offline: e is ApiException && e.isOffline,
            title: e is ApiException && e.isOffline
                ? l10n.errorOfflineTitle
                : l10n.errorGenericTitle,
            message: onboardingErrorText(l10n, e),
            retryLabel: l10n.actionRetry,
            onRetry: () => ref.read(availabilityProvider.notifier).refresh(),
          ),
          data: (data) => _content(context, l10n, data),
        ),
      ),
    );
  }

  Widget _content(
    BuildContext context,
    AppLocalizations l10n,
    AvailabilitySnapshot data,
  ) {
    final theme = Theme.of(context);
    final availability = data.availability;
    final online = availability.online;
    final reasons = _blockingReasons(data.eligibility, online);
    final canSwitch = !_busy && (online || reasons.isEmpty);
    final vehicleId = _vehicleId ?? availability.vehicleId;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Expanded(
              child: Text(l10n.avTitle, style: theme.textTheme.titleMedium),
            ),
            const Flexible(child: RealtimeIndicator()),
          ],
        ),
        const SizedBox(height: AppSpacing.sm),
        SwitchListTile.adaptive(
          key: const Key('availability-switch'),
          contentPadding: EdgeInsets.zero,
          value: online,
          onChanged: canSwitch ? _toggle : null,
          title: Text(
            online ? l10n.avOnlineTitle : l10n.avOfflineTitle,
            key: const Key('availability-title'),
            style: theme.textTheme.titleLarge,
          ),
          subtitle: Text(online ? l10n.avOnlineBody : l10n.avOfflineBody),
          secondary: _busy
              ? const SizedBox(
                  width: 24,
                  height: 24,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              : null,
        ),
        if (!online && widget.vehicles.length > 1) ...[
          const SizedBox(height: AppSpacing.sm),
          DropdownButtonFormField<String>(
            key: const Key('availability-vehicle'),
            initialValue: widget.vehicles.any((v) => v.id == vehicleId)
                ? vehicleId
                : null,
            decoration: InputDecoration(labelText: l10n.avVehicleLabel),
            items: [
              for (final v in widget.vehicles)
                DropdownMenuItem(
                  value: v.id,
                  child: Text('${v.make} ${v.model} · ${v.plate}'),
                ),
            ],
            onChanged: (id) => setState(() => _vehicleId = id),
          ),
        ],
        if (reasons.isNotEmpty) ...[
          const SizedBox(height: AppSpacing.sm),
          _ReasonsList(reasons: reasons),
        ],
        if (!online && availability.lastOfflineReason != null) ...[
          const SizedBox(height: AppSpacing.sm),
          _offlineReasonNote(l10n, availability.lastOfflineReason!),
        ],
        if (availability.hasActiveJob) ...[
          const SizedBox(height: AppSpacing.sm),
          StatusChip(
            key: const Key('availability-active-job'),
            label: l10n.avActiveJobBanner,
            tone: ChipTone.info,
          ),
        ],
        ..._failureNotes(l10n),
        const SizedBox(height: AppSpacing.sm),
        _TrackingLine(plan: availability.tracking),
      ],
    );
  }

  /// The reasons to show. A stale or missing location is the normal state of
  /// an offline driver (the position is sent when going online), so it is not
  /// listed unless the server just refused going online.
  List<EligibilityReason> _blockingReasons(
    EligibilityInfo eligibility,
    bool online,
  ) {
    if (online) return const [];
    final refused = _failure is NotEligible;
    return [
      for (final r in eligibility.reasons)
        if (refused || r != EligibilityReason.locationStale) r,
    ];
  }

  Widget _offlineReasonNote(AppLocalizations l10n, OfflineReason reason) {
    final text = switch (reason) {
      OfflineReason.staleLocation => l10n.avOfflineStale,
      OfflineReason.noLongerEligible => l10n.avOfflineIneligible,
      OfflineReason.accountChanged => l10n.avOfflineAccount,
      _ => null,
    };
    return text == null
        ? const SizedBox.shrink()
        : Text(
            text,
            key: const Key('availability-offline-reason'),
            style: Theme.of(context).textTheme.bodySmall,
          );
  }

  List<Widget> _failureNotes(AppLocalizations l10n) {
    final tracking = ref.watch(trackingControllerProvider);
    final widgets = <Widget>[];

    final permission = switch (_failure) {
      PermissionRequired(:final state) => state,
      _ =>
        tracking.permission == LocationPermissionState.granted
            ? null
            : tracking.permission,
    };
    if (permission != null) {
      widgets.add(const SizedBox(height: AppSpacing.sm));
      widgets.add(_PermissionNote(state: permission));
    }

    final message = switch (_failure) {
      PositionUnavailable() => l10n.avPositionUnavailable,
      RequestFailed(:final error) => _requestFailureText(l10n, error),
      _ => _error == null ? null : onboardingErrorText(l10n, _error!),
    };
    if (message != null) {
      widgets.add(const SizedBox(height: AppSpacing.sm));
      widgets.add(
        Text(
          message,
          key: const Key('availability-error'),
          style: TextStyle(color: Theme.of(context).colorScheme.error),
        ),
      );
    }
    return widgets;
  }

  String _requestFailureText(AppLocalizations l10n, Object error) {
    if (error is ApiException) {
      switch (error.code) {
        case 'outside_service_area':
          return l10n.avOutsideArea;
        case 'vehicle_not_usable':
          return l10n.avVehicleNotUsable;
      }
    }
    return onboardingErrorText(l10n, error);
  }
}

class _ReasonsList extends StatelessWidget {
  const _ReasonsList({required this.reasons});

  final List<EligibilityReason> reasons;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Column(
      key: const Key('availability-reasons'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          l10n.avReasonsTitle,
          style: Theme.of(context).textTheme.titleSmall,
        ),
        for (final reason in reasons)
          Padding(
            padding: const EdgeInsetsDirectional.only(top: AppSpacing.xs),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Icon(
                  Icons.block,
                  size: 18,
                  color: Theme.of(context).colorScheme.error,
                ),
                const SizedBox(width: AppSpacing.xs),
                Expanded(child: Text(eligibilityReasonLabel(l10n, reason))),
              ],
            ),
          ),
      ],
    );
  }
}

class _PermissionNote extends ConsumerWidget {
  const _PermissionNote({required this.state});

  final LocationPermissionState state;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final text = switch (state) {
      LocationPermissionState.deniedForever => l10n.avPermissionForever,
      LocationPermissionState.serviceDisabled => l10n.avServiceDisabled,
      _ => l10n.avPermissionDenied,
    };
    final location = ref.read(locationServiceProvider);
    final canOpenSettings = state != LocationPermissionState.denied;
    return Column(
      key: const Key('availability-permission'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(text),
        if (canOpenSettings)
          TextButton(
            key: const Key('availability-open-settings'),
            onPressed: () => state == LocationPermissionState.serviceDisabled
                ? location.openLocationSettings()
                : location.openAppSettings(),
            child: Text(l10n.avOpenSettings),
          ),
      ],
    );
  }
}

class _TrackingLine extends ConsumerWidget {
  const _TrackingLine({required this.plan});

  final TrackingPlan plan;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final status = ref.watch(trackingControllerProvider);
    final theme = Theme.of(context);
    final label = switch (plan.state) {
      TrackingState.off => l10n.avTrackingOff,
      TrackingState.idle => l10n.avTrackingIdle,
      TrackingState.toPickup => l10n.avTrackingToPickup,
      TrackingState.active => l10n.avTrackingActive,
    };
    return Column(
      key: Key('tracking-${plan.state.value}'),
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Icon(
              plan.isTracking ? Icons.my_location : Icons.location_disabled,
              size: 18,
            ),
            const SizedBox(width: AppSpacing.xs),
            Expanded(child: Text(label, style: theme.textTheme.bodyMedium)),
          ],
        ),
        if (plan.isTracking)
          Text(
            l10n.avTrackingEvery(plan.intervalSeconds),
            style: theme.textTheme.bodySmall,
          ),
        if (status.lastUploadFailed)
          Text(
            l10n.avUploadFailed,
            key: const Key('tracking-upload-failed'),
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.error,
            ),
          ),
        if (plan.isTracking)
          Text(l10n.avNoLocationNote, style: theme.textTheme.bodySmall),
      ],
    );
  }
}
