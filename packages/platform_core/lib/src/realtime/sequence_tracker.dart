/// Detects missed realtime events using the per-channel `sequence` counter
/// (docs/ARCHITECTURE; Plan §6.2: events are hints, refetch on a gap).
class SequenceTracker {
  final _last = <String, int>{};

  /// Records [sequence] for [key] (e.g. a booking id) and returns true when a
  /// refetch of the snapshot is required: a gap, or an unseen first event
  /// that is not sequence 1. Duplicates/old events return false.
  bool needsRefetch(String key, int sequence) {
    final previous = _last[key];
    if (previous != null && sequence <= previous) return false;
    _last[key] = sequence;
    if (previous == null) return sequence != 1;
    return sequence != previous + 1;
  }

  /// Call after a snapshot refetch to resync to the snapshot's sequence.
  void resync(String key, int sequence) => _last[key] = sequence;

  /// Forget everything (reconnect: always refetch).
  void reset() => _last.clear();
}
