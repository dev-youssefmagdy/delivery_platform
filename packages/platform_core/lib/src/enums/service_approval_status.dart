import 'api_enum.dart';

/// Mirrors backend `ServiceApprovalStatus` (a requested service category).
enum ServiceApprovalStatus implements ApiEnum {
  requested('requested'),
  approved('approved'),
  rejected('rejected'),
  unknown('');

  const ServiceApprovalStatus(this.value);

  @override
  final String value;

  static ServiceApprovalStatus parse(String? raw) => parseApiEnum(
    ServiceApprovalStatus.values,
    raw,
    ServiceApprovalStatus.unknown,
  );
}
