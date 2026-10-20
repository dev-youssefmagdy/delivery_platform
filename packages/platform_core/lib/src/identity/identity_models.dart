import '../enums/user_status.dart';

/// Which mobile app is calling; sent as the `X-App` header on the OTP calls
/// and equal to the token ability the server issues.
enum ClientApp {
  customer('customer'),
  provider('provider');

  const ClientApp(this.value);
  final String value;
}

/// Server answer to an OTP request. Identical for every phone number.
class OtpRequestResult {
  const OtpRequestResult({
    required this.expiresInSeconds,
    required this.resendInSeconds,
    required this.length,
  });

  factory OtpRequestResult.fromJson(Map<String, dynamic> json) =>
      OtpRequestResult(
        expiresInSeconds: (json['expires_in'] as num?)?.toInt() ?? 300,
        resendInSeconds: (json['resend_in'] as num?)?.toInt() ?? 60,
        length: (json['length'] as num?)?.toInt() ?? 6,
      );

  final int expiresInSeconds;
  final int resendInSeconds;
  final int length;
}

/// A policy version reference (`id` is the public uuid).
class PolicyVersionRef {
  const PolicyVersionRef({
    required this.id,
    required this.type,
    required this.version,
  });

  factory PolicyVersionRef.fromJson(Map<String, dynamic> json) =>
      PolicyVersionRef(
        id: '${json['id']}',
        type: '${json['type']}',
        version: (json['version'] as num?)?.toInt() ?? 0,
      );

  final String id;
  final String type;
  final int version;
}

/// Full policy text for the consent screen.
class PolicyDocument {
  const PolicyDocument({
    required this.id,
    required this.type,
    required this.version,
    required this.title,
    required this.content,
  });

  factory PolicyDocument.fromJson(Map<String, dynamic> json) => PolicyDocument(
    id: '${json['id']}',
    type: '${json['type']}',
    version: (json['version'] as num?)?.toInt() ?? 0,
    title: '${json['title'] ?? ''}',
    content: '${json['content'] ?? ''}',
  );

  final String id;
  final String type;
  final int version;
  final String title;
  final String content;
}

class DeletionRequestInfo {
  const DeletionRequestInfo({required this.id, required this.status});

  factory DeletionRequestInfo.fromJson(Map<String, dynamic> json) =>
      DeletionRequestInfo(id: '${json['id']}', status: '${json['status']}');

  final String id;
  final String status;
}

class CustomerProfileInfo {
  const CustomerProfileInfo({
    this.accessibilityNeeds,
    this.emergencyContactName,
    this.emergencyContactPhone,
    this.marketingOptIn = false,
  });

  factory CustomerProfileInfo.fromJson(Map<String, dynamic> json) =>
      CustomerProfileInfo(
        accessibilityNeeds: json['accessibility_needs'] as String?,
        emergencyContactName: json['emergency_contact_name'] as String?,
        emergencyContactPhone: json['emergency_contact_phone'] as String?,
        marketingOptIn: json['marketing_opt_in'] == true,
      );

  final String? accessibilityNeeds;
  final String? emergencyContactName;
  final String? emergencyContactPhone;
  final bool marketingOptIn;
}

/// The signed-in user (`GET /me`).
class MeUser {
  const MeUser({
    required this.id,
    required this.status,
    required this.profileComplete,
    this.name,
    this.phone,
    this.email,
    this.photoUrl,
    this.preferredLocale,
    this.customerProfile,
    this.pendingConsents = const [],
    this.deletionRequest,
  });

  factory MeUser.fromJson(Map<String, dynamic> json) {
    final consents = json['consents'];
    final pending = consents is Map ? consents['pending'] : null;
    final profile = json['customer_profile'];
    final deletion = json['deletion_request'];
    return MeUser(
      id: '${json['id']}',
      name: json['name'] as String?,
      phone: json['phone'] as String?,
      email: json['email'] as String?,
      photoUrl: json['photo_url'] as String?,
      preferredLocale: json['preferred_locale'] as String?,
      status: UserStatus.parse(json['status'] as String?),
      profileComplete: json['profile_complete'] == true,
      customerProfile: profile is Map
          ? CustomerProfileInfo.fromJson(Map<String, dynamic>.from(profile))
          : null,
      pendingConsents: pending is List
          ? [
              for (final p in pending)
                if (p is Map)
                  PolicyVersionRef.fromJson(Map<String, dynamic>.from(p)),
            ]
          : const [],
      deletionRequest: deletion is Map
          ? DeletionRequestInfo.fromJson(Map<String, dynamic>.from(deletion))
          : null,
    );
  }

  final String id;
  final String? name;
  final String? phone;
  final String? email;
  final String? photoUrl;
  final String? preferredLocale;
  final UserStatus status;
  final bool profileComplete;
  final CustomerProfileInfo? customerProfile;
  final List<PolicyVersionRef> pendingConsents;
  final DeletionRequestInfo? deletionRequest;
}

/// Result of a successful OTP verification.
class AuthSessionInfo {
  const AuthSessionInfo({
    required this.token,
    required this.user,
    required this.isNewUser,
  });

  factory AuthSessionInfo.fromJson(Map<String, dynamic> json) =>
      AuthSessionInfo(
        token: '${json['token']}',
        isNewUser: json['is_new_user'] == true,
        user: MeUser.fromJson(Map<String, dynamic>.from(json['user'] as Map)),
      );

  final String token;
  final MeUser user;
  final bool isNewUser;
}

/// Profile fields the app can send (`PATCH /me`); null = leave unchanged.
class ProfileUpdate {
  const ProfileUpdate({
    this.name,
    this.email,
    this.preferredLocale,
    this.marketingOptIn,
  });

  final String? name;
  final String? email;
  final String? preferredLocale;
  final bool? marketingOptIn;

  Map<String, Object?> toJson() => {
    'name': ?name,
    'email': ?email,
    'preferred_locale': ?preferredLocale,
    'marketing_opt_in': ?marketingOptIn,
  };
}
