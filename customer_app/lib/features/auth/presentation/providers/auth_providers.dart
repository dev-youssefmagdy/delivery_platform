import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../core/providers.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';

/// This app signs in as `customer` (X-App header and token ability).
final identityRepositoryProvider = Provider<IdentityRepository>(
  (ref) => IdentityRepository(
    api: ref.watch(apiClientProvider),
    app: ClientApp.customer,
    deviceId: DeviceIdStore(ref.watch(sharedPreferencesProvider)),
    appVersion: () => ref.read(appVersionProvider.future),
  ),
);

Duration? _noAutoRetry(int retryCount, Object error) => null;

/// The signed-in user (`GET /me`); null while signed out. Rebuilt whenever
/// the session changes. Errors surface as the account-status retry screen.
class MeNotifier extends AsyncNotifier<MeUser?> {
  MeUser? _seed;

  @override
  Future<MeUser?> build() async {
    final session = ref.watch(sessionStatusProvider);
    if (session != SessionStatus.authenticated) return null;
    final seed = _seed;
    if (seed != null) {
      _seed = null;
      return seed;
    }
    return ref.read(identityRepositoryProvider).me();
  }

  /// Hands over the user returned by OTP verification so sign-in does not
  /// fetch `/me` a second time.
  void seed(MeUser user) => _seed = user;

  void set(MeUser user) => state = AsyncData(user);

  Future<void> reload() async {
    state = const AsyncLoading<MeUser?>();
    state = await AsyncValue.guard(
      () => ref.read(identityRepositoryProvider).me(),
    );
  }
}

final meProvider = AsyncNotifierProvider<MeNotifier, MeUser?>(
  MeNotifier.new,
  retry: _noAutoRetry,
);

/// A restricted account sees its notice once per app run, then continues.
class RestrictedAckNotifier extends Notifier<bool> {
  @override
  bool build() => false;

  void acknowledge() => state = true;

  void reset() => state = false;
}

final restrictedAckProvider = NotifierProvider<RestrictedAckNotifier, bool>(
  RestrictedAckNotifier.new,
);

final authStageProvider = Provider<AuthStage>((ref) {
  final me = ref.watch(meProvider);
  return computeAuthStage(
    session: ref.watch(sessionStatusProvider),
    meLoading: me.isLoading,
    meFailed: me.hasError,
    me: me.value,
    requireProfileAndConsent: true,
    restrictedAcknowledged: ref.watch(restrictedAckProvider),
  );
});

/// Phone and server answer of the OTP request in progress (kept out of the
/// URL; the OTP screen redirects to the phone screen without it).
@immutable
class SignInDraft {
  const SignInDraft({required this.phone, required this.otp});
  final String phone;
  final OtpRequestResult otp;
}

class SignInDraftNotifier extends Notifier<SignInDraft?> {
  @override
  SignInDraft? build() => null;

  void set(SignInDraft draft) => state = draft;

  void clear() => state = null;
}

final signInDraftProvider = NotifierProvider<SignInDraftNotifier, SignInDraft?>(
  SignInDraftNotifier.new,
);

/// Everything the auth screens do. Screens call this, never the repository
/// or dio directly (Plan §6.2).
class AuthActions {
  AuthActions(this._ref);

  final Ref _ref;

  IdentityRepository get _repo => _ref.read(identityRepositoryProvider);

  Future<OtpRequestResult> requestOtp(String phone) async {
    final result = await _repo.requestOtp(phone);
    _ref
        .read(signInDraftProvider.notifier)
        .set(SignInDraft(phone: phone, otp: result));
    return result;
  }

  Future<void> verifyOtp(String phone, String code) async {
    final session = await _repo.verifyOtp(phone, code);
    _ref.read(meProvider.notifier).seed(session.user);
    await _ref.read(sessionControllerProvider).signIn(session.token);
    _ref.read(signInDraftProvider.notifier).clear();
  }

  Future<void> updateProfile(ProfileUpdate update) async {
    final me = await _repo.updateProfile(update);
    _ref.read(meProvider.notifier).set(me);
  }

  Future<PolicyDocument> policy(String type) => _repo.policy(type);

  Future<void> acceptConsents(List<String> ids, {bool? marketingOptIn}) async {
    await _repo.acceptConsents(ids, marketingOptIn: marketingOptIn);
    _ref.read(meProvider.notifier).set(await _repo.me());
  }

  Future<void> logout() async {
    try {
      await _repo.logout();
    } on ApiException {
      // Offline or already expired: the local sign-out below is what counts.
    }
    _ref.read(restrictedAckProvider.notifier).reset();
    await _ref.read(sessionControllerProvider).signOut();
  }

  Future<void> requestDeletion(String idempotencyKey) async {
    await _repo.requestAccountDeletion(idempotencyKey: idempotencyKey);
    _ref.read(meProvider.notifier).set(await _repo.me());
  }

  Future<void> cancelDeletion(String requestId) async {
    await _repo.cancelAccountDeletion(requestId);
    _ref.read(meProvider.notifier).set(await _repo.me());
  }
}

final authActionsProvider = Provider<AuthActions>(AuthActions.new);
