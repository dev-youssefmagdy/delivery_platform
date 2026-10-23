import 'dart:io';

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import '../enums/form_field_type.dart';
import '../ui/tokens.dart';
import 'dynamic_form_strings.dart';
import 'form_logic.dart';
import 'form_schema.dart';
import 'photo_field_handler.dart';
import '../ui/isolated_text.dart';

/// Builds the control of one field. Each [FormFieldType] has its own widget so
/// the renderer itself stays a thin loop (no per-service branching anywhere).
class FormFieldView extends StatelessWidget {
  const FormFieldView({
    super.key,
    required this.field,
    required this.value,
    required this.error,
    required this.languageCode,
    required this.strings,
    required this.onChanged,
    this.photoHandler,
  });

  final FormFieldSchema field;
  final Object? value;
  final String? error;
  final String languageCode;
  final DynamicFormStrings strings;
  final ValueChanged<Object?> onChanged;
  final PhotoFieldHandler? photoHandler;

  String get _label {
    final text = field.label.resolve(languageCode);
    return field.required ? '$text *' : text;
  }

  InputDecoration get _decoration => InputDecoration(
    labelText: _label,
    helperText: field.help.resolveOrNull(languageCode),
    helperMaxLines: 3,
    errorText: error,
    errorMaxLines: 3,
  );

  @override
  Widget build(BuildContext context) {
    final raw = value;
    final map = raw is Map ? raw : const <dynamic, dynamic>{};
    final list = raw is List ? raw : const <dynamic>[];
    switch (field.type) {
      case FormFieldType.text:
      case FormFieldType.textarea:
      case FormFieldType.integer:
      case FormFieldType.decimal:
      case FormFieldType.weight:
        return _TextAnswer(
          key: ValueKey('${field.key}-input'),
          field: field,
          value: value,
          decoration: _decoration.copyWith(
            suffixText: field.type == FormFieldType.weight
                ? strings.kilograms
                : null,
          ),
          onChanged: onChanged,
        );
      case FormFieldType.boolean:
        return _BooleanAnswer(
          field: field,
          value: value as bool?,
          decoration: _decoration,
          strings: strings,
          onChanged: onChanged,
        );
      case FormFieldType.select:
        return InputDecorator(
          decoration: _decoration,
          child: DropdownButtonHideUnderline(
            child: DropdownButton<String>(
              key: ValueKey('${field.key}-dropdown'),
              isExpanded: true,
              value: field.optionFor('$value') == null ? null : '$value',
              hint: Text(strings.choose),
              items: [
                for (final o in field.options)
                  DropdownMenuItem(
                    value: o.value,
                    child: Text(o.label.resolve(languageCode)),
                  ),
              ],
              onChanged: onChanged,
            ),
          ),
        );
      case FormFieldType.multiselect:
        final selected = {...((value as List?)?.map((e) => '$e') ?? const [])};
        return InputDecorator(
          decoration: _decoration,
          child: Wrap(
            spacing: AppSpacing.sm,
            runSpacing: AppSpacing.xs,
            children: [
              for (final o in field.options)
                FilterChip(
                  key: ValueKey('${field.key}-chip-${o.value}'),
                  label: Text(o.label.resolve(languageCode)),
                  selected: selected.contains(o.value),
                  onSelected: (on) {
                    final next = [
                      for (final x in field.options)
                        if (x.value == o.value
                            ? on
                            : selected.contains(x.value))
                          x.value,
                    ];
                    onChanged(next);
                  },
                ),
            ],
          ),
        );
      case FormFieldType.date:
      case FormFieldType.time:
      case FormFieldType.datetime:
        return _DateTimeAnswer(
          field: field,
          value: value as String?,
          decoration: _decoration,
          strings: strings,
          onChanged: onChanged,
        );
      case FormFieldType.contact:
        return _ContactAnswer(
          field: field,
          value: map,
          decoration: _decoration,
          strings: strings,
          onChanged: onChanged,
        );
      case FormFieldType.photo:
        return _PhotoAnswer(
          field: field,
          value: (value as List?)?.map((e) => '$e').toList() ?? const [],
          decoration: _decoration,
          strings: strings,
          handler: photoHandler,
          onChanged: onChanged,
        );
      case FormFieldType.itemChecklist:
        return _ChecklistAnswer(
          field: field,
          value: list,
          decoration: _decoration,
          strings: strings,
          languageCode: languageCode,
          onChanged: onChanged,
        );
      case FormFieldType.dimensions:
        return _DimensionsAnswer(
          field: field,
          value: map,
          decoration: _decoration,
          strings: strings,
          onChanged: onChanged,
        );
      case FormFieldType.unknown:
        return const SizedBox.shrink();
    }
  }
}

/// Text and numeric answers. Keeps the typed text; numbers are reported as
/// numbers when they parse and as the raw text otherwise (so validation can
/// say "not a number" instead of silently dropping the input).
class _TextAnswer extends StatefulWidget {
  const _TextAnswer({
    super.key,
    required this.field,
    required this.value,
    required this.decoration,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final Object? value;
  final InputDecoration decoration;
  final ValueChanged<Object?> onChanged;

  @override
  State<_TextAnswer> createState() => _TextAnswerState();
}

class _TextAnswerState extends State<_TextAnswer> {
  late final TextEditingController _controller = TextEditingController(
    text: widget.value == null ? '' : '${widget.value}',
  );

  bool get _numeric => const {
    FormFieldType.integer,
    FormFieldType.decimal,
    FormFieldType.weight,
  }.contains(widget.field.type);

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  void _changed(String text) {
    if (text.trim().isEmpty) return widget.onChanged(null);
    if (!_numeric) return widget.onChanged(text);
    final number = FormLogic.parseNumber(text);
    final whole = widget.field.type == FormFieldType.integer;
    widget.onChanged(
      number == null
          ? text
          : (whole && number == number.truncate() ? number.toInt() : number),
    );
  }

  @override
  Widget build(BuildContext context) {
    final multiline = widget.field.type == FormFieldType.textarea;
    return TextField(
      controller: _controller,
      decoration: widget.decoration,
      minLines: multiline ? 3 : 1,
      maxLines: multiline ? 6 : 1,
      keyboardType: multiline
          ? TextInputType.multiline
          : (_numeric
                ? TextInputType.numberWithOptions(
                    decimal: widget.field.type != FormFieldType.integer,
                  )
                : TextInputType.text),
      textDirection: _numeric ? TextDirection.ltr : null,
      onChanged: _changed,
    );
  }
}

class _BooleanAnswer extends StatelessWidget {
  const _BooleanAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final bool? value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final ValueChanged<Object?> onChanged;

  @override
  Widget build(BuildContext context) => InputDecorator(
    decoration: decoration,
    child: SegmentedButton<bool>(
      key: ValueKey('${field.key}-segments'),
      emptySelectionAllowed: true,
      showSelectedIcon: false,
      segments: [
        ButtonSegment(value: true, label: Text(strings.yes)),
        ButtonSegment(value: false, label: Text(strings.no)),
      ],
      selected: value == null ? const {} : {value!},
      onSelectionChanged: (s) => onChanged(s.isEmpty ? null : s.first),
    ),
  );
}

class _DateTimeAnswer extends StatelessWidget {
  const _DateTimeAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final String? value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final ValueChanged<Object?> onChanged;

  static String _two(int n) => n.toString().padLeft(2, '0');

  Future<void> _pick(BuildContext context) async {
    final now = DateTime.now();
    final type = field.type;
    String? date;
    String? time;
    if (type != FormFieldType.time) {
      final picked = await showDatePicker(
        context: context,
        helpText: strings.pickDate,
        initialDate: now,
        firstDate: DateTime(now.year - 1),
        lastDate: DateTime(now.year + 5),
      );
      if (picked == null) return;
      date =
          '${picked.year.toString().padLeft(4, '0')}-${_two(picked.month)}-${_two(picked.day)}';
    }
    if (type != FormFieldType.date) {
      if (!context.mounted) return;
      final picked = await showTimePicker(
        context: context,
        helpText: strings.pickTime,
        initialTime: TimeOfDay.fromDateTime(now),
      );
      if (picked == null) return;
      time = '${_two(picked.hour)}:${_two(picked.minute)}';
    }
    onChanged(switch (type) {
      FormFieldType.date => date,
      FormFieldType.time => time,
      _ => '${date}T$time',
    });
  }

  @override
  Widget build(BuildContext context) => InkWell(
    key: ValueKey('${field.key}-field'),
    borderRadius: BorderRadius.circular(AppRadius.sm),
    onTap: () => _pick(context),
    child: InputDecorator(
      decoration: decoration.copyWith(
        suffixIcon: Icon(
          field.type == FormFieldType.time
              ? Icons.schedule
              : Icons.calendar_today_outlined,
        ),
      ),
      child: IsolatedText(
        value == null ? '' : value!.replaceFirst('T', ' '),
        textAlign: TextAlign.start),
    ),
  );
}

class _ContactAnswer extends StatefulWidget {
  const _ContactAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final Map<dynamic, dynamic> value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final ValueChanged<Object?> onChanged;

  @override
  State<_ContactAnswer> createState() => _ContactAnswerState();
}

class _ContactAnswerState extends State<_ContactAnswer> {
  late final _name = TextEditingController(
    text: '${widget.value['name'] ?? ''}',
  );
  late final _phone = TextEditingController(
    text: '${widget.value['phone'] ?? ''}',
  );

  @override
  void dispose() {
    _name.dispose();
    _phone.dispose();
    super.dispose();
  }

  void _changed() =>
      widget.onChanged({'name': _name.text, 'phone': _phone.text});

  @override
  Widget build(BuildContext context) => InputDecorator(
    decoration: widget.decoration,
    child: Column(
      children: [
        TextField(
          key: ValueKey('${widget.field.key}-name'),
          controller: _name,
          decoration: InputDecoration(labelText: widget.strings.contactName),
          onChanged: (_) => _changed(),
        ),
        const SizedBox(height: AppSpacing.sm),
        TextField(
          key: ValueKey('${widget.field.key}-phone'),
          controller: _phone,
          decoration: InputDecoration(labelText: widget.strings.contactPhone),
          keyboardType: TextInputType.phone,
          textDirection: TextDirection.ltr,
          inputFormatters: [
            FilteringTextInputFormatter.allow(RegExp(r'[0-9٠-٩+\s\-()]')),
          ],
          onChanged: (_) => _changed(),
        ),
      ],
    ),
  );
}

class _DimensionsAnswer extends StatefulWidget {
  const _DimensionsAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final Map<dynamic, dynamic> value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final ValueChanged<Object?> onChanged;

  @override
  State<_DimensionsAnswer> createState() => _DimensionsAnswerState();
}

class _DimensionsAnswerState extends State<_DimensionsAnswer> {
  late final Map<String, TextEditingController> _controllers = {
    for (final side in FormLogic.dimensionSides)
      side: TextEditingController(text: '${widget.value[side] ?? ''}'),
  };

  @override
  void dispose() {
    for (final c in _controllers.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _changed() {
    final result = <String, Object?>{};
    for (final side in FormLogic.dimensionSides) {
      final text = _controllers[side]!.text;
      result[side] = text.trim().isEmpty
          ? null
          : (FormLogic.parseNumber(text) ?? text);
    }
    widget.onChanged(result);
  }

  @override
  Widget build(BuildContext context) {
    final labels = {
      'length': widget.strings.length,
      'width': widget.strings.width,
      'height': widget.strings.height,
    };
    return InputDecorator(
      decoration: widget.decoration,
      child: Row(
        children: [
          for (final side in FormLogic.dimensionSides) ...[
            if (side != 'length') const SizedBox(width: AppSpacing.sm),
            Expanded(
              child: TextField(
                key: ValueKey('${widget.field.key}-$side'),
                controller: _controllers[side],
                decoration: InputDecoration(labelText: labels[side]),
                keyboardType: const TextInputType.numberWithOptions(
                  decimal: true,
                ),
                textDirection: TextDirection.ltr,
                onChanged: (_) => _changed(),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _PhotoAnswer extends StatelessWidget {
  const _PhotoAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.handler,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final List<String> value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final PhotoFieldHandler? handler;
  final ValueChanged<Object?> onChanged;

  @override
  Widget build(BuildContext context) {
    final max = (field.max ?? FormLogic.defaultPhotoMax).toInt();
    return InputDecorator(
      decoration: decoration,
      child: Wrap(
        spacing: AppSpacing.sm,
        runSpacing: AppSpacing.sm,
        children: [
          for (final ref in value)
            Stack(
              key: ValueKey('${field.key}-photo-$ref'),
              children: [
                ClipRRect(
                  borderRadius: BorderRadius.circular(AppRadius.sm),
                  child: Image.file(
                    File((handler is PhotoPreviewSource ? (handler! as PhotoPreviewSource).localPathOf(ref) : null) ?? ref),
                    width: 72,
                    height: 72,
                    fit: BoxFit.cover,
                    errorBuilder: (_, _, _) => const SizedBox.square(
                      dimension: 72,
                      child: Icon(Icons.image_outlined),
                    ),
                  ),
                ),
                PositionedDirectional(
                  top: 0,
                  end: 0,
                  child: Semantics(
                    label: strings.removePhoto,
                    button: true,
                    child: InkResponse(
                      key: ValueKey('${field.key}-remove-$ref'),
                      onTap: () =>
                          onChanged(value.where((x) => x != ref).toList()),
                      child: const CircleAvatar(
                        radius: 12,
                        child: Icon(Icons.close, size: 14),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          if (value.length < max && handler != null)
            OutlinedButton.icon(
              key: ValueKey('${field.key}-add'),
              onPressed: () async {
                final picked = await handler!.pick(context);
                if (picked != null) onChanged([...value, picked]);
              },
              icon: const Icon(Icons.add_a_photo_outlined),
              label: Text(strings.addPhoto),
            ),
        ],
      ),
    );
  }
}

class _ChecklistAnswer extends StatelessWidget {
  const _ChecklistAnswer({
    required this.field,
    required this.value,
    required this.decoration,
    required this.strings,
    required this.languageCode,
    required this.onChanged,
  });

  final FormFieldSchema field;
  final List<dynamic> value;
  final InputDecoration decoration;
  final DynamicFormStrings strings;
  final String languageCode;
  final ValueChanged<Object?> onChanged;

  /// item code -> the row as typed (quantity plus optional note, weight and
  /// dimensions).
  Map<String, Map<String, Object?>> get _rows => {
    for (final e in value)
      if (e is Map)
        '${e['item']}': {
          for (final entry in e.entries) '${entry.key}': entry.value,
          'quantity': e['quantity'] is int
              ? e['quantity'] as int
              : (FormLogic.parseNumber('${e['quantity']}')?.toInt() ?? 1),
        },
  };

  void _emit(Map<String, Map<String, Object?>> rows) => onChanged([
    for (final o in field.options)
      if (rows.containsKey(o.value)) {...rows[o.value]!, 'item': o.value},
  ]);

  bool get _hasDetails =>
      strings.itemNote != null &&
      strings.itemWeight != null &&
      strings.itemDimensions != null;

  @override
  Widget build(BuildContext context) {
    final rows = _rows;
    int quantityOf(String code) => rows[code]!['quantity']! as int;
    return InputDecorator(
      decoration: decoration,
      child: Column(
        children: [
          for (final o in field.options) ...[
            Row(
              children: [
                Expanded(
                  child: CheckboxListTile(
                    key: ValueKey('${field.key}-item-${o.value}'),
                    contentPadding: EdgeInsets.zero,
                    controlAffinity: ListTileControlAffinity.leading,
                    dense: true,
                    title: Text(o.label.resolve(languageCode)),
                    value: rows.containsKey(o.value),
                    onChanged: (on) {
                      final next = {...rows};
                      on == true
                          ? next[o.value] = {'quantity': 1}
                          : next.remove(o.value);
                      _emit(next);
                    },
                  ),
                ),
                if (rows.containsKey(o.value))
                  Semantics(
                    label: strings.quantity,
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        IconButton(
                          key: ValueKey('${field.key}-minus-${o.value}'),
                          tooltip: '${strings.quantity} -',
                          icon: const Icon(Icons.remove_circle_outline),
                          onPressed: quantityOf(o.value) > 1
                              ? () => _emit({
                                  ...rows,
                                  o.value: {
                                    ...rows[o.value]!,
                                    'quantity': quantityOf(o.value) - 1,
                                  },
                                })
                              : null,
                        ),
                        Text(
                          '${quantityOf(o.value)}',
                          key: ValueKey('${field.key}-qty-${o.value}'),
                        ),
                        IconButton(
                          key: ValueKey('${field.key}-plus-${o.value}'),
                          tooltip: '${strings.quantity} +',
                          icon: const Icon(Icons.add_circle_outline),
                          onPressed:
                              quantityOf(o.value) < FormLogic.quantityMax
                              ? () => _emit({
                                  ...rows,
                                  o.value: {
                                    ...rows[o.value]!,
                                    'quantity': quantityOf(o.value) + 1,
                                  },
                                })
                              : null,
                        ),
                      ],
                    ),
                  ),
              ],
            ),
            if (_hasDetails && rows.containsKey(o.value))
              _ItemDetails(
                key: ValueKey('${field.key}-details-${o.value}'),
                keyPrefix: '${field.key}-${o.value}',
                row: rows[o.value]!,
                strings: strings,
                onChanged: (row) => _emit({...rows, o.value: row}),
              ),
          ],
        ],
      ),
    );
  }
}

/// Optional facts of one checked item: a note, its weight and its size in cm
/// (they feed the volume and the weight the drivers see).
class _ItemDetails extends StatefulWidget {
  const _ItemDetails({
    super.key,
    required this.keyPrefix,
    required this.row,
    required this.strings,
    required this.onChanged,
  });

  final String keyPrefix;
  final Map<String, Object?> row;
  final DynamicFormStrings strings;
  final ValueChanged<Map<String, Object?>> onChanged;

  @override
  State<_ItemDetails> createState() => _ItemDetailsState();
}

class _ItemDetailsState extends State<_ItemDetails> {
  late final _note = TextEditingController(text: '${widget.row['note'] ?? ''}');
  late final _weight = TextEditingController(
    text: '${widget.row['weight_kg'] ?? ''}',
  );
  late final Map<String, TextEditingController> _dims = {
    for (final side in FormLogic.dimensionSides)
      side: TextEditingController(
        text: '${(widget.row['dimensions'] as Map?)?[side] ?? ''}',
      ),
  };

  @override
  void dispose() {
    _note.dispose();
    _weight.dispose();
    for (final c in _dims.values) {
      c.dispose();
    }
    super.dispose();
  }

  void _changed() {
    final row = <String, Object?>{'quantity': widget.row['quantity']};
    final note = _note.text.trim();
    if (note.isNotEmpty) row['note'] = note;
    final weight = FormLogic.parseNumber(_weight.text);
    if (_weight.text.trim().isNotEmpty) row['weight_kg'] = weight ?? _weight.text;
    final dims = {
      for (final side in FormLogic.dimensionSides)
        side: FormLogic.parseNumber(_dims[side]!.text),
    };
    if (dims.values.any((v) => v != null)) row['dimensions'] = dims;
    widget.onChanged(row);
  }

  @override
  Widget build(BuildContext context) {
    final s = widget.strings;
    final labels = {'length': s.length, 'width': s.width, 'height': s.height};
    return Padding(
      padding: const EdgeInsetsDirectional.only(
        start: AppSpacing.lg,
        bottom: AppSpacing.sm,
      ),
      child: Column(
        children: [
          TextField(
            key: ValueKey('${widget.keyPrefix}-note'),
            controller: _note,
            maxLength: 255,
            decoration: InputDecoration(
              labelText: s.itemNote,
              counterText: '',
              isDense: true,
            ),
            onChanged: (_) => _changed(),
          ),
          const SizedBox(height: AppSpacing.xs),
          TextField(
            key: ValueKey('${widget.keyPrefix}-weight'),
            controller: _weight,
            decoration: InputDecoration(
              labelText: s.itemWeight,
              suffixText: s.kilograms,
              isDense: true,
            ),
            keyboardType: const TextInputType.numberWithOptions(decimal: true),
            textDirection: TextDirection.ltr,
            onChanged: (_) => _changed(),
          ),
          const SizedBox(height: AppSpacing.xs),
          Row(
            children: [
              for (final side in FormLogic.dimensionSides) ...[
                if (side != 'length') const SizedBox(width: AppSpacing.xs),
                Expanded(
                  child: TextField(
                    key: ValueKey('${widget.keyPrefix}-$side'),
                    controller: _dims[side],
                    decoration: InputDecoration(
                      labelText: '${labels[side]} (${s.itemDimensions})',
                      isDense: true,
                    ),
                    keyboardType: const TextInputType.numberWithOptions(
                      decimal: true,
                    ),
                    textDirection: TextDirection.ltr,
                    onChanged: (_) => _changed(),
                  ),
                ),
              ],
            ],
          ),
        ],
      ),
    );
  }
}
