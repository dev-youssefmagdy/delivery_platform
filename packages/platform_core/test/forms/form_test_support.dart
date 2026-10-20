import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

DynamicFormStrings testStrings() => DynamicFormStrings(
  requiredMessage: 'required!',
  tooShort: (n) => 'min length $n',
  tooLong: (n) => 'max length $n',
  tooSmall: (n) => 'at least $n',
  tooLarge: (n) => 'at most $n',
  notANumber: 'not a number',
  notAWholeNumber: 'not whole',
  invalidChoice: 'bad choice',
  tooFewItems: (n) => 'pick at least $n',
  tooManyItems: (n) => 'pick at most $n',
  invalidPhone: 'bad phone',
  invalidDate: 'bad date',
  incomplete: 'incomplete',
  yes: 'Yes',
  no: 'No',
  choose: 'Choose',
  contactName: 'Name',
  contactPhone: 'Phone',
  length: 'Length',
  width: 'Width',
  height: 'Height',
  kilograms: 'kg',
  quantity: 'Quantity',
  addPhoto: 'Add photo',
  removePhoto: 'Remove photo',
  photoCamera: 'Camera',
  photoGallery: 'Gallery',
  pickDate: 'Pick date',
  pickTime: 'Pick time',
);

class FakePhotoHandler implements PhotoFieldHandler {
  FakePhotoHandler(this.results);
  final List<String> results;
  int calls = 0;

  @override
  Future<String?> pick(BuildContext context) async =>
      calls < results.length ? results[calls++] : null;
}

FormFieldSchema field(
  String key,
  FormFieldType type, {
  String? ar,
  String? en,
  bool required = false,
  num? min,
  num? max,
  List<(String, String, String)> options = const [],
  FormFieldVisibility? visibleIf,
  String? helpAr,
}) => FormFieldSchema(
  key: key,
  type: type,
  label: LocalizedText({'ar': ar ?? 'عنوان $key', 'en': en ?? 'Label $key'}),
  help: helpAr == null
      ? const LocalizedText({})
      : LocalizedText({'ar': helpAr}),
  required: required,
  min: min,
  max: max,
  options: [
    for (final o in options)
      FormFieldOption(
        value: o.$1,
        label: LocalizedText({'ar': o.$2, 'en': o.$3}),
      ),
  ],
  visibleIf: visibleIf,
);

/// Pumps the renderer in the given direction and returns the controller.
Future<DynamicFormController> pumpForm(
  WidgetTester tester,
  List<FormFieldSchema> fields, {
  required TextDirection dir,
  String? languageCode,
  PhotoFieldHandler? photoHandler,
  Map<String, Object?> initialValues = const {},
}) async {
  final controller = DynamicFormController(
    fields: fields,
    initialValues: initialValues,
  );
  addTearDown(controller.dispose);
  await tester.pumpWidget(
    MaterialApp(
      locale: Locale(dir == TextDirection.rtl ? 'ar' : 'en'),
      builder: (context, child) =>
          Directionality(textDirection: dir, child: child!),
      home: Scaffold(
        body: SingleChildScrollView(
          padding: const EdgeInsets.all(16),
          child: DynamicFormRenderer(
            controller: controller,
            languageCode:
                languageCode ?? (dir == TextDirection.rtl ? 'ar' : 'en'),
            strings: testStrings(),
            photoHandler: photoHandler,
          ),
        ),
      ),
    ),
  );
  return controller;
}

const dirs = [TextDirection.rtl, TextDirection.ltr];
