import 'package:platform_core/platform_core.dart';

/// API-shaped JSON, parsed by the same models production uses.
ServiceFamilyInfo family(
  int id,
  String code,
  String ar,
  String en, {
  String kind = 'passenger_city',
  String icon = 'car',
}) => ServiceFamilyInfo.fromJson({
  'id': id,
  'code': code,
  'kind': kind,
  'name': {'ar': ar, 'en': en},
  'description': {},
  'icon': icon,
  'sort_order': id * 10,
  'categories_count': 1,
});

ServiceCategoryInfo category(
  int id,
  int familyId,
  String ar,
  String en, {
  List<Map<String, Object?>> fields = const [],
  int? maxPassengers = 4,
  int? maxLuggage,
  String? maxWeightKg,
  int? minFare = 1500,
  String icon = 'car',
  bool negotiation = true,
}) => ServiceCategoryInfo.fromJson({
  'id': id,
  'code': 'cat_$id',
  'version': {
    'id': id * 100,
    'number': 1,
    'published_at': '2026-10-20T10:00:00Z',
  },
  'family': {
    'id': familyId,
    'code': 'f$familyId',
    'kind': 'passenger_city',
    'name': {'ar': 'عائلة'},
  },
  'name': {'ar': ar, 'en': en},
  'description': {'ar': 'وصف $ar', 'en': 'About $en'},
  'icon': icon,
  'map_marker': icon,
  'sort_order': id * 10,
  'booking': {
    'allowed_booking_types': ['on_demand'],
    'supports_stops': false,
    'max_stops': 0,
    'service_hours': [],
  },
  'pricing': {
    'negotiation_enabled': negotiation,
    'min_customer_fare_minor': minFare,
    'max_customer_fare_minor': null,
  },
  'dispatch': {},
  'limits': {
    'max_passengers': maxPassengers,
    'max_luggage': maxLuggage,
    'max_weight_kg': maxWeightKg,
    'max_volume_m3': null,
  },
  'safety': {},
  'payments': {
    'payment_methods': ['cash'],
    'cash_allowed': true,
  },
  'vehicle_category_ids': [1],
  'vehicle_categories': [
    {
      'id': 1,
      'code': 'taxi',
      'name': {'ar': 'تاكسي', 'en': 'Taxi'},
      'passenger_capacity': 4,
    },
  ],
  'form_schema': {'version': 1, 'fields': fields},
});

const passengersField = {
  'key': 'passengers',
  'type': 'integer',
  'label': {'ar': 'عدد الركاب', 'en': 'Passengers'},
  'help': null,
  'required': true,
  'min': 1,
  'max': 4,
  'options': <Object>[],
  'visible_if': null,
};

const notesField = {
  'key': 'notes',
  'type': 'textarea',
  'label': {'ar': 'ملاحظات', 'en': 'Notes'},
  'help': {'ar': 'اختياري', 'en': 'Optional'},
  'required': false,
  'min': null,
  'max': 100,
  'options': <Object>[],
  'visible_if': null,
};
