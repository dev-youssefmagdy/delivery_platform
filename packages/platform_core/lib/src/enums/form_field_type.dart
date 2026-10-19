import 'api_enum.dart';

/// Mirrors backend `FormFieldType`; unknown wire values map to [FormFieldType.unknown].
enum FormFieldType implements ApiEnum {
  text('text'),
  textarea('textarea'),
  integer('integer'),
  decimal('decimal'),
  boolean('boolean'),
  select('select'),
  multiselect('multiselect'),
  date('date'),
  time('time'),
  datetime('datetime'),
  contact('contact'),
  photo('photo'),
  itemChecklist('item_checklist'),
  dimensions('dimensions'),
  weight('weight'),
  unknown('');

  const FormFieldType(this.value);

  @override
  final String value;

  static FormFieldType parse(String? raw) =>
      parseApiEnum(FormFieldType.values, raw, FormFieldType.unknown);
}
