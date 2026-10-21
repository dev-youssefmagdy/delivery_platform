import 'package:flutter/foundation.dart'
    show defaultTargetPlatform, TargetPlatform;
import 'package:geolocator/geolocator.dart';

import '../enums/location_source.dart';

class GeoPoint {
  const GeoPoint({
    required this.latitude,
    required this.longitude,
    this.accuracyMeters,
    this.heading,
    this.speedMps,
    this.source = LocationSource.gps,
    this.capturedAt,
  });

  final double latitude;
  final double longitude;
  final double? accuracyMeters;
  final double? heading;
  final double? speedMps;
  final LocationSource source;
  final DateTime? capturedAt;
}

enum LocationPermissionState { granted, denied, deniedForever, serviceDisabled }

/// Abstraction over geolocator so features and tests never import it.
abstract interface class LocationService {
  Future<LocationPermissionState> status();

  /// Requests permission. Callers must show their rationale UI (see
  /// [LocationPermissionFlow]) BEFORE calling this.
  Future<LocationPermissionState> request();

  Future<GeoPoint?> current();
  Stream<GeoPoint> watch({int distanceFilterMeters = 20});

  Future<void> openAppSettings();
  Future<void> openLocationSettings();
}

class GeolocatorLocationService implements LocationService {
  const GeolocatorLocationService();

  LocationPermissionState _map(LocationPermission p) => switch (p) {
    LocationPermission.always ||
    LocationPermission.whileInUse => LocationPermissionState.granted,
    LocationPermission.deniedForever => LocationPermissionState.deniedForever,
    _ => LocationPermissionState.denied,
  };

  @override
  Future<LocationPermissionState> status() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      return LocationPermissionState.serviceDisabled;
    }
    return _map(await Geolocator.checkPermission());
  }

  @override
  Future<LocationPermissionState> request() async {
    if (!await Geolocator.isLocationServiceEnabled()) {
      return LocationPermissionState.serviceDisabled;
    }
    return _map(await Geolocator.requestPermission());
  }

  GeoPoint _point(Position p) => GeoPoint(
    latitude: p.latitude,
    longitude: p.longitude,
    accuracyMeters: p.accuracy,
    heading: p.heading,
    speedMps: p.speed,
    capturedAt: p.timestamp,
  );

  @override
  Future<GeoPoint?> current() async {
    if (await status() != LocationPermissionState.granted) return null;
    return _point(await Geolocator.getCurrentPosition());
  }

  @override
  Stream<GeoPoint> watch({int distanceFilterMeters = 20}) =>
      Geolocator.getPositionStream(
        locationSettings: LocationSettings(
          accuracy: LocationAccuracy.high,
          distanceFilter: distanceFilterMeters,
        ),
      ).map(_point);

  @override
  Future<void> openAppSettings() => Geolocator.openAppSettings();

  @override
  Future<void> openLocationSettings() => Geolocator.openLocationSettings();
}

/// Rationale-first permission flow: ask the user (via [showRationale]) why we
/// need location, and only then trigger the OS prompt.
class LocationPermissionFlow {
  const LocationPermissionFlow(this._service);

  final LocationService _service;

  /// [showRationale] returns true when the user agrees to continue.
  Future<LocationPermissionState> ensure({
    required Future<bool> Function() showRationale,
  }) async {
    final current = await _service.status();
    if (current == LocationPermissionState.granted ||
        current == LocationPermissionState.deniedForever ||
        current == LocationPermissionState.serviceDisabled) {
      return current;
    }
    if (!await showRationale()) return LocationPermissionState.denied;
    return _service.request();
  }
}

/// What the Android foreground-service notification says while the app keeps
/// tracking in the background (always visible: the driver can see and stop it).
class TrackingNotice {
  const TrackingNotice({required this.title, required this.text});

  final String title;
  final String text;
}

/// Continuous position source for the driver's availability and active jobs.
/// Separate from [LocationService] (one-off reads and permissions) because it
/// runs as a foreground service on Android and keeps the iOS background
/// location indicator visible; stopping the subscription stops the service.
abstract interface class TrackingLocationSource {
  /// Positions at most every [interval] while the stream is listened to.
  Stream<GeoPoint> track({
    required Duration interval,
    required TrackingNotice notice,
  });
}

class GeolocatorTrackingSource implements TrackingLocationSource {
  const GeolocatorTrackingSource();

  @override
  Stream<GeoPoint> track({
    required Duration interval,
    required TrackingNotice notice,
  }) {
    final settings = switch (defaultTargetPlatform) {
      TargetPlatform.android => AndroidSettings(
        accuracy: LocationAccuracy.high,
        intervalDuration: interval,
        foregroundNotificationConfig: ForegroundNotificationConfig(
          notificationTitle: notice.title,
          notificationText: notice.text,
          notificationChannelName: notice.title,
          enableWakeLock: true,
          setOngoing: true,
        ),
      ),
      TargetPlatform.iOS || TargetPlatform.macOS => AppleSettings(
        accuracy: LocationAccuracy.high,
        activityType: ActivityType.automotiveNavigation,
        pauseLocationUpdatesAutomatically: false,
        allowBackgroundLocationUpdates: true,
        showBackgroundLocationIndicator: true,
      ),
      _ => const LocationSettings(accuracy: LocationAccuracy.high),
    };
    return Geolocator.getPositionStream(locationSettings: settings).map(
      (p) => GeoPoint(
        latitude: p.latitude,
        longitude: p.longitude,
        accuracyMeters: p.accuracy,
        heading: p.heading,
        speedMps: p.speed,
        capturedAt: p.timestamp,
      ),
    );
  }
}
