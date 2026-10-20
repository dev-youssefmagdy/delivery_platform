import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../domain/place_selection.dart';
import '../providers/map_providers.dart';
import '../screens/map_screen_args.dart';
import 'booking_map.dart';
import 'location_rationale.dart';

/// Home map + pickup/destination fields (SRS §6.3): current location with a
/// rationale-first permission flow and a manual fallback, swap, and the
/// outside-area / area-check-failed states.
class TripLocationPanel extends ConsumerStatefulWidget {
  const TripLocationPanel({super.key, this.mapHeight = 240});

  final double mapHeight;

  @override
  ConsumerState<TripLocationPanel> createState() => _TripLocationPanelState();
}

class _TripLocationPanelState extends ConsumerState<TripLocationPanel> {
  @override
  void initState() {
    super.initState();
    // Reads the permission silently; the OS prompt only follows the rationale.
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (mounted) ref.read(userLocationProvider.notifier).refresh();
    });
  }

  Future<void> _enable() => ref
      .read(userLocationProvider.notifier)
      .enable(showRationale: () => showLocationRationale(context));

  Future<void> _pick(PlaceSearchMode mode) async {
    final endpoints = ref.read(tripEndpointsProvider);
    final picked = await context.push<PlaceSelection>(
      Routes.placeSearch,
      extra: PlaceSearchArgs(
        mode: mode,
        near: endpoints.pickup?.point ?? ref.read(userLocationProvider).point,
      ),
    );
    if (picked == null) return;
    final notifier = ref.read(tripEndpointsProvider.notifier);
    mode == PlaceSearchMode.pickup
        ? notifier.setPickup(picked)
        : notifier.setDestination(picked);
  }

  String _describe(AppLocalizations l10n, PlaceSelection? place, String hint) {
    if (place == null) return hint;
    if (place.isCurrentLocation) return l10n.mapCurrentLocation;
    return place.title ?? l10n.mapPinnedLocation;
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final endpoints = ref.watch(tripEndpointsProvider);
    final location = ref.watch(userLocationProvider);

    // The device position becomes the pickup until the user picks one.
    ref.listen(userLocationProvider.select((s) => s.point), (_, point) {
      final current = ref.read(tripEndpointsProvider).pickup;
      if (point != null && (current == null || current.isCurrentLocation)) {
        ref
            .read(tripEndpointsProvider.notifier)
            .setPickup(PlaceSelection(point: point, isCurrentLocation: true));
      }
    });

    return Column(
      crossAxisAlignment: CrossAxisAlignment.stretch,
      children: [
        SizedBox(
          height: widget.mapHeight,
          child: Stack(
            children: [
              Positioned.fill(
                child: BookingMap(
                  pickup: endpoints.pickup?.point,
                  destination: endpoints.destination?.point,
                  user: location.point,
                  semanticLabel: l10n.mapPickupLabel,
                ),
              ),
              PositionedDirectional(
                end: AppSpacing.sm,
                bottom: AppSpacing.lg,
                child: FloatingActionButton.small(
                  key: const Key('map-my-location'),
                  heroTag: 'map-my-location',
                  tooltip: l10n.mapMyLocation,
                  onPressed: location.granted
                      ? ref.read(userLocationProvider.notifier).locate
                      : _enable,
                  child: location.busy
                      ? const SizedBox.square(
                          dimension: 18,
                          child: CircularProgressIndicator(strokeWidth: 2),
                        )
                      : const Icon(Icons.my_location),
                ),
              ),
            ],
          ),
        ),
        _PermissionBanner(
          location: location,
          hasPickup: endpoints.pickup != null,
          onEnable: _enable,
          onChooseManually: () => _pick(PlaceSearchMode.pickup),
        ),
        Padding(
          padding: const EdgeInsetsDirectional.symmetric(
            horizontal: AppSpacing.md,
            vertical: AppSpacing.sm,
          ),
          child: Card(
            margin: EdgeInsets.zero,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.sm),
              child: Row(
                children: [
                  Expanded(
                    child: Column(
                      children: [
                        _EndpointField(
                          fieldKey: const Key('pickup-field'),
                          icon: Icons.trip_origin,
                          label: l10n.mapPickupLabel,
                          value: _describe(
                            l10n,
                            endpoints.pickup,
                            l10n.mapPickupHint,
                          ),
                          filled: endpoints.pickup != null,
                          note:
                              endpoints.pickup != null &&
                                  !endpoints.pickup!.hasExactAddress &&
                                  !endpoints.pickup!.isCurrentLocation &&
                                  endpoints.pickup!.address != null
                              ? l10n.mapApproximate
                              : null,
                          onTap: () => _pick(PlaceSearchMode.pickup),
                        ),
                        const Divider(height: 1),
                        _EndpointField(
                          fieldKey: const Key('destination-field'),
                          icon: Icons.location_on_outlined,
                          label: l10n.mapDestinationLabel,
                          value: _describe(
                            l10n,
                            endpoints.destination,
                            l10n.mapDestinationHint,
                          ),
                          filled: endpoints.destination != null,
                          onTap: () => _pick(PlaceSearchMode.destination),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    key: const Key('swap-endpoints'),
                    tooltip: l10n.mapSwap,
                    icon: const Icon(Icons.swap_vert),
                    onPressed: endpoints.canSwap
                        ? ref.read(tripEndpointsProvider.notifier).swap
                        : null,
                  ),
                ],
              ),
            ),
          ),
        ),
        const _AreaBanner(),
      ],
    );
  }
}

class _EndpointField extends StatelessWidget {
  const _EndpointField({
    required this.fieldKey,
    required this.icon,
    required this.label,
    required this.value,
    required this.filled,
    required this.onTap,
    this.note,
  });

  final Key fieldKey;
  final IconData icon;
  final String label;
  final String value;
  final bool filled;
  final String? note;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Semantics(
      button: true,
      label: '$label: $value',
      excludeSemantics: true,
      child: InkWell(
        key: fieldKey,
        onTap: onTap,
        child: ConstrainedBox(
          constraints: BoxConstraints(
            minHeight: context.density.minTouchTarget,
          ),
          child: Padding(
            padding: const EdgeInsetsDirectional.symmetric(
              horizontal: AppSpacing.sm,
              vertical: AppSpacing.sm,
            ),
            child: Row(
              children: [
                Icon(icon, size: 20),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        value,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: filled
                            ? theme.textTheme.bodyLarge
                            : theme.textTheme.bodyLarge?.copyWith(
                                color: theme.hintColor,
                              ),
                      ),
                      if (note != null)
                        Text(note!, style: theme.textTheme.bodySmall),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

/// Why there is no device location, and what the user can do about it.
class _PermissionBanner extends ConsumerWidget {
  const _PermissionBanner({
    required this.location,
    required this.hasPickup,
    required this.onEnable,
    required this.onChooseManually,
  });

  final UserLocationState location;
  final bool hasPickup;
  final Future<void> Function() onEnable;
  final VoidCallback onChooseManually;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    if (location.busy) {
      return Padding(
        key: const Key('location-busy'),
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        child: Row(
          children: [
            const SizedBox.square(
              dimension: 18,
              child: CircularProgressIndicator(strokeWidth: 2),
            ),
            const SizedBox(width: AppSpacing.sm),
            Text(l10n.mapLocating),
          ],
        ),
      );
    }
    if (location.granted && !location.failed) return const SizedBox.shrink();
    // A manually chosen pickup needs no nagging.
    if (hasPickup) return const SizedBox.shrink();

    final permission = location.permission;
    if (permission == null) return const SizedBox.shrink();
    final (message, actionLabel, action) = switch (permission) {
      LocationPermissionState.denied => (
        l10n.mapPermissionDenied,
        l10n.mapRationaleAllow,
        () => onEnable(),
      ),
      LocationPermissionState.deniedForever => (
        l10n.mapPermissionBlocked,
        l10n.mapOpenSettings,
        () => ref.read(userLocationProvider.notifier).openSettings(),
      ),
      LocationPermissionState.serviceDisabled => (
        l10n.mapServiceDisabled,
        l10n.mapOpenSettings,
        () => ref.read(userLocationProvider.notifier).openSettings(),
      ),
      LocationPermissionState.granted => (
        l10n.mapLocateFailed,
        l10n.actionRetry,
        () => ref.read(userLocationProvider.notifier).locate(),
      ),
    };
    return Padding(
      key: const Key('location-banner'),
      padding: const EdgeInsetsDirectional.fromSTEB(
        AppSpacing.md,
        AppSpacing.sm,
        AppSpacing.md,
        0,
      ),
      child: Card(
        color: Theme.of(context).colorScheme.surfaceContainerHighest,
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              Text(message),
              const SizedBox(height: AppSpacing.sm),
              Wrap(
                spacing: AppSpacing.sm,
                children: [
                  FilledButton.tonal(
                    key: const Key('location-action'),
                    onPressed: action,
                    child: Text(actionLabel),
                  ),
                  OutlinedButton(
                    key: const Key('location-manual'),
                    onPressed: onChooseManually,
                    child: Text(l10n.mapChoosePickup),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

/// Service area of the pickup: outside-area, check failed, restricted pickup.
class _AreaBanner extends ConsumerWidget {
  const _AreaBanner();

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    final theme = Theme.of(context);
    final area = ref.watch(serviceAreaProvider);

    Widget box(Key key, Color color, Widget child) => Padding(
      key: key,
      padding: const EdgeInsetsDirectional.fromSTEB(
        AppSpacing.md,
        0,
        AppSpacing.md,
        AppSpacing.sm,
      ),
      child: Card(
        color: color,
        margin: EdgeInsets.zero,
        child: Padding(
          padding: const EdgeInsetsDirectional.all(AppSpacing.md),
          child: child,
        ),
      ),
    );

    return area.when(
      skipLoadingOnReload: true,
      loading: () => const SizedBox.shrink(),
      error: (error, _) => box(
        const Key('area-error'),
        theme.colorScheme.errorContainer,
        Row(
          children: [
            Expanded(
              child: Text(
                error is ApiException && error.isOffline
                    ? l10n.errorOfflineBody
                    : l10n.mapAreaCheckFailed,
              ),
            ),
            TextButton(
              onPressed: () => ref.invalidate(serviceAreaProvider),
              child: Text(l10n.actionRetry),
            ),
          ],
        ),
      ),
      data: (info) {
        if (info == null) return const SizedBox.shrink();
        if (!info.inServiceArea) {
          return box(
            const Key('area-outside'),
            theme.colorScheme.errorContainer,
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(l10n.mapOutsideTitle, style: theme.textTheme.titleSmall),
                Text(l10n.mapOutsideBody),
                const SizedBox(height: AppSpacing.sm),
                OutlinedButton(
                  key: const Key('area-change-pickup'),
                  onPressed: () => context
                      .push<PlaceSelection>(
                        Routes.placeSearch,
                        extra: const PlaceSearchArgs(
                          mode: PlaceSearchMode.pickup,
                        ),
                      )
                      .then((picked) {
                        if (picked != null) {
                          ref
                              .read(tripEndpointsProvider.notifier)
                              .setPickup(picked);
                        }
                      }),
                  child: Text(l10n.mapChoosePickup),
                ),
              ],
            ),
          );
        }
        if (info.restrictedPickup) {
          return box(
            const Key('area-restricted'),
            theme.colorScheme.secondaryContainer,
            Text(l10n.mapRestrictedPickup),
          );
        }
        return const SizedBox.shrink();
      },
    );
  }
}
