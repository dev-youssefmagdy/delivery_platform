import '../enums/booking_type.dart';
import '../enums/payment_method_type.dart';
import '../enums/service_family_kind.dart';
import '../forms/form_schema.dart';

int? _int(Object? v) => (v as num?)?.toInt();

double? _double(Object? v) {
  if (v is num) return v.toDouble();
  return v is String ? double.tryParse(v) : null;
}

/// A group of services on the home screen (rides, courier, moving ...).
class ServiceFamilyInfo {
  const ServiceFamilyInfo({
    required this.id,
    required this.code,
    required this.kind,
    required this.name,
    this.description = const LocalizedText({}),
    this.icon,
    this.sortOrder = 0,
    this.categoriesCount = 0,
  });

  factory ServiceFamilyInfo.fromJson(Map<String, dynamic> json) =>
      ServiceFamilyInfo(
        id: _int(json['id']) ?? 0,
        code: '${json['code']}',
        kind: ServiceFamilyKind.parse(json['kind'] as String?),
        name: LocalizedText.fromJson(json['name']),
        description: LocalizedText.fromJson(json['description']),
        icon: json['icon'] as String?,
        sortOrder: _int(json['sort_order']) ?? 0,
        categoriesCount: _int(json['categories_count']) ?? 0,
      );

  final int id;
  final String code;
  final ServiceFamilyKind kind;
  final LocalizedText name;
  final LocalizedText description;
  final String? icon;
  final int sortOrder;
  final int categoriesCount;
}

/// A vehicle class with its capacities (decimals arrive as strings).
class VehicleCategoryInfo {
  const VehicleCategoryInfo({
    required this.id,
    required this.code,
    required this.name,
    this.icon,
    this.mapMarker,
    this.passengerCapacity,
    this.luggageCapacity,
    this.maxPayloadKg,
    this.maxVolumeM3,
  });

  factory VehicleCategoryInfo.fromJson(Map<String, dynamic> json) =>
      VehicleCategoryInfo(
        id: _int(json['id']) ?? 0,
        code: '${json['code']}',
        name: LocalizedText.fromJson(json['name']),
        icon: json['icon'] as String?,
        mapMarker: json['map_marker'] as String?,
        passengerCapacity: _int(json['passenger_capacity']),
        luggageCapacity: _int(json['luggage_capacity']),
        maxPayloadKg: _double(json['max_payload_kg']),
        maxVolumeM3: _double(json['max_volume_m3']),
      );

  final int id;
  final String code;
  final LocalizedText name;
  final String? icon;
  final String? mapMarker;
  final int? passengerCapacity;
  final int? luggageCapacity;
  final double? maxPayloadKg;
  final double? maxVolumeM3;
}

/// A bookable category as of its published version (docs/API.md 8.6).
/// `versionId` is the id that bookings will reference (P2.3).
class ServiceCategoryInfo {
  const ServiceCategoryInfo({
    required this.id,
    required this.code,
    required this.versionId,
    required this.versionNumber,
    required this.familyId,
    required this.familyKind,
    required this.name,
    required this.description,
    required this.bookingTypes,
    required this.formSchema,
    this.icon,
    this.sortOrder = 0,
    this.supportsStops = false,
    this.maxStops = 0,
    this.negotiationEnabled = true,
    this.minFareMinor,
    this.maxFareMinor,
    this.maxPassengers,
    this.maxLuggage,
    this.maxWeightKg,
    this.maxVolumeM3,
    this.paymentMethods = const [],
    this.cashAllowed = true,
    this.vehicleCategories = const [],
  });

  factory ServiceCategoryInfo.fromJson(Map<String, dynamic> json) {
    Map<String, dynamic> section(String key) => json[key] is Map
        ? Map<String, dynamic>.from(json[key] as Map)
        : const {};
    final version = section('version');
    final family = section('family');
    final booking = section('booking');
    final pricing = section('pricing');
    final limits = section('limits');
    final payments = section('payments');

    return ServiceCategoryInfo(
      id: _int(json['id']) ?? 0,
      code: '${json['code']}',
      versionId: _int(version['id']) ?? 0,
      versionNumber: _int(version['number']) ?? 0,
      familyId: _int(family['id']) ?? 0,
      familyKind: ServiceFamilyKind.parse(family['kind'] as String?),
      name: LocalizedText.fromJson(json['name']),
      description: LocalizedText.fromJson(json['description']),
      icon: json['icon'] as String?,
      sortOrder: _int(json['sort_order']) ?? 0,
      bookingTypes: [
        for (final t in (booking['allowed_booking_types'] as List?) ?? const [])
          BookingType.parse('$t'),
      ],
      supportsStops: booking['supports_stops'] == true,
      maxStops: _int(booking['max_stops']) ?? 0,
      negotiationEnabled: pricing['negotiation_enabled'] != false,
      minFareMinor: _int(pricing['min_customer_fare_minor']),
      maxFareMinor: _int(pricing['max_customer_fare_minor']),
      maxPassengers: _int(limits['max_passengers']),
      maxLuggage: _int(limits['max_luggage']),
      maxWeightKg: _double(limits['max_weight_kg']),
      maxVolumeM3: _double(limits['max_volume_m3']),
      paymentMethods: [
        for (final m in (payments['payment_methods'] as List?) ?? const [])
          PaymentMethodType.parse('$m'),
      ],
      cashAllowed: payments['cash_allowed'] != false,
      vehicleCategories: [
        for (final v in (json['vehicle_categories'] as List?) ?? const [])
          if (v is Map)
            VehicleCategoryInfo.fromJson(Map<String, dynamic>.from(v)),
      ],
      formSchema: ServiceFormSchema.fromJson(json['form_schema']),
    );
  }

  final int id;
  final String code;
  final int versionId;
  final int versionNumber;
  final int familyId;
  final ServiceFamilyKind familyKind;
  final LocalizedText name;
  final LocalizedText description;
  final String? icon;
  final int sortOrder;
  final List<BookingType> bookingTypes;
  final bool supportsStops;
  final int maxStops;
  final bool negotiationEnabled;
  final int? minFareMinor;
  final int? maxFareMinor;
  final int? maxPassengers;
  final int? maxLuggage;
  final double? maxWeightKg;
  final double? maxVolumeM3;
  final List<PaymentMethodType> paymentMethods;
  final bool cashAllowed;
  final List<VehicleCategoryInfo> vehicleCategories;
  final ServiceFormSchema formSchema;
}
