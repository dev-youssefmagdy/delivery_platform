import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';
import '../../../app_config/presentation/providers/app_config_providers.dart';
import '../../../catalog/presentation/providers/catalog_providers.dart';
import '../../domain/entities/booking_entities.dart';
import '../../domain/logic/fare_input.dart';
import '../providers/booking_providers.dart';
import 'booking_texts.dart';

/// Changes the proposed fare of a booking that is still searching. The server
/// invalidates the active offers; the sheet says so before saving, and never
/// promises that a higher fare brings offers.
Future<void> showEditFareSheet(BuildContext context, Booking booking) =>
    showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      showDragHandle: true,
      builder: (_) => EditFareSheet(booking: booking),
    );

class EditFareSheet extends ConsumerStatefulWidget {
  const EditFareSheet({super.key, required this.booking});

  final Booking booking;

  @override
  ConsumerState<EditFareSheet> createState() => _EditFareSheetState();
}

class _EditFareSheetState extends ConsumerState<EditFareSheet> {
  late final TextEditingController _controller;
  bool _busy = false;
  String? _error;

  int get _exponent => ref.read(appConfigProvider).value?.currencyExponent ?? 2;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(
      text: fareInputText(
        widget.booking.proposedFare.minor,
        exponent: widget.booking.proposedFare.exponent,
      ),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  Future<void> _save() async {
    final l10n = AppLocalizations.of(context);
    final category = ref
        .read(catalogProvider)
        .value
        ?.categoryById(widget.booking.serviceId);
    final minor = parseFareMinor(_controller.text, exponent: _exponent);
    final check = _controller.text.trim().isEmpty
        ? FareCheck.empty
        : checkFare(
            minor,
            minMinor: category?.minFareMinor,
            maxMinor: category?.maxFareMinor,
          );
    if (check != FareCheck.ok) {
      setState(
        () => _error = switch (check) {
          FareCheck.invalid => l10n.bkFareInvalid,
          FareCheck.belowMinimum => l10n.bkErrFareBelow,
          FareCheck.aboveMaximum => l10n.bkErrFareAbove,
          _ => l10n.bkFareRequired,
        },
      );
      return;
    }
    final navigator = Navigator.of(context);
    final messenger = ScaffoldMessenger.of(context);
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await ref
          .read(bookingActionsProvider)
          .changeFare(widget.booking.id, minor!);
      navigator.pop();
      messenger.showSnackBar(SnackBar(content: Text(l10n.bkFareUpdated)));
    } on Object catch (e) {
      if (!mounted) return;
      setState(() {
        _busy = false;
        _error = bookingErrorText(l10n, e);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final l10n = AppLocalizations.of(context);
    final currency = widget.booking.proposedFare.currency;
    return Padding(
      padding: EdgeInsetsDirectional.only(
        start: AppSpacing.md,
        end: AppSpacing.md,
        bottom: MediaQuery.viewInsetsOf(context).bottom + AppSpacing.md,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Text(
            l10n.bkEditFareTitle,
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            widget.booking.activeOfferCount > 0
                ? l10n.bkEditFareWarnOffers
                : l10n.bkEditFareWarn,
            key: const Key('edit-fare-warning'),
          ),
          const SizedBox(height: AppSpacing.sm),
          Text(
            l10n.bkEditFareNoGuarantee,
            style: Theme.of(context).textTheme.bodySmall,
          ),
          const SizedBox(height: AppSpacing.md),
          TextField(
            key: const Key('edit-fare-field'),
            controller: _controller,
            enabled: !_busy,
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            inputFormatters: [
              FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩.,٫]')),
            ],
            decoration: InputDecoration(
              labelText: l10n.bkFareLabel(currency),
              errorText: _error,
            ),
          ),
          const SizedBox(height: AppSpacing.md),
          AppButton(
            key: const Key('edit-fare-save'),
            label: l10n.bkEditFareSave,
            loading: _busy,
            onPressed: _save,
          ),
        ],
      ),
    );
  }
}
