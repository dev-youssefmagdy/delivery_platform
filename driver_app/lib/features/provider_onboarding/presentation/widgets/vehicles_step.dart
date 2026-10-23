import 'package:flutter/material.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import 'onboarding_labels.dart';

/// Step 3: the driver's vehicles. Each opens the editor (details, photos,
/// documents, submit for review).
class VehiclesStep extends StatelessWidget {
  const VehiclesStep({
    super.key,
    required this.application,
    required this.onOpenVehicle,
    required this.onNext,
  });

  final ProviderApplication application;

  /// `null` opens the editor for a new vehicle.
  final void Function(String? vehicleId) onOpenVehicle;
  final VoidCallback onNext;

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final vehicles = application.vehicles;

    return ListView(
      padding: const EdgeInsetsDirectional.all(AppSpacing.md),
      children: [
        Text(
          l10n.obVehiclesTitle,
          style: Theme.of(context).textTheme.titleLarge,
        ),
        const SizedBox(height: AppSpacing.sm),
        Text(l10n.obVehiclesIntro),
        const SizedBox(height: AppSpacing.md),
        if (vehicles.isEmpty)
          Padding(
            padding: const EdgeInsetsDirectional.symmetric(
              vertical: AppSpacing.md,
            ),
            child: Text(l10n.obVehiclesEmpty, key: const Key('vehicles-empty')),
          ),
        for (final v in vehicles)
          Card(
            margin: const EdgeInsetsDirectional.only(bottom: AppSpacing.sm),
            child: ListTile(
              key: Key('vehicle-${v.id}'),
              minTileHeight: context.density.minTouchTarget,
              title: Text(v.title),
              subtitle: Text(
                v.rejectionReason == null
                    ? ltrIsolate(v.plate)
                    : '${ltrIsolate(v.plate)} · ${l10n.obVehicleRejectedReason(v.rejectionReason!)}'),
              trailing: StatusChip(
                label: v.status.label(l10n),
                tone: v.status.tone,
              ),
              onTap: () => onOpenVehicle(v.id),
            ),
          ),
        const SizedBox(height: AppSpacing.sm),
        AppButton(
          key: const Key('vehicle-add'),
          label: l10n.obVehicleAdd,
          icon: Icons.add,
          secondary: true,
          onPressed: () => onOpenVehicle(null),
        ),
        const SizedBox(height: AppSpacing.lg),
        AppButton(label: l10n.actionNext, onPressed: onNext),
      ],
    );
  }
}
