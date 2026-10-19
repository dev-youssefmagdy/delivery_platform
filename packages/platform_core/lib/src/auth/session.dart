import 'dart:async';

import 'token_store.dart';

enum SessionStatus {
  /// Token store not read yet.
  unknown,
  unauthenticated,
  authenticated,

  /// A request was rejected with 401 while signed in.
  expired,
}

/// Session state holder. Apps expose it through a Riverpod provider and bind
/// `ApiClient.sessionExpired` to [markExpired].
class SessionController {
  SessionController(this._store);

  final TokenStore _store;
  final _controller = StreamController<SessionStatus>.broadcast();
  SessionStatus _status = SessionStatus.unknown;

  SessionStatus get status => _status;
  Stream<SessionStatus> get changes => _controller.stream;

  Future<String?> readToken() => _store.read();

  Future<SessionStatus> restore() async {
    final token = await _store.read();
    return _set(
      token == null || token.isEmpty
          ? SessionStatus.unauthenticated
          : SessionStatus.authenticated,
    );
  }

  Future<void> signIn(String token) async {
    await _store.write(token);
    _set(SessionStatus.authenticated);
  }

  Future<void> signOut() async {
    await _store.clear();
    _set(SessionStatus.unauthenticated);
  }

  Future<void> markExpired() async {
    if (_status != SessionStatus.authenticated) return;
    await _store.clear();
    _set(SessionStatus.expired);
  }

  SessionStatus _set(SessionStatus next) {
    _status = next;
    if (!_controller.isClosed) _controller.add(next);
    return next;
  }

  void dispose() => _controller.close();
}
