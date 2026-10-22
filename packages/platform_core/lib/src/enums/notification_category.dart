import 'api_enum.dart';

/// Mirrors backend `NotificationCategory`; unknown wire values map to [NotificationCategory.unknown].
enum NotificationCategory implements ApiEnum {
  booking('booking'),
  offers('offers'),
  account('account'),
  payments('payments'),
  payouts('payouts'),
  promotions('promotions'),
  support('support'),
  safety('safety'),
  marketing('marketing'),
  system('system'),
  unknown('');

  const NotificationCategory(this.value);

  @override
  final String value;

  static NotificationCategory parse(String? raw) =>
      parseApiEnum(NotificationCategory.values, raw, NotificationCategory.unknown);
}
