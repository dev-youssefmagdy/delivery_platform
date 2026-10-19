import 'api_enum.dart';

/// Mirrors backend `TicketStatus`; unknown wire values map to [TicketStatus.unknown].
enum TicketStatus implements ApiEnum {
  open('open'),
  pendingCustomer('pending_customer'),
  pendingInternal('pending_internal'),
  escalated('escalated'),
  resolved('resolved'),
  closed('closed'),
  unknown('');

  const TicketStatus(this.value);

  @override
  final String value;

  static TicketStatus parse(String? raw) =>
      parseApiEnum(TicketStatus.values, raw, TicketStatus.unknown);
}
