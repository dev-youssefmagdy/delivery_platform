import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

/// Picks one photo for a `photo` field and returns the value stored in the
/// answer: a local path, or a server reference when an [uploader] is given.
abstract interface class PhotoFieldHandler {
  Future<String?> pick(BuildContext context);
}

/// Camera / gallery sheet backed by `image_picker`.
class ImagePickerPhotoHandler implements PhotoFieldHandler {
  const ImagePickerPhotoHandler({
    required this.cameraLabel,
    required this.galleryLabel,
    this.uploader,
    this._picker,
  });

  final String cameraLabel;
  final String galleryLabel;

  /// Uploads the picked file and returns its server reference (attachments
  /// arrive with the courier/moving milestones, P4.x). Without it the local
  /// path is the value.
  final Future<String> Function(XFile file)? uploader;
  final ImagePicker? _picker;

  @override
  Future<String?> pick(BuildContext context) async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(cameraLabel),
              onTap: () => Navigator.pop(ctx, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(galleryLabel),
              onTap: () => Navigator.pop(ctx, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return null;
    final file = await (_picker ?? ImagePicker()).pickImage(
      source: source,
      imageQuality: 85,
      maxWidth: 2048,
    );
    if (file == null) return null;
    return uploader == null ? file.path : uploader!(file);
  }
}
