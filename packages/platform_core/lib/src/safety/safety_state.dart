import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../api/api_exception.dart';
import 'safety_models.dart';
import 'safety_repository.dart';

enum SosPhase { idle, sending, sent, failed }

/// State of the SOS button on one job: nothing is hidden, the result lists what was done.
class SosState {
  const SosState({this.phase = SosPhase.idle, this.incident, this.error});

  final SosPhase phase;
  final SafetyIncident? incident;
  final Object? error;
}

/// Raises the SOS. The GPS fix is asked for with a short timeout and NEVER delays or blocks the alert: without a position the
/// alert goes out without one (the server says so in its answer).
class SosNotifier extends Notifier<SosState> {
  SosNotifier(this._repository);

  final Provider<SafetyRepository> _repository;

  @override
  SosState build() => const SosState();

  Future<void> trigger(String bookingId, Future<SafetyFix?> Function() locate) async {
    if (state.phase == SosPhase.sending) return;
    state = const SosState(phase: SosPhase.sending);
    SafetyFix? fix;
    try {
      fix = await locate().timeout(const Duration(seconds: 4));
    } on Object {
      fix = null;
    }
    try {
      final incident = await ref.read(_repository).sos(bookingId: bookingId, fix: fix);
      state = SosState(phase: SosPhase.sent, incident: incident);
    } on ApiException catch (e) {
      state = SosState(phase: SosPhase.failed, error: e);
    } on Object catch (e) {
      state = SosState(phase: SosPhase.failed, error: e);
    }
  }

  void reset() => state = const SosState();
}

class TrustedContactsNotifier extends AsyncNotifier<TrustedContactList> {
  TrustedContactsNotifier(this._repository);

  final Provider<SafetyRepository> _repository;

  @override
  Future<TrustedContactList> build() => ref.watch(_repository).contacts();

  Future<void> add(TrustedContactInput input, {required String key}) async {
    await ref.read(_repository).addContact(input, key: key);
    ref.invalidateSelf();
    await future;
  }

  Future<void> change(String id, TrustedContactInput input) async {
    await ref.read(_repository).updateContact(id, input);
    ref.invalidateSelf();
    await future;
  }

  Future<void> remove(String id) async {
    await ref.read(_repository).removeContact(id);
    ref.invalidateSelf();
    await future;
  }
}

/// One-off safety actions with a result: the incident report, a follow-up, a file, and sharing the trip.
class SafetyActionsNotifier extends Notifier<bool> {
  SafetyActionsNotifier(this._repository);

  final Provider<SafetyRepository> _repository;

  /// True while an action runs (buttons disable themselves).
  @override
  bool build() => false;

  Future<T> _run<T>(Future<T> Function(SafetyRepository repository) action) async {
    state = true;
    try {
      return await action(ref.read(_repository));
    } finally {
      state = false;
    }
  }

  Future<SafetyIncident> report({
    required String bookingId,
    required String category,
    required String narrative,
    SafetyFix? fix,
    String? callbackPhone,
  }) => _run((r) => r.report(bookingId: bookingId, category: category, narrative: narrative, fix: fix, callbackPhone: callbackPhone));

  Future<SafetyIncident> addUpdate(String id, {required String key, required String body}) => _run((r) => r.addUpdate(id, key: key, body: body));

  Future<void> addFile(String id, {required String key, required String path}) => _run((r) => r.addAttachment(id, key: key, path: path));

  Future<TripShareResult> shareTrip(String bookingId) => _run((r) => r.shareTrip(bookingId));

  Future<void> stopShare(String bookingId) => _run((r) => r.stopTripShare(bookingId));
}

/// The providers of the safety feature of one app, built from its [SafetyRepository] provider. Screens in this package receive
/// the bundle, so both apps share the UI while each keeps its own dependency graph.
class SafetyProviders {
  SafetyProviders(this.repository)
    : sos = NotifierProvider<SosNotifier, SosState>(() => SosNotifier(repository)),
      contacts = AsyncNotifierProvider<TrustedContactsNotifier, TrustedContactList>(() => TrustedContactsNotifier(repository)),
      actions = NotifierProvider<SafetyActionsNotifier, bool>(() => SafetyActionsNotifier(repository)),
      config = FutureProvider<SafetyConfig?>((ref) => ref.watch(repository).config()),
      incidents = FutureProvider.autoDispose<IncidentPage>((ref) => ref.watch(repository).incidents()),
      incident = FutureProvider.autoDispose.family<SafetyIncident, String>((ref, id) => ref.watch(repository).incident(id)).call;

  final Provider<SafetyRepository> repository;
  final NotifierProvider<SosNotifier, SosState> sos;
  final AsyncNotifierProvider<TrustedContactsNotifier, TrustedContactList> contacts;
  final NotifierProvider<SafetyActionsNotifier, bool> actions;
  final FutureProvider<SafetyConfig?> config;
  final FutureProvider<IncidentPage> incidents;
  final FutureProvider<SafetyIncident> Function(String id) incident;
}
