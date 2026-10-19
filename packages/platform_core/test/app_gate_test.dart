import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  group('parseAppVersion', () {
    test('accepts full, short and decorated versions', () {
      expect(parseAppVersion('1.2.3'), [1, 2, 3]);
      expect(parseAppVersion('2.0'), [2, 0, 0]);
      expect(parseAppVersion('7'), [7, 0, 0]);
      expect(parseAppVersion('1.2.3+45'), [1, 2, 3]);
      expect(parseAppVersion('1.2.3-beta.1'), [1, 2, 3]);
      expect(parseAppVersion(' 1.0.0 '), [1, 0, 0]);
    });

    test('rejects anything else', () {
      expect(parseAppVersion(null), isNull);
      expect(parseAppVersion(''), isNull);
      expect(parseAppVersion('abc'), isNull);
      expect(parseAppVersion('1.2.3.4'), isNull);
      expect(parseAppVersion('v1.2.3'), isNull);
    });
  });

  group('compareAppVersions', () {
    test('compares numerically, not lexically', () {
      expect(compareAppVersions('1.2.3', '1.2.3'), 0);
      expect(compareAppVersions('1.2.3', '1.10.0')! < 0, isTrue);
      expect(compareAppVersions('2.0.0', '1.99.99')! > 0, isTrue);
      expect(compareAppVersions('1.0', '1.0.0'), 0);
      expect(compareAppVersions('1.0.0+9', '1.0.0'), 0);
    });

    test('is null when either side is not a version', () {
      expect(compareAppVersions('x', '1.0.0'), isNull);
      expect(compareAppVersions('1.0.0', null), isNull);
    });
  });

  group('evaluateAppGate', () {
    AppGateStatus gate({
      bool maintenance = false,
      String? min = '2.0.0',
      bool force = true,
      String? current = '1.0.0',
    }) => evaluateAppGate(
      maintenance: maintenance,
      minVersion: min,
      forceUpdate: force,
      currentVersion: current,
    );

    test('forced update below the minimum', () {
      expect(gate(), AppGateStatus.updateRequired);
    });

    test('open at or above the minimum', () {
      expect(gate(current: '2.0.0'), AppGateStatus.open);
      expect(gate(current: '2.0.1'), AppGateStatus.open);
    });

    test('below the minimum is allowed when the update is not forced', () {
      expect(gate(force: false), AppGateStatus.open);
    });

    test('maintenance wins over a forced update', () {
      expect(gate(maintenance: true), AppGateStatus.maintenance);
      expect(gate(maintenance: true, force: false), AppGateStatus.maintenance);
    });

    test('bad data never locks users out', () {
      expect(gate(min: 'garbage'), AppGateStatus.open);
      expect(gate(current: null), AppGateStatus.open);
      expect(gate(min: null), AppGateStatus.open);
    });
  });
}
