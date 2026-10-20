import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../../app/router.dart';
import '../../../../core/async_state_view.dart';
import '../../domain/place_selection.dart';
import '../providers/map_providers.dart';
import '../widgets/geo_error_text.dart';
import 'map_screen_args.dart';
import 'place_search_screen.dart' show savedPlaceIcon;

/// Home / Work / other saved places: list, add (search or pin) and delete.
class SavedPlacesScreen extends ConsumerWidget {
  const SavedPlacesScreen({super.key});

  String _typeLabel(AppLocalizations l10n, SavedPlaceType type) =>
      switch (type) {
        SavedPlaceType.home => l10n.savedPlaceTypeHome,
        SavedPlaceType.work => l10n.savedPlaceTypeWork,
        _ => l10n.savedPlaceTypeOther,
      };

  Future<void> _add(BuildContext context, WidgetRef ref) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final selection = await context.push<PlaceSelection>(
      Routes.placeSearch,
      extra: PlaceSearchArgs(
        mode: PlaceSearchMode.savedPlace,
        near: ref.read(pickupPointProvider),
      ),
    );
    if (selection == null || !context.mounted) return;
    final details = await showDialog<_SavedPlaceForm>(
      context: context,
      builder: (_) => _SavedPlaceDialog(
        initialLabel: selection.title ?? '',
        typeLabel: (type) => _typeLabel(l10n, type),
      ),
    );
    if (details == null) return;
    try {
      await ref
          .read(savedPlacesProvider.notifier)
          .add(label: details.label, type: details.type, selection: selection);
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(geoErrorText(l10n, e))));
    }
  }

  Future<void> _delete(
    BuildContext context,
    WidgetRef ref,
    SavedPlaceInfo place,
  ) async {
    final l10n = AppLocalizations.of(context);
    final messenger = ScaffoldMessenger.of(context);
    final confirmed = await showConfirmSheet(
      context,
      title: l10n.savedPlaceDeleteTitle,
      message: l10n.savedPlaceDeleteBody,
      confirmLabel: l10n.savedPlaceDelete,
      cancelLabel: l10n.actionCancel,
      destructive: true,
    );
    if (!confirmed) return;
    try {
      await ref.read(savedPlacesProvider.notifier).remove(place.id);
    } on Object catch (e) {
      messenger.showSnackBar(SnackBar(content: Text(geoErrorText(l10n, e))));
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final l10n = AppLocalizations.of(context);
    return Scaffold(
      appBar: AppBar(title: Text(l10n.savedPlacesTitle)),
      floatingActionButton: FloatingActionButton.extended(
        key: const Key('saved-place-add'),
        onPressed: () => _add(context, ref),
        icon: const Icon(Icons.add_location_alt_outlined),
        label: Text(l10n.savedPlacesAdd),
      ),
      body: AsyncStateView<List<SavedPlaceInfo>>(
        value: ref.watch(savedPlacesProvider),
        onRetry: () => ref.invalidate(savedPlacesProvider),
        builder: (context, places) => places.isEmpty
            ? EmptyState(
                title: l10n.savedPlacesEmptyTitle,
                message: l10n.savedPlacesEmptyBody,
                icon: Icons.bookmarks_outlined,
              )
            : ListView(
                padding: const EdgeInsetsDirectional.only(bottom: 88),
                children: [
                  for (final place in places)
                    ListTile(
                      key: Key('saved-place-${place.id}'),
                      minTileHeight: context.density.minTouchTarget,
                      leading: Icon(savedPlaceIcon(place.type)),
                      title: Text(place.label),
                      subtitle: Text(
                        place.addressLine ?? _typeLabel(l10n, place.type),
                      ),
                      trailing: IconButton(
                        key: Key('saved-place-delete-${place.id}'),
                        tooltip: l10n.savedPlaceDelete,
                        icon: const Icon(Icons.delete_outline),
                        onPressed: () => _delete(context, ref, place),
                      ),
                    ),
                ],
              ),
      ),
    );
  }
}

class _SavedPlaceForm {
  const _SavedPlaceForm(this.label, this.type);

  final String label;
  final SavedPlaceType type;
}

class _SavedPlaceDialog extends StatefulWidget {
  const _SavedPlaceDialog({
    required this.initialLabel,
    required this.typeLabel,
  });

  final String initialLabel;
  final String Function(SavedPlaceType) typeLabel;

  @override
  State<_SavedPlaceDialog> createState() => _SavedPlaceDialogState();
}

class _SavedPlaceDialogState extends State<_SavedPlaceDialog> {
  late final _label = TextEditingController(text: widget.initialLabel);
  SavedPlaceType _type = SavedPlaceType.home;
  bool _invalid = false;

  @override
  void dispose() {
    _label.dispose();
    super.dispose();
  }

  void _submit() {
    final label = _label.text.trim();
    if (label.isEmpty) {
      setState(() => _invalid = true);
      return;
    }
    Navigator.of(context).pop(_SavedPlaceForm(label, _type));
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    return AlertDialog(
      title: Text(l10n.savedPlacesAdd),
      content: SingleChildScrollView(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            DropdownButtonFormField<SavedPlaceType>(
              key: const Key('saved-place-type'),
              initialValue: _type,
              decoration: InputDecoration(labelText: l10n.savedPlaceTypeField),
              items: [
                for (final type in const [
                  SavedPlaceType.home,
                  SavedPlaceType.work,
                  SavedPlaceType.other,
                ])
                  DropdownMenuItem(
                    value: type,
                    child: Text(widget.typeLabel(type)),
                  ),
              ],
              onChanged: (type) => setState(() => _type = type ?? _type),
            ),
            const SizedBox(height: AppSpacing.md),
            TextField(
              key: const Key('saved-place-name'),
              controller: _label,
              maxLength: 60,
              decoration: InputDecoration(
                labelText: l10n.savedPlaceNameField,
                errorText: _invalid ? l10n.savedPlaceNameRequired : null,
              ),
              onChanged: (_) {
                if (_invalid) setState(() => _invalid = false);
              },
            ),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.of(context).pop(),
          child: Text(l10n.actionCancel),
        ),
        FilledButton(
          key: const Key('saved-place-save'),
          onPressed: _submit,
          child: Text(l10n.savedPlaceSave),
        ),
      ],
    );
  }
}
