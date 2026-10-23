import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:latlong2/latlong.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../core/providers.dart';
import '../../domain/place_selection.dart';
import '../providers/map_providers.dart';
import '../widgets/geo_error_text.dart';
import 'map_screen_args.dart';

/// Move the map under a fixed pin to set an exact point. After the map settles
/// the address is looked up (and the service area checked); a failed lookup
/// never blocks confirming the point, and an approximate answer is not shown as
/// an exact address.
class PinAdjustScreen extends ConsumerStatefulWidget {
  const PinAdjustScreen({super.key, required this.args});

  final PinAdjustArgs args;

  /// Pause after the last map movement before the lookup starts.
  static const settle = Duration(milliseconds: 600);

  @override
  ConsumerState<PinAdjustScreen> createState() => _PinAdjustScreenState();
}

class _PinAdjustScreenState extends ConsumerState<PinAdjustScreen> {
  final _controller = MapController();
  Timer? _timer;
  int _sequence = 0;
  late LatLng _center = widget.args.initial;
  bool _resolving = true;
  PlaceResult? _place;
  Object? _lookupError;
  ServiceAreaInfo? _area;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _resolve());
  }

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onMoved(MapCamera camera, bool hasGesture) {
    _center = camera.center;
    if (!hasGesture) return;
    _timer?.cancel();
    setState(() => _resolving = true);
    _timer = Timer(PinAdjustScreen.settle, _resolve);
  }

  Future<void> _resolve() async {
    final mine = ++_sequence;
    final point = _center;
    final repo = ref.read(geoRepositoryProvider);
    PlaceResult? place;
    Object? error;
    ServiceAreaInfo? area;
    await Future.wait<void>([
      () async {
        try {
          place = await repo.reverse(point);
        } on Object catch (e) {
          error = e;
        }
      }(),
      if (widget.args.checkServiceArea)
        () async {
          try {
            area = await repo.resolveArea(point);
          } on Object {
            area = null;
          }
        }(),
    ]);
    if (!mounted || mine != _sequence) return;
    setState(() {
      _place = place;
      _lookupError = error;
      _area = area;
      _resolving = false;
    });
  }

  void _confirm(AppLocalizations l10n) {
    final place = _place;
    context.pop(
      PlaceSelection(
        point: _center,
        title: place?.title ?? l10n.mapPinnedLocation,
        address: place?.address,
        precision: place?.precision ?? LocationPrecision.unknown,
        // The pinned point is what the user chose, not the geocoder's answer.
        place: place == null ? null : _asPinned(place),
      ),
    );
  }

  PlaceResult _asPinned(PlaceResult place) => PlaceResult(
    id: place.id,
    title: place.title,
    address: place.address,
    point: _center,
    precision: place.precision,
    provider: place.provider,
  );

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final env = ref.watch(envProvider);
    final theme = Theme.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.pinInstruction)),
      body: Column(
        children: [
          Expanded(
            child: Stack(
              alignment: Alignment.center,
              children: [
                Semantics(
                  container: true,
                  label: l10n.pinInstruction,
                  child: FlutterMap(
                    mapController: _controller,
                    options: MapOptions(
                      initialCenter: widget.args.initial,
                      initialZoom: 16,
                      interactionOptions: const InteractionOptions(
                        flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
                      ),
                      onPositionChanged: _onMoved,
                    ),
                    children: [
                      TileLayer(
                        urlTemplate: env.mapTileUrl,
                        userAgentPackageName: 'com.example.customer_app',
                        tileProvider: ref.watch(mapTileProviderProvider),
                      ),
                      RichAttributionWidget(
                        attributions: [
                          TextSourceAttribution(env.mapAttribution),
                        ],
                      ),
                    ],
                  ),
                ),
                // The pin's tip is the map center.
                IgnorePointer(
                  child: Padding(
                    padding: const EdgeInsets.only(bottom: 40),
                    child: Icon(
                      Icons.location_on,
                      key: const Key('pin-marker'),
                      size: 48,
                      color: theme.colorScheme.primary,
                    ),
                  ),
                ),
              ],
            ),
          ),
          SafeArea(
            top: false,
            child: Padding(
              padding: const EdgeInsetsDirectional.all(AppSpacing.md),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  _status(l10n, theme),
                  const SizedBox(height: AppSpacing.md),
                  AppButton(
                    key: const Key('pin-confirm'),
                    label: l10n.pinConfirm,
                    onPressed: () => _confirm(l10n),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _status(AppLocalizations l10n, ThemeData theme) {
    if (_resolving) {
      return Row(
        key: const Key('pin-resolving'),
        children: [
          const SizedBox.square(
            dimension: 18,
            child: CircularProgressIndicator(strokeWidth: 2),
          ),
          const SizedBox(width: AppSpacing.sm),
          Text(l10n.pinResolving),
        ],
      );
    }
    final lines = <Widget>[];
    final place = _place;
    if (_lookupError != null) {
      lines.add(
        Text(
          isMapsOutage(_lookupError!)
              ? l10n.pinLookupFailed
              : geoErrorText(l10n, _lookupError!),
          key: const Key('pin-lookup-failed'),
        ),
      );
    } else if (place == null) {
      lines.add(Text(l10n.pinNoAddress, key: const Key('pin-no-address')));
    } else {
      lines.add(Text(place.title, style: theme.textTheme.titleMedium));
      if (place.address.isNotEmpty && place.address != place.title) {
        lines.add(Text(place.address));
      }
      if (!place.isExact) {
        lines.add(
          Text(
            l10n.mapApproximate,
            key: const Key('pin-approximate'),
            style: theme.textTheme.bodySmall,
          ),
        );
      }
    }
    if (widget.args.checkServiceArea &&
        _area != null &&
        !_area!.inServiceArea) {
      lines.add(
        Text(
          l10n.pinOutside,
          key: const Key('pin-outside'),
          style: TextStyle(color: theme.colorScheme.error),
        ),
      );
    }
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: lines,
    );
  }
}
