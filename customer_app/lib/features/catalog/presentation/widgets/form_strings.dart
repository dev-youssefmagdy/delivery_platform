import 'package:platform_core/platform_core.dart';

import '../../../../app/l10n/generated/app_localizations.dart';

/// The dynamic form's texts from the app's ARB files.
DynamicFormStrings dynamicFormStrings(AppLocalizations l10n) =>
    DynamicFormStrings(
      requiredMessage: l10n.validationRequired,
      tooShort: l10n.formTooShort,
      tooLong: l10n.formTooLong,
      tooSmall: l10n.formTooSmall,
      tooLarge: l10n.formTooLarge,
      notANumber: l10n.formNotANumber,
      notAWholeNumber: l10n.formNotWhole,
      invalidChoice: l10n.formInvalidChoice,
      tooFewItems: l10n.formTooFewItems,
      tooManyItems: l10n.formTooManyItems,
      invalidPhone: l10n.formInvalidPhone,
      invalidDate: l10n.formInvalidDate,
      incomplete: l10n.formIncomplete,
      yes: l10n.formYes,
      no: l10n.formNo,
      choose: l10n.formChoose,
      contactName: l10n.formContactName,
      contactPhone: l10n.formContactPhone,
      length: l10n.formLength,
      width: l10n.formWidth,
      height: l10n.formHeight,
      kilograms: l10n.formKilograms,
      quantity: l10n.formQuantity,
      addPhoto: l10n.formAddPhoto,
      removePhoto: l10n.formRemovePhoto,
      photoCamera: l10n.formPhotoCamera,
      photoGallery: l10n.formPhotoGallery,
      pickDate: l10n.formPickDate,
      pickTime: l10n.formPickTime,
      itemNote: l10n.lfItemNote,
      itemWeight: l10n.lfItemWeight,
      itemDimensions: l10n.lfItemDimensionsUnit,
    );
