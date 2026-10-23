import 'package:flutter/widgets.dart';

/// Text whose glyph order is forced left to right (money, plates, phone numbers, codes, masked card numbers) while its
/// alignment still follows the surrounding directionality. A plain `Text(textDirection: ltr)` also resolves
/// `TextAlign.start` to the physical left, which breaks full width columns in Arabic (P6.3).
class IsolatedText extends StatelessWidget {
  const IsolatedText(
    this.data, {
    super.key,
    this.style,
    this.textAlign,
    this.maxLines,
    this.overflow,
    this.softWrap,
    this.semanticsLabel,
  });

  final String data;
  final TextStyle? style;

  /// Defaults to the start edge of the ambient directionality.
  final TextAlign? textAlign;
  final int? maxLines;
  final TextOverflow? overflow;
  final bool? softWrap;
  final String? semanticsLabel;

  @override
  Widget build(BuildContext context) {
    final ambientRtl = Directionality.of(context) == TextDirection.rtl;
    return IsolatedText(
      data,
      style: style,
      textAlign: textAlign ?? (ambientRtl ? TextAlign.right : TextAlign.left),
      maxLines: maxLines,
      overflow: overflow,
      softWrap: softWrap,
      semanticsLabel: semanticsLabel,);
  }
}

/// Wraps [value] in Unicode left-to-right isolate marks so a Latin run (plate, phone, e-mail, masked card) keeps its own order inside
/// an Arabic sentence without changing the direction or alignment of the surrounding paragraph.
String ltrIsolate(String value) => '⁦$value⁩';
