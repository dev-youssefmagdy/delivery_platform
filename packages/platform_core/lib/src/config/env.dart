/// Deployment flavor, selected with `--dart-define=FLAVOR=<dev|staging|prod>`.
enum Flavor {
  dev,
  staging,
  prod;

  static Flavor parse(String raw) => Flavor.values.firstWhere(
    (f) => f.name == raw.toLowerCase(),
    orElse: () => Flavor.dev,
  );
}

/// Build-time configuration injected with `--dart-define`. No secrets here:
/// only public endpoints and the public Reverb app key.
class Env {
  const Env({
    required this.apiBaseUrl,
    required this.reverbHost,
    required this.reverbPort,
    required this.reverbKey,
    required this.reverbScheme,
    required this.mapTileUrl,
    required this.flavor,
    this.mapAttribution = defaultMapAttribution,
  });

  static const defaultMapAttribution = '© OpenStreetMap contributors';

  /// Reads the compile-time defines. Defaults target the Android emulator
  /// talking to a local `php artisan serve` (10.0.2.2 is the host loopback).
  factory Env.fromEnvironment() => Env(
    apiBaseUrl: const String.fromEnvironment(
      'API_BASE_URL',
      defaultValue: 'http://10.0.2.2:8000/api/v1',
    ),
    reverbHost: const String.fromEnvironment(
      'REVERB_HOST',
      defaultValue: '10.0.2.2',
    ),
    reverbPort: const int.fromEnvironment('REVERB_PORT', defaultValue: 8080),
    reverbKey: const String.fromEnvironment('REVERB_KEY'),
    reverbScheme: const String.fromEnvironment(
      'REVERB_SCHEME',
      defaultValue: 'http',
    ),
    mapTileUrl: const String.fromEnvironment(
      'MAP_TILE_URL',
      defaultValue: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
    ),
    mapAttribution: const String.fromEnvironment(
      'MAP_ATTRIBUTION',
      defaultValue: defaultMapAttribution,
    ),
    flavor: Flavor.parse(
      const String.fromEnvironment('FLAVOR', defaultValue: 'dev'),
    ),
  );

  final String apiBaseUrl;
  final String reverbHost;
  final int reverbPort;
  final String reverbKey;
  final String reverbScheme;
  final String mapTileUrl;
  final Flavor flavor;

  /// Attribution text of the tile provider (shown on every map).
  final String mapAttribution;

  bool get isProd => flavor == Flavor.prod;

  /// Base URL (scheme + host) of the API without the `/api/v1` path; used to
  /// build the private-channel auth endpoint.
  String get apiOrigin => Uri.parse(apiBaseUrl).origin;
}
