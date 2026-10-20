import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../providers/provider_onboarding_providers.dart';
import 'document_tile.dart';
import 'document_upload_screen.dart';
import 'onboarding_labels.dart';

/// Creates or edits one vehicle, manages its photos and documents and submits
/// it for review. A vehicle in review (or approved) is shown read-only.
class VehicleEditorScreen extends ConsumerStatefulWidget {
  const VehicleEditorScreen({super.key, this.vehicleId});

  final String? vehicleId;

  @override
  ConsumerState<VehicleEditorScreen> createState() =>
      _VehicleEditorScreenState();
}

class _VehicleEditorScreenState extends ConsumerState<VehicleEditorScreen> {
  final _make = TextEditingController();
  final _model = TextEditingController();
  final _year = TextEditingController();
  final _color = TextEditingController();
  final _plate = TextEditingController();
  final _passengers = TextEditingController();
  final _payload = TextEditingController();
  final _volume = TextEditingController();
  int? _categoryId;
  Set<VehicleCapability> _capabilities = {};
  String? _vehicleId;
  bool _loadedFields = false;
  bool _submitted = false;
  bool _busy = false;
  String? _error;
  // One idempotency key per intent, renewed after a success.
  String _createKey = newIdempotencyKey();
  String _submitKey = newIdempotencyKey();

  @override
  void initState() {
    super.initState();
    _vehicleId = widget.vehicleId;
  }

  @override
  void dispose() {
    for (final c in [
      _make,
      _model,
      _year,
      _color,
      _plate,
      _passengers,
      _payload,
      _volume,
    ]) {
      c.dispose();
    }
    super.dispose();
  }

  void _fill(ProviderVehicleInfo vehicle) {
    if (_loadedFields) return;
    _loadedFields = true;
    _make.text = vehicle.make;
    _model.text = vehicle.model;
    _year.text = '${vehicle.year}';
    _color.text = vehicle.color;
    _plate.text = vehicle.plate;
    _passengers.text = vehicle.passengerCapacity?.toString() ?? '';
    _payload.text = vehicle.payloadKg ?? '';
    _volume.text = vehicle.volumeM3 ?? '';
    _categoryId = vehicle.vehicleCategoryId;
    _capabilities = {
      for (final c in vehicle.capabilities)
        if (c != VehicleCapability.unknown) c,
    };
  }

  String? _required(AppLocalizations l10n, TextEditingController c) =>
      _submitted && c.text.trim().isEmpty ? l10n.validationRequired : null;

  VehicleDraft _draft() => VehicleDraft(
    vehicleCategoryId: _categoryId,
    make: _make.text.trim(),
    model: _model.text.trim(),
    year: int.tryParse(_year.text.trim()),
    color: _color.text.trim(),
    plate: _plate.text.trim(),
    passengerCapacity: int.tryParse(_passengers.text.trim()),
    payloadKg: _payload.text.trim().isEmpty ? null : _payload.text.trim(),
    volumeM3: _volume.text.trim().isEmpty ? null : _volume.text.trim(),
    capabilities: _capabilities.toList(),
  );

  Future<void> _run(Future<void> Function() action) async {
    final l10n = AppLocalizations.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await action();
    } on Object catch (e) {
      if (mounted) setState(() => _error = onboardingErrorText(l10n, e));
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _save() async {
    setState(() => _submitted = true);
    final complete =
        _categoryId != null &&
        [
          _make,
          _model,
          _year,
          _color,
          _plate,
        ].every((c) => c.text.trim().isNotEmpty);
    if (!complete) return;

    await _run(() async {
      final actions = ref.read(onboardingActionsProvider);
      if (_vehicleId == null) {
        final created = await actions.createVehicle(_draft(), _createKey);
        _createKey = newIdempotencyKey();
        if (mounted) setState(() => _vehicleId = created.id);
      } else {
        await actions.updateVehicle(_vehicleId!, _draft());
      }
    });
  }

  Future<void> _addPhoto() async {
    final l10n = AppLocalizations.of(context);
    final kind = await showModalBottomSheet<VehiclePhotoKind>(
      context: context,
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(title: Text(l10n.obVehiclePhotoKindTitle)),
            for (final k in [
              VehiclePhotoKind.front,
              VehiclePhotoKind.back,
              VehiclePhotoKind.side,
              VehiclePhotoKind.interior,
              VehiclePhotoKind.other,
            ])
              ListTile(
                title: Text(k.label(l10n)),
                onTap: () => Navigator.pop(ctx, k),
              ),
          ],
        ),
      ),
    );
    if (kind == null || !mounted) return;

    final handler = ref.read(photoPickerFactoryProvider)(
      cameraLabel: l10n.photoCamera,
      galleryLabel: l10n.photoGallery,
    );
    final path = await handler.pick(context);
    if (path == null || !mounted) return;

    await _run(
      () => ref
          .read(onboardingActionsProvider)
          .addVehiclePhoto(
            _vehicleId!,
            filePath: path,
            kind: kind,
            idempotencyKey: newIdempotencyKey(),
          ),
    );
  }

  Future<void> _submitVehicle() async {
    final l10n = AppLocalizations.of(context);
    await _run(() async {
      await ref
          .read(onboardingActionsProvider)
          .submitVehicle(_vehicleId!, _submitKey);
      _submitKey = newIdempotencyKey();
      if (!mounted) return;
      ScaffoldMessenger.of(context)
          .showSnackBar(SnackBar(content: Text(l10n.obVehicleSubmitted)));
      Navigator.of(context).pop();
    });
  }

  Future<void> _delete() async {
    final l10n = AppLocalizations.of(context);
    final ok = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(l10n.obVehicleDeleteTitle),
        content: Text(l10n.obVehicleDeleteBody),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: Text(l10n.actionCancel),
          ),
          TextButton(
            key: const Key('vehicle-delete-confirm'),
            onPressed: () => Navigator.pop(ctx, true),
            child: Text(l10n.actionDelete),
          ),
        ],
      ),
    );
    if (ok != true || !mounted) return;
    await _run(() async {
      await ref.read(onboardingActionsProvider).deleteVehicle(_vehicleId!);
      if (mounted) Navigator.of(context).pop();
    });
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final language = Localizations.localeOf(context).languageCode;
    final application = ref.watch(applicationProvider).value;
    final vehicle = _vehicleId == null
        ? null
        : application?.vehicleById(_vehicleId!);
    if (vehicle != null) _fill(vehicle);
    final editable = vehicle?.editable ?? _vehicleId == null;
    final categories = ref.watch(vehicleCategoriesProvider);
    final minPhotos = 2;

    return Scaffold(
      appBar: AppBar(title: Text(l10n.obVehicleFormTitle)),
      body: ListView(
        padding: const EdgeInsetsDirectional.all(AppSpacing.md),
        children: [
          if (vehicle != null)
            Wrap(
              spacing: AppSpacing.sm,
              children: [
                StatusChip(
                  label: vehicle.status.label(l10n),
                  tone: vehicle.status.tone,
                ),
              ],
            ),
          if (vehicle?.rejectionReason != null)
            Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
              child: Text(
                l10n.obVehicleRejectedReason(vehicle!.rejectionReason!),
                key: const Key('vehicle-rejection'),
                style: TextStyle(color: Theme.of(context).colorScheme.error),
              ),
            ),
          if (!editable)
            Padding(
              padding: const EdgeInsetsDirectional.only(top: AppSpacing.sm),
              child: Text(l10n.obVehicleLockedHint),
            ),
          const SizedBox(height: AppSpacing.md),
          categories.when(
            loading: () => const LinearProgressIndicator(),
            error: (e, _) => ErrorState(
              title: l10n.errorGenericTitle,
              retryLabel: l10n.actionRetry,
              onRetry: () => ref.invalidate(vehicleCategoriesProvider),
            ),
            data: (list) => DropdownButtonFormField<int>(
              key: const Key('vehicle-category'),
              initialValue: list.any((c) => c.id == _categoryId)
                  ? _categoryId
                  : null,
              decoration: InputDecoration(
                labelText: l10n.obVehicleCategory,
                hintText: l10n.obVehicleCategoryPick,
                errorText: _submitted && _categoryId == null
                    ? l10n.validationRequired
                    : null,
              ),
              items: [
                for (final c in list)
                  DropdownMenuItem(
                    value: c.id,
                    child: Text(c.name.resolve(language)),
                  ),
              ],
              onChanged: editable
                  ? (v) => setState(() => _categoryId = v)
                  : null,
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehicleMake,
            controller: _make,
            enabled: editable,
            errorText: _required(l10n, _make),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehicleModel,
            controller: _model,
            enabled: editable,
            errorText: _required(l10n, _model),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehicleYear,
            controller: _year,
            enabled: editable,
            keyboardType: TextInputType.number,
            textDirection: TextDirection.ltr,
            errorText: _required(l10n, _year),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehicleColor,
            controller: _color,
            enabled: editable,
            errorText: _required(l10n, _color),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehiclePlate,
            controller: _plate,
            enabled: editable,
            errorText: _required(l10n, _plate),
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehiclePassengers,
            controller: _passengers,
            enabled: editable,
            keyboardType: TextInputType.number,
            textDirection: TextDirection.ltr,
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehiclePayload,
            controller: _payload,
            enabled: editable,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            textDirection: TextDirection.ltr,
          ),
          const SizedBox(height: AppSpacing.md),
          AppTextField(
            label: l10n.obVehicleVolume,
            controller: _volume,
            enabled: editable,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            textDirection: TextDirection.ltr,
          ),
          const SizedBox(height: AppSpacing.md),
          Text(
            l10n.obVehicleCapabilities,
            style: Theme.of(context).textTheme.titleSmall,
          ),
          const SizedBox(height: AppSpacing.sm),
          Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.sm,
            children: [
              for (final c in VehicleCapability.values)
                if (c != VehicleCapability.unknown)
                  FilterChip(
                    label: Text(c.label(l10n)),
                    selected: _capabilities.contains(c),
                    onSelected: editable
                        ? (on) => setState(
                            () => on
                                ? _capabilities.add(c)
                                : _capabilities.remove(c),
                          )
                        : null,
                  ),
            ],
          ),
          if (editable) ...[
            const SizedBox(height: AppSpacing.lg),
            AppButton(
              key: const Key('vehicle-save'),
              label: l10n.obVehicleSave,
              loading: _busy,
              onPressed: _save,
            ),
          ],
          if (_error != null) ...[
            const SizedBox(height: AppSpacing.md),
            Text(
              _error!,
              key: const Key('vehicle-error'),
              style: TextStyle(color: Theme.of(context).colorScheme.error),
            ),
          ],
          if (vehicle != null) ...[
            const Divider(height: AppSpacing.xl * 1.5),
            Text(
              l10n.obVehiclePhotos,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            Text(
              l10n.obVehiclePhotosHint(minPhotos),
              style: Theme.of(context).textTheme.bodySmall,
            ),
            const SizedBox(height: AppSpacing.sm),
            Wrap(
              spacing: AppSpacing.sm,
              runSpacing: AppSpacing.sm,
              children: [
                for (final photo in vehicle.photos)
                  _PhotoThumb(
                    photo: photo,
                    removable: editable && !_busy,
                    kindLabel: photo.kind.label(l10n),
                    removeLabel: l10n.actionRemove,
                    onRemove: () => _run(
                      () => ref
                          .read(onboardingActionsProvider)
                          .removeVehiclePhoto(vehicle.id, photo.id),
                    ),
                  ),
                if (editable)
                  OutlinedButton.icon(
                    key: const Key('vehicle-add-photo'),
                    onPressed: _busy ? null : _addPhoto,
                    icon: const Icon(Icons.add_a_photo_outlined),
                    label: Text(l10n.obVehiclePhotoAdd),
                  ),
              ],
            ),
            const SizedBox(height: AppSpacing.lg),
            Text(
              l10n.obVehicleDocuments,
              style: Theme.of(context).textTheme.titleMedium,
            ),
            const SizedBox(height: AppSpacing.sm),
            for (final item in application!.documentsOfVehicle(vehicle.id))
              DocumentTile(
                item: item,
                languageCode: language,
                onUpload: () => Navigator.of(context).push(
                  MaterialPageRoute<bool>(
                    builder: (_) => DocumentUploadScreen(
                      type: item.type,
                      vehicleId: vehicle.id,
                    ),
                  ),
                ),
              ),
            if (editable) ...[
              const SizedBox(height: AppSpacing.lg),
              AppButton(
                key: const Key('vehicle-submit'),
                label: l10n.obVehicleSubmit,
                loading: _busy,
                onPressed: _submitVehicle,
              ),
              const SizedBox(height: AppSpacing.sm),
              if (vehicle.status == VehicleStatus.draft)
                AppButton(
                  key: const Key('vehicle-delete'),
                  label: l10n.actionDelete,
                  secondary: true,
                  onPressed: _busy ? null : _delete,
                ),
            ],
          ],
        ],
      ),
    );
  }
}

class _PhotoThumb extends StatelessWidget {
  const _PhotoThumb({
    required this.photo,
    required this.removable,
    required this.kindLabel,
    required this.removeLabel,
    required this.onRemove,
  });

  final VehiclePhotoInfo photo;
  final bool removable;
  final String kindLabel;
  final String removeLabel;
  final VoidCallback onRemove;

  @override
  Widget build(BuildContext context) => SizedBox(
    width: 96,
    child: Column(
      children: [
        Stack(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(AppRadius.sm),
              child: SizedBox.square(
                dimension: 96,
                child: photo.url == null
                    ? const Icon(Icons.image_outlined)
                    : Image.network(
                        photo.url!,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) =>
                            const Icon(Icons.broken_image_outlined),
                      ),
              ),
            ),
            if (removable)
              PositionedDirectional(
                top: 0,
                end: 0,
                child: Semantics(
                  label: removeLabel,
                  button: true,
                  child: InkResponse(
                    key: Key('photo-remove-${photo.id}'),
                    onTap: onRemove,
                    child: const CircleAvatar(
                      radius: 12,
                      child: Icon(Icons.close, size: 14),
                    ),
                  ),
                ),
              ),
          ],
        ),
        Text(kindLabel, style: Theme.of(context).textTheme.labelSmall),
      ],
    ),
  );
}
