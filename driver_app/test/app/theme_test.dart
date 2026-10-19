import 'package:driver_app/app/theme.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  test('driver theme uses the large-touch density profile', () {
    final density = buildAppTheme().extension<AppDensityExtension>()!.density;
    expect(density.minTouchTarget, 64);
    expect(density.buttonHeight, 64);
    expect(density.textScale, greaterThan(1));
  });
}
