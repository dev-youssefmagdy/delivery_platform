<?php

/*
| Flutter ARB parity checker (P6.3). Not a test suite; exits 1 on problems so CI can run it later.
|
|   php scripts/check-arb-parity.php            (from the repository root)
|
| For every directory that holds an app_ar.arb: customer_app, driver_app and, when it ever gets ARB files, packages/platform_core
| (any l10n/app_ar.arb below it), compares it with the sibling app_en.arb:
|   - same message keys in both (the `@key` metadata is ignored for the key set);
|   - non-empty strings, balanced braces, ICU plural/select blocks that contain `other`;
|   - the same {placeholders} (including the plural/select selector argument) in both locales;
|   - every placeholder used in the template (app_ar.arb) is declared in its `@key.placeholders`, and the metadata of both files
|     declares the same placeholder names;
|   - `@@locale` matches the file name.
| Also warns about generated l10n output that is older than the ARB files (run `flutter gen-l10n`).
*/

$root = dirname(__DIR__);
$errors = [];
$warnings = [];
$checked = 0;

$dirs = [];
foreach (['customer_app', 'driver_app', 'packages/platform_core'] as $base) {
    if (! is_dir("$root/$base")) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$base", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $p = $f->getPathname();
        if ($f->getFilename() === 'app_ar.arb' && ! str_contains($p, '/build/') && ! str_contains($p, '/.dart_tool/')) {
            $dirs[] = dirname($p);
        }
    }
}
if ($dirs === []) {
    fwrite(STDERR, "No app_ar.arb found\n");
    exit(2);
}

function placeholders(string $msg): array
{
    $names = [];
    $depth = 0;
    $len = strlen($msg);
    for ($i = 0; $i < $len; $i++) {
        $c = $msg[$i];
        if ($c === '{') {
            if ($depth === 0 || $depth === 1) {
                // argument name until , or }
                if (preg_match('/\G\s*([A-Za-z_][A-Za-z0-9_]*)\s*([,}])/', $msg, $m, 0, $i + 1)) {
                    $names[$m[1]] = true;
                }
            }
            $depth++;
        } elseif ($c === '}') {
            $depth--;
        }
    }
    unset($names['other']);
    $n = array_keys($names);
    // names that are really plural/select case labels (=0, one, few ...) never match the identifier regex followed by , or }
    sort($n);

    return $n;
}

function balanced(string $msg): bool
{
    $d = 0;
    foreach (str_split($msg) as $c) {
        if ($c === '{') {
            $d++;
        } elseif ($c === '}' && --$d < 0) {
            return false;
        }
    }

    return $d === 0;
}

function declared(array $arb, string $key): array
{
    $n = array_keys((array) ($arb["@$key"]['placeholders'] ?? []));
    sort($n);

    return $n;
}

foreach ($dirs as $dir) {
    $rel = substr($dir, strlen($root) + 1);
    $ar = json_decode((string) file_get_contents("$dir/app_ar.arb"), true);
    $en = is_file("$dir/app_en.arb") ? json_decode((string) file_get_contents("$dir/app_en.arb"), true) : null;
    if (! is_array($ar) || ! is_array($en)) {
        $errors[] = "$rel: app_ar.arb / app_en.arb missing or not valid JSON";

        continue;
    }
    $checked++;
    if (($ar['@@locale'] ?? null) !== 'ar') {
        $errors[] = "$rel/app_ar.arb: @@locale must be 'ar'";
    }
    if (($en['@@locale'] ?? null) !== 'en') {
        $errors[] = "$rel/app_en.arb: @@locale must be 'en'";
    }
    $keys = static fn (array $a): array => array_values(array_filter(array_keys($a), static fn ($k) => $k[0] !== '@'));
    $kAr = $keys($ar);
    $kEn = $keys($en);
    foreach (array_diff($kAr, $kEn) as $k) {
        $errors[] = "$rel: '$k' is in app_ar.arb but missing in app_en.arb";
    }
    foreach (array_diff($kEn, $kAr) as $k) {
        $errors[] = "$rel: '$k' is in app_en.arb but missing in app_ar.arb";
    }
    foreach (array_intersect($kAr, $kEn) as $k) {
        foreach (['ar' => $ar, 'en' => $en] as $loc => $arb) {
            $v = $arb[$k];
            if (! is_string($v) || trim($v) === '') {
                $errors[] = "$rel/app_$loc.arb: '$k' is empty or not a string";

                continue;
            }
            if (! balanced($v)) {
                $errors[] = "$rel/app_$loc.arb: '$k' has unbalanced braces";
            }
            if (preg_match('/\{[^{},]+,\s*(plural|select)\s*,/', $v) && ! str_contains($v, 'other{')) {
                $errors[] = "$rel/app_$loc.arb: '$k' plural/select has no 'other' case";
            }
        }
        $pAr = placeholders($ar[$k]);
        $pEn = placeholders($en[$k]);
        if ($pAr !== $pEn) {
            $errors[] = "$rel: '$k' placeholders differ: ar {".implode(',', $pAr).'} / en {'.implode(',', $pEn).'}';
        }
        $dAr = declared($ar, $k);
        $dEn = declared($en, $k);
        if ($pAr !== [] && array_diff($pAr, $dAr) !== []) {
            $errors[] = "$rel: '$k' uses {".implode(',', array_diff($pAr, $dAr)).'} without declaring it in @'.$k.'.placeholders (app_ar.arb)';
        }
        if ($dEn !== [] && $dAr !== [] && $dEn !== $dAr) {
            $errors[] = "$rel: '$k' declares different placeholders in the two files";
        }
    }

    $gen = "$dir/generated/app_localizations.dart";
    if (is_file($gen) && filemtime($gen) < max(filemtime("$dir/app_ar.arb"), filemtime("$dir/app_en.arb"))) {
        $warnings[] = "$rel: generated localizations are older than the ARB files (run flutter gen-l10n)";
    }
    printf("%s: %d keys (ar %d / en %d)\n", $rel, count($kAr), count($kAr), count($kEn));
}

foreach ($warnings as $w) {
    echo "WARN  $w\n";
}
foreach ($errors as $e) {
    echo "ERROR $e\n";
}
printf("\n%d l10n sets checked, %d errors, %d warnings\n", $checked, count($errors), count($warnings));
exit($errors === [] ? 0 : 1);
