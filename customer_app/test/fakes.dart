import 'package:platform_core/platform_core.dart';

/// Scriptable identity API for widget tests.
class FakeIdentityRepository implements IdentityRepository {
  FakeIdentityRepository({MeUser? me}) : meUser = me ?? defaultUser();

  MeUser meUser;
  Object? requestError;
  Object? verifyError;
  Object? meError;
  String validCode = '123456';
  final requestedPhones = <String>[];
  final verifyAttempts = <String>[];
  final accepted = <List<String>>[];
  bool? acceptedMarketing;
  final deletionKeys = <String>[];
  bool loggedOut = false;
  bool cancelled = false;
  ProfileUpdate? lastProfile;

  static MeUser defaultUser({
    UserStatus status = UserStatus.active,
    bool complete = true,
    List<PolicyVersionRef> pending = const [],
    DeletionRequestInfo? deletion,
  }) => MeUser(
    id: 'u-1',
    name: complete ? 'Mona' : null,
    phone: '+201001234567',
    status: status,
    profileComplete: complete,
    pendingConsents: pending,
    deletionRequest: deletion,
  );

  @override
  Future<OtpRequestResult> requestOtp(String phoneE164) async {
    requestedPhones.add(phoneE164);
    if (requestError != null) throw requestError!;
    return const OtpRequestResult(
      expiresInSeconds: 300,
      resendInSeconds: 30,
      length: 6,
    );
  }

  @override
  Future<AuthSessionInfo> verifyOtp(String phoneE164, String code) async {
    verifyAttempts.add(code);
    if (verifyError != null) throw verifyError!;
    if (code != validCode) {
      throw const ApiException(
        kind: ApiFailureKind.validation,
        code: 'otp_invalid',
        message: null,
        statusCode: 422,
      );
    }
    return AuthSessionInfo(token: 'tok', user: meUser, isNewUser: true);
  }

  @override
  Future<MeUser> me() async {
    if (meError != null) throw meError!;
    return meUser;
  }

  @override
  Future<MeUser> updateProfile(ProfileUpdate update) async {
    lastProfile = update;
    meUser = MeUser(
      id: meUser.id,
      name: update.name ?? meUser.name,
      phone: meUser.phone,
      status: meUser.status,
      profileComplete: true,
      pendingConsents: meUser.pendingConsents,
    );
    return meUser;
  }

  @override
  Future<PolicyDocument> policy(String type) async => PolicyDocument(
    id: 'p',
    type: type,
    version: 1,
    title: 'Policy title $type',
    content: 'Policy body $type',
  );

  @override
  Future<void> acceptConsents(
    List<String> policyIds, {
    bool? marketingOptIn,
  }) async {
    accepted.add(policyIds);
    acceptedMarketing = marketingOptIn;
    meUser = MeUser(
      id: meUser.id,
      name: meUser.name,
      status: meUser.status,
      profileComplete: meUser.profileComplete,
    );
  }

  @override
  Future<void> logout() async => loggedOut = true;

  @override
  Future<void> requestAccountDeletion({
    required String idempotencyKey,
    String? reason,
  }) async {
    deletionKeys.add(idempotencyKey);
    meUser = defaultUser(
      status: UserStatus.deletionPending,
      deletion: const DeletionRequestInfo(id: 'd-1', status: 'pending'),
    );
  }

  @override
  Future<void> cancelAccountDeletion(String requestId) async {
    cancelled = true;
    meUser = defaultUser();
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}

/// Scriptable catalog API for widget tests.
class FakeCatalogRepository implements CatalogRepository {
  FakeCatalogRepository({
    this.familiesResult = const [],
    this.categoriesResult = const [],
  });

  List<ServiceFamilyInfo> familiesResult;
  List<ServiceCategoryInfo> categoriesResult;
  Object? error;
  int familiesCalls = 0;

  @override
  Future<List<ServiceFamilyInfo>> families() async {
    familiesCalls++;
    if (error != null) throw error!;
    return familiesResult;
  }

  @override
  Future<List<ServiceCategoryInfo>> categories({
    BookingType? bookingType,
    int? familyId,
    double? lat,
    double? lng,
  }) async {
    if (error != null) throw error!;
    return categoriesResult;
  }

  @override
  dynamic noSuchMethod(Invocation invocation) => super.noSuchMethod(invocation);
}
