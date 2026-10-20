import 'package:flutter/material.dart';
import 'package:flutter_map/flutter_map.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:latlong2/latlong.dart';

import '../../../../core/providers.dart';
import '../providers/map_providers.dart';

/// Read-only map of the trip being composed: pickup, destination and the
/// device position. Tiles come from [mapTileProviderProvider] and the URL from
/// `MAP_TILE_URL` (Env); the attribution is always shown.
class BookingMap extends ConsumerStatefulWidget {
  const BookingMap({
    super.key,
    this.pickup,
    this.destination,
    this.user,
    this.semanticLabel,
    this.onTap,
  });

  final LatLng? pickup;
  final LatLng? destination;
  final LatLng? user;
  final String? semanticLabel;
  final void Function(LatLng point)? onTap;

  @override
  ConsumerState<BookingMap> createState() => _BookingMapState();
}

class _BookingMapState extends ConsumerState<BookingMap> {
  final _controller = MapController();
  bool _ready = false;

  LatLng get _focus =>
      widget.pickup ?? widget.user ?? widget.destination ?? defaultMapCenter;

  @override
  void didUpdateWidget(BookingMap old) {
    super.didUpdateWidget(old);
    if (!_ready) return;
    if (old.pickup != widget.pickup && widget.pickup != null) {
      _fit();
    } else if (old.destination != widget.destination &&
        widget.destination != null) {
      _fit();
    } else if (old.pickup == null &&
        old.user != widget.user &&
        widget.user != null) {
      _controller.move(widget.user!, 15);
    }
  }

  void _fit() {
    final pickup = widget.pickup;
    final destination = widget.destination;
    if (pickup != null && destination != null) {
      _controller.fitCamera(
        CameraFit.coordinates(
          coordinates: [pickup, destination],
          padding: const EdgeInsets.all(48),
          maxZoom: 16,
        ),
      );
    } else {
      _controller.move(_focus, 15);
    }
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final env = ref.watch(envProvider);
    final scheme = Theme.of(context).colorScheme;
    return Semantics(
      container: true,
      label: widget.semanticLabel,
      child: FlutterMap(
        mapController: _controller,
        options: MapOptions(
          initialCenter: _focus,
          initialZoom: widget.pickup == null && widget.user == null ? 11 : 15,
          interactionOptions: const InteractionOptions(
            flags: InteractiveFlag.all & ~InteractiveFlag.rotate,
          ),
          onMapReady: () => _ready = true,
          onTap: widget.onTap == null
              ? null
              : (_, point) => widget.onTap!(point),
        ),
        children: [
          TileLayer(
            urlTemplate: env.mapTileUrl,
            userAgentPackageName: 'com.example.customer_app',
            tileProvider: ref.watch(mapTileProviderProvider),
          ),
          MarkerLayer(
            markers: [
              if (widget.user != null)
                Marker(
                  point: widget.user!,
                  width: 22,
                  height: 22,
                  child: DecoratedBox(
                    key: const Key('map-user-marker'),
                    decoration: BoxDecoration(
                      color: scheme.primary,
                      shape: BoxShape.circle,
                      border: Border.all(color: scheme.onPrimary, width: 3),
                    ),
                  ),
                ),
              if (widget.destination != null)
                Marker(
                  point: widget.destination!,
                  width: 40,
                  height: 40,
                  alignment: Alignment.topCenter,
                  child: Icon(
                    Icons.location_on,
                    key: const Key('map-destination-marker'),
                    color: scheme.error,
                    size: 40,
                  ),
                ),
              if (widget.pickup != null)
                Marker(
                  point: widget.pickup!,
                  width: 40,
                  height: 40,
                  alignment: Alignment.topCenter,
                  child: Icon(
                    Icons.location_on,
                    key: const Key('map-pickup-marker'),
                    color: scheme.primary,
                    size: 40,
                  ),
                ),
            ],
          ),
          RichAttributionWidget(
            attributions: [TextSourceAttribution(env.mapAttribution)],
          ),
        ],
      ),
    );
  }
}
