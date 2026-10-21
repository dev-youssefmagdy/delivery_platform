import 'dart:async';

import 'package:flutter/material.dart';

/// Rebuilds every second with the time left until [until] (never negative).
/// [builder] gets [Duration.zero] and `expired == true` once it has passed.
class Countdown extends StatefulWidget {
  const Countdown({
    super.key,
    required this.until,
    required this.builder,
    this.now,
  });

  final DateTime until;
  final Widget Function(BuildContext context, Duration remaining, bool expired)
  builder;

  /// Clock override for tests.
  final DateTime Function()? now;

  @override
  State<Countdown> createState() => _CountdownState();
}

class _CountdownState extends State<Countdown> {
  Timer? _timer;

  DateTime _now() => (widget.now ?? DateTime.now)();

  @override
  void initState() {
    super.initState();
    _timer = Timer.periodic(const Duration(seconds: 1), (_) {
      if (mounted) setState(() {});
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final left = widget.until.difference(_now());
    final expired = left <= Duration.zero;
    return widget.builder(context, expired ? Duration.zero : left, expired);
  }
}
