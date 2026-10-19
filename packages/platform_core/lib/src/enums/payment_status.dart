import 'api_enum.dart';

/// Mirrors backend `PaymentStatus`; unknown wire values map to [PaymentStatus.unknown].
enum PaymentStatus implements ApiEnum {
  notRequired('not_required'),
  pending('pending'),
  requiresAction('requires_action'),
  authorized('authorized'),
  paid('paid'),
  failed('failed'),
  partiallyRefunded('partially_refunded'),
  refunded('refunded'),
  cashDue('cash_due'),
  cashCollected('cash_collected'),
  reconciliationRequired('reconciliation_required'),
  unknown('');

  const PaymentStatus(this.value);

  @override
  final String value;

  static PaymentStatus parse(String? raw) =>
      parseApiEnum(PaymentStatus.values, raw, PaymentStatus.unknown);
}
