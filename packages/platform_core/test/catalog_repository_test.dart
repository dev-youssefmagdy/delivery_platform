import 'package:dio/dio.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

import 'api_client_test.dart' show FakeAdapter, json, env;

/// Exact shapes of `GET /service-families|categories|vehicle-categories`
/// as produced by the backend resources (docs/API.md 8.6).
const _familyJson = {
  'id': 1,
  'code': 'rides',
  'kind': 'passenger_city',
  'name': {'ar': 'رحلات', 'en': 'Rides'},
  'description': {'ar': 'وصف'},
  'icon': 'car',
  'sort_order': 10,
  'categories_count': 2,
};

const _categoryJson = {
  'id': 7,
  'code': 'ride_taxi',
  'version': {'id': 31, 'number': 2, 'published_at': '2026-10-20T10:00:00Z'},
  'family': {
    'id': 1,
    'code': 'rides',
    'kind': 'passenger_city',
    'name': {'ar': 'رحلات'},
  },
  'name': {'ar': 'تاكسي', 'en': 'Taxi'},
  'description': {},
  'icon': 'car',
  'map_marker': 'car',
  'sort_order': 20,
  'booking': {
    'allowed_booking_types': ['on_demand', 'scheduled', 'teleport'],
    'supports_stops': true,
    'max_stops': 3,
    'service_hours': [],
  },
  'pricing': {
    'negotiation_enabled': true,
    'reference_estimate_enabled': true,
    'min_customer_fare_minor': 1500,
    'max_customer_fare_minor': 150000,
    'counter_offer_min_pct': 70,
    'counter_offer_max_pct': 200,
  },
  'dispatch': {'offer_ttl_seconds': 120},
  'limits': {
    'max_passengers': 4,
    'max_luggage': 2,
    'max_weight_kg': null,
    'max_volume_m3': '0.50',
  },
  'safety': {'pin_required': true},
  'payments': {
    'payment_methods': ['cash', 'card'],
    'cash_allowed': true,
    'tipping_enabled': true,
    'coupon_eligible': true,
  },
  'vehicle_category_ids': [3],
  'vehicle_categories': [
    {
      'id': 3,
      'code': 'taxi',
      'name': {'ar': 'تاكسي'},
      'icon': 'car',
      'map_marker': 'car',
      'passenger_capacity': 4,
      'luggage_capacity': 2,
      'max_payload_kg': null,
      'max_volume_m3': null,
    },
  ],
  'form_schema': {
    'version': 4,
    'fields': [
      {
        'key': 'passengers',
        'type': 'integer',
        'label': {'ar': 'عدد الركاب', 'en': 'Passengers'},
        'help': null,
        'required': true,
        'min': 1,
        'max': 4,
        'options': [],
        'visible_if': null,
      },
    ],
  },
};

void main() {
  late FakeAdapter adapter;
  late CatalogRepository repo;

  void respond(Object? data) {
    adapter = FakeAdapter((o) => json(200, {'success': true, 'data': data}));
    repo = CatalogRepository(
      ApiClient(
        env: env,
        dio: Dio(BaseOptions(baseUrl: env.apiBaseUrl))
          ..httpClientAdapter = adapter,
        readToken: () => null,
        readLanguageCode: () => 'ar',
      ),
    );
  }

  test('families parse', () async {
    respond([_familyJson]);
    final families = await repo.families();
    expect(families.single.code, 'rides');
    expect(families.single.kind, ServiceFamilyKind.passengerCity);
    expect(families.single.name.resolve('en'), 'Rides');
    expect(families.single.description.resolve('en'), 'وصف');
    expect(families.single.categoriesCount, 2);
    expect(adapter.requests.single.path, endsWith('/service-families'));
  });

  test('categories parse the published snapshot and the form schema', () async {
    respond([_categoryJson]);
    final categories = await repo.categories(
      bookingType: BookingType.onDemand,
      familyId: 1,
    );
    final c = categories.single;
    expect(c.id, 7);
    expect(c.versionId, 31);
    expect(c.versionNumber, 2);
    expect(c.bookingTypes, [
      BookingType.onDemand,
      BookingType.scheduled,
      BookingType.unknown,
    ]);
    expect(c.supportsStops, isTrue);
    expect(c.maxStops, 3);
    expect(c.minFareMinor, 1500);
    expect(c.maxPassengers, 4);
    expect(c.maxWeightKg, isNull);
    expect(c.maxVolumeM3, 0.5);
    expect(c.paymentMethods, [PaymentMethodType.cash, PaymentMethodType.card]);
    expect(c.vehicleCategories.single.passengerCapacity, 4);
    expect(c.formSchema.version, 4);
    expect(c.formSchema.fields.single.key, 'passengers');
    expect(c.formSchema.fields.single.max, 4);
    final uri = adapter.requests.single.uri;
    expect(uri.queryParameters, {
      'booking_type': 'on_demand',
      'service_family_id': '1',
    });
  });

  test('vehicle categories pass the service category filter', () async {
    respond([
      {
        'id': 3,
        'code': 'taxi',
        'name': {'ar': 'تاكسي'},
        'max_payload_kg': '1000.00',
      },
    ]);
    final vehicles = await repo.vehicleCategories(serviceCategoryId: 7);
    expect(vehicles.single.maxPayloadKg, 1000.0);
    expect(adapter.requests.single.uri.queryParameters, {
      'service_category_id': '7',
    });
  });

  test('a non-list payload is an invalid response', () async {
    respond({'oops': true});
    expect(
      repo.families(),
      throwsA(
        isA<ApiException>().having((e) => e.code, 'code', 'invalid_response'),
      ),
    );
  });
}
