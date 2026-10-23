import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../auth/presentation/providers/auth_providers.dart';
import '../../../map/domain/place_selection.dart';
import '../../domain/entities/booking_entities.dart';

/// The current restricted-goods policy, fetched when a courier form opens
/// (`GET /policies/restricted_goods`). Its id is sent as
/// `restricted_goods_policy_version`; a newer policy than the one read makes
/// the server answer `restricted_goods_ack_stale` and the form re-reads it.
final restrictedGoodsPolicyProvider =
    FutureProvider.autoDispose<PolicyDocument>(
      (ref) => ref.watch(identityRepositoryProvider).policy('restricted_goods'),
      retry: (_, _) => null,
    );

/// The customer's acknowledgement of the restricted-goods policy, kept per
/// policy id so a new version needs a new tick.
class RestrictedGoodsAckNotifier extends Notifier<String?> {
  @override
  String? build() => null;

  void acknowledge(String policyId) => state = policyId;

  void clear() => state = null;
}

final restrictedGoodsAckProvider =
    NotifierProvider<RestrictedGoodsAckNotifier, String?>(
      RestrictedGoodsAckNotifier.new,
    );

/// An intermediate stop being composed (freight / moving): the place and the
/// person to meet there.
class StopDraft {
  const StopDraft({
    required this.place,
    this.name = '',
    this.phone = '',
    this.note = '',
  });

  final PlaceSelection place;
  final String name;
  final String phone;
  final String note;

  StopDraft copyWith({String? name, String? phone, String? note}) => StopDraft(
    place: place,
    name: name ?? this.name,
    phone: phone ?? this.phone,
    note: note ?? this.note,
  );

  bool get hasContact => name.trim().isNotEmpty || phone.trim().isNotEmpty;

  /// A contact needs both parts; null when fine, else the missing one.
  StopIssue? get issue {
    if (!hasContact) return null;
    if (name.trim().isEmpty) return StopIssue.nameMissing;
    if (FormLogic.normalizePhone(phone) == null) return StopIssue.phoneInvalid;
    return null;
  }

  StopInput toInput() => StopInput(
    place: PlaceInfo(
      lat: place.point.latitude,
      lng: place.point.longitude,
      title: place.title,
      line: place.address,
    ),
    contactName: hasContact ? name.trim() : null,
    contactPhone: hasContact
        ? (FormLogic.normalizePhone(phone) ?? phone.trim())
        : null,
    contactNote: note.trim().isEmpty ? null : note.trim(),
  );
}

enum StopIssue { nameMissing, phoneInvalid }

class StopsNotifier extends Notifier<List<StopDraft>> {
  @override
  List<StopDraft> build() => const [];

  void add(PlaceSelection place) => state = [...state, StopDraft(place: place)];

  void remove(int index) => state = [
    for (var i = 0; i < state.length; i++)
      if (i != index) state[i],
  ];

  void update(int index, StopDraft Function(StopDraft) change) => state = [
    for (var i = 0; i < state.length; i++)
      i == index ? change(state[i]) : state[i],
  ];

  void move(int index, int delta) {
    final to = index + delta;
    if (to < 0 || to >= state.length) return;
    final list = [...state];
    final item = list.removeAt(index);
    list.insert(to, item);
    state = list;
  }

  void clear() => state = const [];
}

final bookingStopsProvider = NotifierProvider<StopsNotifier, List<StopDraft>>(
  StopsNotifier.new,
);

/// `parcel` for courier, `freight` for freight, else `on_demand` when the
/// category allows it (moving), else its first allowed type.
BookingType bookingTypeFor(ServiceCategoryInfo category) {
  if (category.familyKind == ServiceFamilyKind.courier &&
      category.bookingTypes.contains(BookingType.parcel)) {
    return BookingType.parcel;
  }
  if (category.familyKind == ServiceFamilyKind.freight &&
      category.bookingTypes.contains(BookingType.freight)) {
    return BookingType.freight;
  }
  if (category.bookingTypes.isEmpty ||
      category.bookingTypes.contains(BookingType.onDemand)) {
    return BookingType.onDemand;
  }
  return category.bookingTypes.firstWhere(
    (t) => t != BookingType.unknown,
    orElse: () => BookingType.onDemand,
  );
}

/// Courier, moving and freight forms carry load files and use uploads.
bool usesLoadUploads(ServiceCategoryInfo category) =>
    category.familyKind == ServiceFamilyKind.moving ||
    category.familyKind == ServiceFamilyKind.freight;
