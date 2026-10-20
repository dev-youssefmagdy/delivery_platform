import 'form_logic.dart';

/// Every user-visible text of the dynamic form. The apps build this from
/// their ARB files, so platform_core itself holds no literal strings.
class DynamicFormStrings {
  const DynamicFormStrings({
    required this.requiredMessage,
    required this.tooShort,
    required this.tooLong,
    required this.tooSmall,
    required this.tooLarge,
    required this.notANumber,
    required this.notAWholeNumber,
    required this.invalidChoice,
    required this.tooFewItems,
    required this.tooManyItems,
    required this.invalidPhone,
    required this.invalidDate,
    required this.incomplete,
    required this.yes,
    required this.no,
    required this.choose,
    required this.contactName,
    required this.contactPhone,
    required this.length,
    required this.width,
    required this.height,
    required this.kilograms,
    required this.quantity,
    required this.addPhoto,
    required this.removePhoto,
    required this.photoCamera,
    required this.photoGallery,
    required this.pickDate,
    required this.pickTime,
  });

  final String requiredMessage;
  final String Function(num limit) tooShort;
  final String Function(num limit) tooLong;
  final String Function(num limit) tooSmall;
  final String Function(num limit) tooLarge;
  final String notANumber;
  final String notAWholeNumber;
  final String invalidChoice;
  final String Function(num limit) tooFewItems;
  final String Function(num limit) tooManyItems;
  final String invalidPhone;
  final String invalidDate;
  final String incomplete;
  final String yes;
  final String no;
  final String choose;
  final String contactName;
  final String contactPhone;
  final String length;
  final String width;
  final String height;
  final String kilograms;
  final String quantity;
  final String addPhoto;
  final String removePhoto;
  final String photoCamera;
  final String photoGallery;
  final String pickDate;
  final String pickTime;

  String messageFor(FormFieldIssue issue) {
    final limit = issue.limit ?? 0;
    return switch (issue.kind) {
      FormFieldIssueKind.required => requiredMessage,
      FormFieldIssueKind.tooShort => tooShort(limit),
      FormFieldIssueKind.tooLong => tooLong(limit),
      FormFieldIssueKind.tooSmall => tooSmall(limit),
      FormFieldIssueKind.tooLarge => tooLarge(limit),
      FormFieldIssueKind.notANumber => notANumber,
      FormFieldIssueKind.notAWholeNumber => notAWholeNumber,
      FormFieldIssueKind.invalidChoice => invalidChoice,
      FormFieldIssueKind.tooFewItems => tooFewItems(limit),
      FormFieldIssueKind.tooManyItems => tooManyItems(limit),
      FormFieldIssueKind.invalidPhone => invalidPhone,
      FormFieldIssueKind.invalidDate => invalidDate,
      FormFieldIssueKind.incomplete => incomplete,
    };
  }
}
