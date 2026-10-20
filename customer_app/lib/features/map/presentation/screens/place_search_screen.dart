import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../domain/place_selection.dart';
import '../providers/map_providers.dart';
import '../widgets/geo_error_text.dart';
import 'map_screen_args.dart';

/// Debounced place search (type-ahead) with saved places, "use current
/// location" and "choose on map". Pops a [PlaceSelection].
class PlaceSearchScreen extends ConsumerStatefulWidget {
  const PlaceSearchScreen({super.key, required this.args});

  final PlaceSearchArgs args;

  /// Typing pause before a search is sent.
  static const debounce = Duration(milliseconds: 350);
  static const minChars = 2;

  @override
  ConsumerState<PlaceSearchScreen> createState() => _PlaceSearchScreenState();
}

class _PlaceSearchScreenState extends ConsumerState<PlaceSearchScreen> {
  final _controller = TextEditingController();
  Timer? _timer;
  int _sequence = 0;
  String _query = '';
  bool _loading = false;
  Object? _error;
  List<PlaceResult> _results = const [];

  @override
  void dispose() {
    _timer?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String raw) {
    _timer?.cancel();
    final query = raw.trim();
    setState(() {
      _query = query;
      _error = null;
      if (query.length < PlaceSearchScreen.minChars) {
        _loading = false;
        _results = const [];
        _sequence++;
      }
    });
    if (query.length < PlaceSearchScreen.minChars) return;
    setState(() => _loading = true);
    _timer = Timer(PlaceSearchScreen.debounce, () => _search(query));
  }

  Future<void> _search(String query) async {
    final mine = ++_sequence;
    try {
      final results = await ref
          .read(geoRepositoryProvider)
          .searchPlaces(query, near: widget.args.near);
      if (!mounted || mine != _sequence) return;
      setState(() {
        _results = results;
        _loading = false;
        _error = null;
      });
    } on Object catch (e) {
      if (!mounted || mine != _sequence) return;
      setState(() {
        _error = e;
        _loading = false;
        _results = const [];
      });
    }
  }

  void _done(PlaceSelection selection) => context.pop(selection);

  Future<void> _chooseOnMap() async {
    final start =
        widget.args.near ??
        ref.read(userLocationProvider).point ??
        defaultMapCenter;
    final picked = await context.push<PlaceSelection>(
      Routes.pinAdjust,
      extra: PinAdjustArgs(
        initial: start,
        checkServiceArea: widget.args.mode == PlaceSearchMode.pickup,
      ),
    );
    if (picked != null && mounted) _done(picked);
  }

  String _title(AppLocalizations l10n) => switch (widget.args.mode) {
    PlaceSearchMode.pickup => l10n.searchPickupTitle,
    PlaceSearchMode.destination => l10n.searchDestinationTitle,
    PlaceSearchMode.savedPlace => l10n.searchPlaceTitle,
  };

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(_title(l10n))),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsetsDirectional.all(AppSpacing.md),
            child: TextField(
              key: const Key('place-search-field'),
              controller: _controller,
              autofocus: true,
              textInputAction: TextInputAction.search,
              onChanged: _onChanged,
              decoration: InputDecoration(
                hintText: l10n.searchFieldHint,
                prefixIcon: const Icon(Icons.search),
                suffixIcon: _controller.text.isEmpty
                    ? null
                    : IconButton(
                        tooltip: l10n.actionCancel,
                        icon: const Icon(Icons.close),
                        onPressed: () {
                          _controller.clear();
                          _onChanged('');
                        },
                      ),
              ),
            ),
          ),
          Expanded(child: _body(l10n)),
        ],
      ),
    );
  }

  Widget _body(AppLocalizations l10n) {
    final density = context.density;
    final mapTile = ListTile(
      key: const Key('search-on-map'),
      minTileHeight: density.minTouchTarget,
      leading: const Icon(Icons.pin_drop_outlined),
      title: Text(l10n.searchOnMap),
      onTap: _chooseOnMap,
    );

    if (_query.length < PlaceSearchScreen.minChars) {
      return ListView(children: [..._shortcuts(l10n), mapTile]);
    }
    if (_loading) {
      return LoadingView(semanticLabel: l10n.loadingLabel);
    }
    final error = _error;
    if (error != null) {
      final outage = isMapsOutage(error);
      return ListView(
        children: [
          ErrorState(
            offline: error is ApiException && error.isOffline,
            title: outage
                ? l10n.searchUnavailable
                : error is ApiException && error.isOffline
                ? l10n.errorOfflineTitle
                : l10n.errorGenericTitle,
            message: outage ? null : geoErrorText(l10n, error),
            retryLabel: l10n.actionRetry,
            onRetry: () => _onChanged(_controller.text),
          ),
          mapTile,
        ],
      );
    }
    if (_results.isEmpty) {
      return ListView(
        children: [
          EmptyState(
            title: l10n.searchEmptyTitle,
            message: l10n.searchEmptyBody,
            icon: Icons.search_off,
          ),
          mapTile,
        ],
      );
    }
    return ListView(
      children: [
        _Header(l10n.searchResultsHeader),
        for (final place in _results)
          ListTile(
            key: Key('place-result-${place.id ?? place.title}'),
            minTileHeight: density.minTouchTarget,
            leading: const Icon(Icons.place_outlined),
            title: Text(place.title),
            subtitle: Text(
              place.isExact
                  ? place.address
                  : '${place.address}\n${l10n.mapApproximate}',
            ),
            isThreeLine: !place.isExact,
            onTap: () => _done(PlaceSelection.fromPlace(place)),
          ),
        mapTile,
      ],
    );
  }

  List<Widget> _shortcuts(AppLocalizations l10n) {
    final density = context.density;
    final widgets = <Widget>[];
    final location = ref.watch(userLocationProvider);
    if (widget.args.mode == PlaceSearchMode.pickup && location.point != null) {
      widgets.add(
        ListTile(
          key: const Key('search-use-current'),
          minTileHeight: density.minTouchTarget,
          leading: const Icon(Icons.my_location),
          title: Text(l10n.searchUseCurrent),
          onTap: () => _done(
            PlaceSelection(point: location.point!, isCurrentLocation: true),
          ),
        ),
      );
    }
    if (widget.args.mode != PlaceSearchMode.savedPlace) {
      final saved = ref.watch(savedPlacesProvider).value ?? const [];
      if (saved.isNotEmpty) {
        widgets.add(_Header(l10n.searchSavedHeader));
        for (final place in saved) {
          widgets.add(
            ListTile(
              key: Key('search-saved-${place.id}'),
              minTileHeight: density.minTouchTarget,
              leading: Icon(savedPlaceIcon(place.type)),
              title: Text(place.label),
              subtitle: place.addressLine == null
                  ? null
                  : Text(place.addressLine!),
              onTap: () => _done(PlaceSelection.fromSaved(place)),
            ),
          );
        }
      }
    }
    if (_query.isNotEmpty) {
      widgets.add(
        Padding(
          padding: const EdgeInsetsDirectional.symmetric(
            horizontal: AppSpacing.md,
            vertical: AppSpacing.sm,
          ),
          child: Text(
            l10n.searchMinChars,
            style: Theme.of(context).textTheme.bodySmall,
          ),
        ),
      );
    }
    return widgets;
  }
}

IconData savedPlaceIcon(SavedPlaceType type) => switch (type) {
  SavedPlaceType.home => Icons.home_outlined,
  SavedPlaceType.work => Icons.work_outline,
  _ => Icons.bookmark_border,
};

class _Header extends StatelessWidget {
  const _Header(this.text);

  final String text;

  @override
  Widget build(BuildContext context) => Padding(
    padding: const EdgeInsetsDirectional.fromSTEB(
      AppSpacing.md,
      AppSpacing.md,
      AppSpacing.md,
      AppSpacing.xs,
    ),
    child: Text(text, style: Theme.of(context).textTheme.titleSmall),
  );
}
