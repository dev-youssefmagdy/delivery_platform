import 'package:flutter_test/flutter_test.dart';
import 'package:platform_core/platform_core.dart';

void main() {
  group('Money', () {
    const m = Money(minor: 123450, currency: 'EGP');

    test('arithmetic is exact and currency-safe', () {
      expect((m + const Money(minor: 50, currency: 'EGP')).minor, 123500);
      expect((m - m).isZero, isTrue);
      expect(
        () => m + const Money(minor: 1, currency: 'USD'),
        throwsArgumentError,
      );
    });

    test('fromJson / toJson roundtrip', () {
      final back = Money.fromJson(m.toJson());
      expect(back, m);
    });

    test('formats Arabic with Arabic-Indic digits and trailing symbol', () {
      const f = MoneyFormatter(languageCode: 'ar');
      expect(f.format(m), '١٬٢٣٤٫٥٠ ج.م');
    });

    test('formats English with leading symbol', () {
      const f = MoneyFormatter(languageCode: 'en');
      expect(f.format(m), 'EGP 1,234.50');
    });

    test('Arabic with Western digit preference', () {
      const f = MoneyFormatter(languageCode: 'ar', digits: DigitStyle.western);
      expect(f.format(m), '1,234.50 ج.م');
    });

    test('negative and sub-unit amounts', () {
      const f = MoneyFormatter(languageCode: 'en');
      expect(f.format(const Money(minor: -5, currency: 'EGP')), '-EGP 0.05');
    });

    test('zero-exponent currency has no fraction', () {
      const f = MoneyFormatter(languageCode: 'en');
      expect(
        f.format(const Money(minor: 1500, currency: 'JPY', exponent: 0)),
        'JPY 1,500',
      );
    });
  });

  group('enums', () {
    test('known values parse', () {
      expect(
        BookingStatus.parse('provider_arrived'),
        BookingStatus.providerArrived,
      );
      expect(
        FormFieldType.parse('item_checklist'),
        FormFieldType.itemChecklist,
      );
    });

    test('unknown / null values fall back to unknown', () {
      expect(BookingStatus.parse('brand_new_state'), BookingStatus.unknown);
      expect(OfferStatus.parse(null), OfferStatus.unknown);
      expect(PaymentStatus.parse(''), PaymentStatus.unknown);
    });

    test('wire values roundtrip for every member', () {
      for (final v in BookingStatus.values.where(
        (e) => e != BookingStatus.unknown,
      )) {
        expect(BookingStatus.parse(v.value), v);
      }
    });
  });

  group('SequenceTracker', () {
    test('in-order events need no refetch', () {
      final t = SequenceTracker();
      expect(t.needsRefetch('b1', 1), isFalse);
      expect(t.needsRefetch('b1', 2), isFalse);
    });

    test('gap, first-unseen and duplicates', () {
      final t = SequenceTracker();
      expect(t.needsRefetch('b1', 5), isTrue); // first event is not 1
      t.resync('b1', 5);
      expect(t.needsRefetch('b1', 6), isFalse);
      expect(t.needsRefetch('b1', 8), isTrue); // gap
      expect(t.needsRefetch('b1', 8), isFalse); // duplicate
      expect(t.needsRefetch('b1', 3), isFalse); // stale
    });
  });

  group('SessionController', () {
    test('restore, signIn, expire, signOut', () async {
      final store = MemoryTokenStore();
      final s = SessionController(store);
      expect(await s.restore(), SessionStatus.unauthenticated);
      await s.signIn('t');
      expect(s.status, SessionStatus.authenticated);
      await s.markExpired();
      expect(s.status, SessionStatus.expired);
      expect(await store.read(), isNull);
      await s.signIn('t2');
      await s.signOut();
      expect(s.status, SessionStatus.unauthenticated);
    });
  });
}
