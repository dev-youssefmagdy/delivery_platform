import 'api_enum.dart';

/// Mirrors backend `NotificationLinkType`: what a notification opens.
enum NotificationLinkType implements ApiEnum {
  booking('booking'),
  payout('payout'),
  documents('documents'),
  application('application'),
  wallet('wallet'),
  support('support'),
  message('message'),
  chat('chat'),
  export('export'),
  inbox('inbox'),
  safety('safety'),
  unknown('');

  const NotificationLinkType(this.value);

  @override
  final String value;

  static NotificationLinkType parse(String? raw) => parseApiEnum(
    NotificationLinkType.values,
    raw,
    NotificationLinkType.unknown,
  );
}
