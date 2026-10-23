import 'dart:typed_data';

import 'package:flutter/material.dart';

import '../../ui/tokens.dart';

/// An image behind a signed URL that still needs the person's token: it is
/// downloaded through the app's [ApiClient] (via [load]) instead of
/// `Image.network`. Shows a spinner, an error placeholder and opens full size on tap.
class SecureImage extends StatefulWidget {
  const SecureImage({
    super.key,
    required this.cacheKey,
    required this.load,
    required this.errorLabel,
    this.size = 160,
  });

  /// Stable id of the image (the URL changes on every request; the message id does not).
  final String cacheKey;
  final Future<Uint8List> Function() load;
  final String errorLabel;
  final double size;

  @override
  State<SecureImage> createState() => _SecureImageState();
}

class _SecureImageState extends State<SecureImage> {
  static final _cache = <String, Uint8List>{};
  late Future<Uint8List> _future = _fetch();

  Future<Uint8List> _fetch() async {
    final cached = _cache[widget.cacheKey];
    if (cached != null) return cached;
    final bytes = await widget.load();
    _cache[widget.cacheKey] = bytes;
    return bytes;
  }

  @override
  Widget build(BuildContext context) => FutureBuilder<Uint8List>(
    future: _future,
    builder: (context, snapshot) {
      final box = widget.size;
      if (snapshot.hasError) {
        return InkWell(
          onTap: () => setState(() => _future = _fetch()),
          child: SizedBox.square(
            dimension: box,
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.broken_image_outlined),
                  Text(
                    widget.errorLabel,
                    style: Theme.of(context).textTheme.labelSmall,
                    textAlign: TextAlign.center,
                  ),
                ],
              ),
            ),
          ),
        );
      }
      final bytes = snapshot.data;
      if (bytes == null) {
        return SizedBox.square(
          dimension: box,
          child: const Center(child: CircularProgressIndicator(strokeWidth: 2)),
        );
      }
      return GestureDetector(
        onTap: () => showDialog<void>(
          context: context,
          builder: (_) => Dialog(
            child: InteractiveViewer(
              child: Image.memory(bytes, fit: BoxFit.contain),
            ),
          ),
        ),
        child: ClipRRect(
          borderRadius: BorderRadius.circular(AppRadius.sm),
          child: Image.memory(
            bytes,
            width: box,
            height: box,
            fit: BoxFit.cover,
            gaplessPlayback: true,
          ),
        ),
      );
    },
  );
}
