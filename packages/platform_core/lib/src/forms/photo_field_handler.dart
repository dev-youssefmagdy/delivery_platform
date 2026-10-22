import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import '../api/api_exception.dart';
import '../upload/attachment_repository.dart';

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

/// Lets a field show a local preview for a value that is not a path (an
/// uploaded attachment id).
abstract interface class PhotoPreviewSource {
  /// The local file behind [reference], or null when unknown (after a restart).
  String? localPathOf(String reference);
}

/// Photo / video field backed by `POST /bookings/attachments` (moving and
/// freight forms, docs/API.md P4.2): the picked file is uploaded at once and
/// the field stores the attachment id the booking later references. The upload
/// keeps one Idempotency-Key per file, so a retry after a timeout never stores
/// it twice. A refusal is reported through [onError]; nothing is stored.
class AttachmentPhotoHandler implements PhotoFieldHandler, PhotoPreviewSource {
  AttachmentPhotoHandler({
    required this.repository,
    required this.newKey,
    required this.cameraLabel,
    required this.galleryLabel,
    required this.videoLabel,
    required this.uploadingLabel,
    required this.onError,
    this.allowVideo = false,
    ImagePicker? picker,
  }) : _picker = picker ?? ImagePicker();

  final BookingAttachmentRepository repository;
  final String Function() newKey;
  final String cameraLabel;
  final String galleryLabel;
  final String videoLabel;
  final String uploadingLabel;
  final void Function(BuildContext context, Object error) onError;
  final bool allowVideo;
  final ImagePicker _picker;
  final _paths = <String, String>{};

  @override
  String? localPathOf(String reference) => _paths[reference];

  @override
  Future<String?> pick(BuildContext context) async {
    final choice = await showModalBottomSheet<_Choice>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: Text(cameraLabel),
              onTap: () => Navigator.pop(ctx, _Choice.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: Text(galleryLabel),
              onTap: () => Navigator.pop(ctx, _Choice.gallery),
            ),
            if (allowVideo)
              ListTile(
                leading: const Icon(Icons.videocam_outlined),
                title: Text(videoLabel),
                onTap: () => Navigator.pop(ctx, _Choice.video),
              ),
          ],
        ),
      ),
    );
    if (choice == null) return null;
    final XFile? file = switch (choice) {
      _Choice.camera => await _picker.pickImage(source: ImageSource.camera, imageQuality: 85, maxWidth: 2048),
      _Choice.gallery => await _picker.pickImage(source: ImageSource.gallery, imageQuality: 85, maxWidth: 2048),
      _Choice.video => await _picker.pickVideo(source: ImageSource.gallery, maxDuration: const Duration(minutes: 2)),
    };
    if (file == null || !context.mounted) return null;
    final messenger = ScaffoldMessenger.of(context);
    messenger
      ..hideCurrentSnackBar()
      ..showSnackBar(SnackBar(content: Text(uploadingLabel), duration: const Duration(minutes: 1)));
    final kind = choice == _Choice.video ? AttachmentKind.video : AttachmentKind.photo;
    try {
      final uploaded = await repository.upload(file.path, kind, newKey: newKey);
      _paths[uploaded.id] = file.path;
      return uploaded.id;
    } on ApiException catch (e) {
      // A transport failure keeps the key (the same file can be retried safely);
      // a refusal frees it.
      if (!e.isOffline && e.kind != ApiFailureKind.server) repository.forget(file.path, kind);
      if (context.mounted) onError(context, e);
      return null;
    } finally {
      messenger.hideCurrentSnackBar();
    }
  }
}

enum _Choice { camera, gallery, video }

/// Takes one picture with the camera (proofs, evidence). Returns the local
/// path, or null when the person cancelled or the camera is unavailable.
class CameraCapture {
  const CameraCapture({this.picker});

  final ImagePicker? picker;

  Future<String?> take() async {
    final file = await (picker ?? ImagePicker()).pickImage(
      source: ImageSource.camera,
      imageQuality: 85,
      maxWidth: 2048,
    );
    return file?.path;
  }
}
