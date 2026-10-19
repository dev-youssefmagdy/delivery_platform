import 'api_enum.dart';

/// Mirrors backend `NotificationChannel`; unknown wire values map to [NotificationChannel.unknown].
enum NotificationChannel implements ApiEnum {
  push('push'),
  sms('sms'),
  email('email'),
  inApp('in_app'),
  unknown('');

  const NotificationChannel(this.value);

  @override
  final String value;

  static NotificationChannel parse(String? raw) => parseApiEnum(
    NotificationChannel.values,
    raw,
    NotificationChannel.unknown,
  );
}
