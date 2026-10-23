# Localization, RTL and accessibility review (P6.3)

**Read this first: no visual or device review was possible.** The project was built without a browser, an emulator or a phone, and by instruction no test suite ran. Everything below comes from reading the code, grep audits, `php -l`, `dart analyze` on the touched directories, `php artisan view:cache`, one `npm run build` and the three scanner scripts. Nothing was looked at in ar or en on a small phone, large phone, tablet or desktop; overflow, truncation and mirrored layout are reasoned from the widget and class choices, not observed. P6.4 must include a browser and device pass.

## Scanners (repo scripts, exit 1 on problems)

| Script | Checks | Result before | Result after |
|---|---|---|---|
| `php scripts/check-translations.php` | literal and dynamic-prefix keys vs `lang/ar` and `lang/en`, ar/en parity, `:placeholders`, empty values, hard-coded Arabic | 20 findings (4 real) | 0 errors, 18 warnings (all legitimate, below) |
| `php scripts/check-arb-parity.php` | ARB key parity and placeholders, customer_app (978 keys), driver_app (1125 keys); `platform_core` has no ARB files (its widgets take strings objects) | 0 errors | 0 errors |
| `node scripts/check-contrast.mjs` | 67 token pairs: admin, website, charts light/dark, Flutter | 10 failing | 0 failing, 5 waived |

Limits of the Laravel scanner: it matches string literals passed to `__()`, `trans()`, `trans_choice()`, `@lang`, `Lang::get`; for dynamic keys (`__("enums.x.{$v}")`) it only checks that the prefix exists in both locales, not that every enum case has a key. Keys passed to other helpers (for example column labels given as plain strings) are not seen. It does not execute code.

### Translation findings fixed
- `admin.security.account_menu`, `my_account`, `sign_out` and `validation.base64` existed only in English.
- `admin.bookings.messages.recipient_otp_lock_lifted` and `attempt_resolved` were nested one level too deep (inside the booking event list), so `BookingOverrideController` showed the raw key in both languages. Moved to `messages`.
- A hard-coded Arabic comma separated lists in English admin pages (12 places) and the donut chart label "Other" was hard-coded Arabic/English in JS. Now `admin.list_separator` and `admin.js.charts.other`.
- Warnings kept on purpose: digit normalisation tables (`PhoneNumber`, `PlateNumber`, OTP and form guards, `ui.js` number parsing), the Arabic search variants, scaffolding in `MakeAdminModule`, `SettingKey` default text, and JS test fixtures.

## RTL review (by code)

- **Tailwind**: a regex audit over views, JS-generated markup, DataTables classes and CSS for `ml- mr- pl- pr- left- right- text-left text-right float-* border-l/r rounded-l/r inset-x` found no physical utilities. The only physical CSS is the Tom Select arrow override under `[dir='rtl']` (intentional). Slide-in elements (drawer, sidebar, toggle knob) carry `rtl:` variants. Directional icons mirror through `Icon::directional()` (`rtl:-scale-x-100`); logos, maps and chevron-down do not.
- **Charts and maps**: the Chart.js time axis reverses and the value axis moves right in RTL; maps are forced `dir="ltr"` (coordinates do not mirror).
- **Flutter**: no `EdgeInsets.only(left/right)`, `Alignment.centerLeft/Right`, `Positioned(left/right)` or `TextAlign.left/right` outside `IsolatedText`; only directional widgets are used. `Icons.chevron_right`, `send`, `logout` carry `matchTextDirection` in the Flutter icon set, so they mirror by themselves.
- **Fixed, bidi**: 22 money, plate, phone and masked-card `Text(textDirection: ltr)` widgets (plus 7 mixed-language sentences now using `ltrIsolate()`) drifted to the left edge in full-width Arabic columns and localized sentences ("Call support: +20...") were forced LTR. New `IsolatedText` and `ltrIsolate()` in `platform_core` (ADR-210); `MoneyText` now really is LTR. Inputs keep `textDirection: ltr` on purpose. Remaining `Directionality(ltr)` wrappers (PIN, referral code, blocking notice codes) are short single-purpose values.
- **Fixed, admin/website**: `.ltr-only` now isolates in both locales (it only did in RTL, so an Arabic plate in an English page could reorder), person names use `<bdi>`, support/chat/review free text uses `dir="auto"`.
- **Digits**: backend money and the Flutter `MoneyFormatter` honour the digits preference; ratings and distances in the apps now go through `MoneyFormatter.number()` too. **Not fixed**: ARB `int` placeholders (`{count}`, minutes) and some `intl` dates render by locale (Arabic-Indic for `ar`) regardless of the server preference. Mixed output only appears when an operator sets `western` for Arabic.
- **Fonts**: admin and website self-host IBM Plex Sans Arabic (OFL). The apps bundle no font on purpose; the theme now lists Arabic fallbacks (`arabicFontFallback`: Noto Sans/Naskh Arabic, Geeza Pro, SF Arabic, Segoe UI, Tahoma). Bundling a font in the apps (with its licence) is still open.

## Accessibility review (by code)

- **Admin**: modal and drawer have `role="dialog"`, `aria-modal`, `aria-labelledby`, focus trap and Escape; tabs have `tablist/tab/tabpanel`, `aria-selected`, roving `tabindex`; dropdown has `aria-haspopup`, `aria-expanded`, Escape returns focus; filters card has `aria-expanded/controls`; toasts use `role=status|alert`; icon-only buttons require a label; skip link and `main` landmark exist; badges carry text (and a dot only as decoration). Global `:focus-visible` outline exists.
- **Website**: labelled fields with `aria-describedby`, `aria-invalid`, required marker with screen-reader text, skip link, labelled navs, menu toggle with `aria-expanded`. Added a global `:focus-visible` outline (links and summary relied on browser defaults).
- **Fixed**: live operations map had mouse-only tooltips and colour-only provider state; added a `details` list (same items, text only, keyboard reachable, rendered only while open).
- **Contrast fixed**: `btn-success` and `btn-warning` (white on emerald/amber-600 was 3.7 and 3.2) now 700 steps; muted `text-slate-400` on light surfaces (2.6) moved to `slate-500` in 30 places (sidebar group labels and the website footer keep it on their dark backgrounds); placeholders; `text-warning-600/success-600/info-600/sky-600/emerald-500` text to 700; form control, checkbox and input-addon borders use `--color-field` (3.2:1, WCAG 1.4.11); Flutter `StatusChip` text is darkened 25% (success/warning were 4.3 on their tint).
- **Waived (documented in the script)**: chart light series 3, 4, 5 are 2.1 to 2.7 against the surface; every chart has a legend, a tooltip and a data table, and the validated palette order was kept. Flutter `accent` is used only for decorative icons beside text.
- **Apps, semantics**: map actions already had tooltips (`my location`, `swap`); the pin-adjust map now has a label; +/- steppers (scope builder, passengers, helpers, quantities) get `label +/-` tooltips instead of `-`/none; `StatusChip`, `MoneyText`, rating and offline widgets already expose text semantics.
- **Apps, text scale 200%**: neither app clamps the system text scale. Fixed-height boxes holding text were turned into minimum heights (driver keypad keys, PIN message area, counter-offer hint). Remaining fixed heights hold images, skeletons, the daily earnings bars (96) and map areas, not text. Rows with a trailing `Text` next to an `Expanded` label (money rows) will wrap the label, not overflow, but this was not observed.
- **Touch targets**: driver theme uses 64dp minimum for buttons and icon buttons; selectable chips now get 16dp vertical padding in the driver profile (about 64dp); the scope-builder steppers went from 56 to 64dp; the chat attachment remove button lost `VisualDensity.compact`. Admin `icon-btn` is 32px (above the WCAG 2.2 AA minimum of 24px, below the 44px target for touch tablets; desktop-first by design).

## Deferred / open

1. Visual review of every admin screen, website page and app screen in ar and en on four sizes (needs a browser and devices).
2. RTL golden tests, text-scale 200% tests, parity tests in CI (listed in `MISSED_TESTS.md`, P6.3).
3. Wire the three scripts into CI (P6.4).
4. Digit preference for ARB `int` placeholders and `intl` dates.
5. Bundle an Arabic font in the apps (licence check) if platform fonts prove inconsistent on device.
6. Toast auto-dismiss (6 s) has no pause on hover/focus; errors should stay until dismissed.
7. Admin `icon-btn` target size on touch tablets; chart series 3 to 5 light colours.
8. Dynamic enum label keys: a check that every enum case has a key in both locales (needs a PHP-side script that boots the app).
