<?php

/*
| Laravel missing-translation scanner (P6.3). Not a test suite; exits 1 on problems so CI can run it later.
|
|   php scripts/check-translations.php            (from the repository root)
|   php scripts/check-translations.php --verbose  (also lists dynamic-key prefixes it checked)
|
| Checks:
|   1. parity: every key in lang/ar/*.php exists in lang/en/*.php and vice versa (files and nested keys), empty values, and
|      :placeholder / |plural-variant parity between the two locales;
|   2. usage: every literal key passed to __() / trans() / trans_choice() / @lang() / Lang::get() in app, resources, routes, config
|      and database resolves in BOTH locales (keys that are not `file.key` shaped are JSON-string keys and are skipped);
|   3. dynamic keys (`__("enums.x.{$v}")`): the static prefix must exist as an array/group in both locales;
|   4. warning only: Arabic letters hard-coded in Blade/PHP/JS output outside lang files (should be a translation).
*/

$root = dirname(__DIR__).'/backend';
$verbose = in_array('--verbose', $argv, true);
$errors = [];
$warnings = [];

function flatten(array $a, string $p = ''): array
{
    $o = [];
    foreach ($a as $k => $v) {
        $key = $p === '' ? (string) $k : "$p.$k";
        if (is_array($v)) {
            $o += flatten($v, $key);
            $o[$key.'.'] = null; // group marker (for dynamic prefixes)
        } else {
            $o[$key] = $v;
        }
    }

    return $o;
}

$lang = ['ar' => [], 'en' => []];
$files = [];
foreach (['ar', 'en'] as $loc) {
    foreach (glob("$root/lang/$loc/*.php") as $f) {
        $name = basename($f, '.php');
        $files[$name][$loc] = true;
        $data = require $f;
        foreach (flatten((array) $data, $name) as $k => $v) {
            $lang[$loc][$k] = $v;
        }
    }
}

foreach ($files as $name => $locs) {
    foreach (['ar', 'en'] as $loc) {
        if (! isset($locs[$loc])) {
            $errors[] = "lang/$loc/$name.php is missing";
        }
    }
}

$placeholders = static function (?string $s): array {
    preg_match_all('/:[A-Za-z_]+/', (string) $s, $m);
    $p = array_unique($m[0]);
    sort($p);

    return $p;
};
$variants = static fn (?string $s): int => substr_count((string) $s, '|') + 1;

foreach (['ar' => 'en', 'en' => 'ar'] as $a => $b) {
    foreach ($lang[$a] as $k => $v) {
        if (str_ends_with($k, '.')) {
            continue;
        }
        if (! array_key_exists($k, $lang[$b]) && ! array_key_exists($k.'.', $lang[$b])) {
            $errors[] = "key '$k' is in lang/$a but missing in lang/$b";
        }
    }
}
foreach ($lang['ar'] as $k => $v) {
    if (str_ends_with($k, '.') || ! array_key_exists($k, $lang['en'])) {
        continue;
    }
    $e = $lang['en'][$k];
    if (! is_string($v) || ! is_string($e)) {
        continue;
    }
    if ((trim($v) === '' || trim($e) === '') && ! str_starts_with($k, 'site.paths.') && ! str_starts_with($k, 'datatables.')) {
        $errors[] = "key '$k' has an empty value";
    }
    if ($placeholders($v) !== $placeholders($e)) {
        $errors[] = "key '$k' placeholders differ: ar ".implode(',', $placeholders($v)).' / en '.implode(',', $placeholders($e));
    }
    if (! str_starts_with($k, 'validation.') && $variants($v) !== $variants($e) && (str_contains($v, '|') || str_contains($e, '|'))) {
        $warnings[] = "key '$k' has a different number of plural variants (ar ".$variants($v).' / en '.$variants($e).') (Arabic may legitimately have more)';
    }
}

// Usage scan.
$dirs = ['app', 'resources', 'routes', 'config', 'database'];
$used = [];
$dynamic = [];
$arabicHardcoded = [];
$exts = ['php', 'js'];
foreach ($dirs as $d) {
    if (! is_dir("$root/$d")) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$root/$d", FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        $path = $f->getPathname();
        if (! in_array($f->getExtension(), $exts, true) || str_contains($path, '/lang/') || str_contains($path, '/vendor/')) {
            continue;
        }
        $rel = substr($path, strlen($root) + 1);
        $src = file_get_contents($path);
        $n = preg_match_all('/(?:\b__|\btrans|\btrans_choice|@lang|Lang::get|Lang::choice)\(\s*([\'"])((?:(?!\1).)+)\1/s', $src, $m, PREG_OFFSET_CAPTURE);
        for ($i = 0; $i < $n; $i++) {
            $key = $m[2][$i][0];
            $line = substr_count(substr($src, 0, $m[0][$i][1]), "\n") + 1;
            if (! str_contains($key, '.') || preg_match('/\s/', $key)) {
                continue; // JSON string key / plain sentence
            }
            $after = ltrim(substr($src, $m[0][$i][1] + strlen($m[0][$i][0]), 6));
            if (str_contains($key, '$') || str_ends_with($key, '.') || str_ends_with($key, '_') || str_starts_with($after, '.')) {
                $prefix = preg_replace('/\{?\$.*$/s', '', $key);
                $dynamic[$prefix][] = "$rel:$line";

                continue;
            }
            $first = explode('.', $key, 2)[0];
            if (! isset($files[$first])) {
                continue;
            }
            $used[$key][] = "$rel:$line";
        }
        if (str_starts_with($rel, 'resources/') || str_starts_with($rel, 'app/')) {
            $stripped = preg_replace(['~/\*.*?\*/~s', '~(?<!:)//[^\n]*~', '~\{\{--.*?--\}\}~s'], '', $src);
            if (preg_match('/[\x{0600}-\x{06FF}]/u', $stripped)) {
                $arabicHardcoded[] = $rel;
            }
        }
    }
}

foreach ($used as $key => $where) {
    foreach (['ar', 'en'] as $loc) {
        if (! array_key_exists($key, $lang[$loc]) && ! array_key_exists($key.'.', $lang[$loc])) {
            $errors[] = "missing in lang/$loc: '$key' (".implode(', ', array_slice($where, 0, 3)).')';
        } elseif (is_array($lang[$loc][$key] ?? null)) {
            $errors[] = "key '$key' resolves to a group, not a string, in lang/$loc (".$where[0].')';
        }
    }
}
foreach ($dynamic as $prefix => $where) {
    $has = static function (string $loc) use ($lang, $prefix): bool {
        $p = rtrim($prefix, '.');
        foreach ($lang[$loc] as $k => $_) {
            if (str_starts_with($k, $p)) {
                return true;
            }
        }

        return false;
    };
    foreach (['ar', 'en'] as $loc) {
        if (! $has($loc)) {
            $errors[] = "dynamic key prefix '$prefix' has no keys in lang/$loc (".$where[0].')';
        }
    }
    if ($verbose) {
        echo "dynamic prefix checked: $prefix (".count($where)." use(s))\n";
    }
}
foreach (array_unique($arabicHardcoded) as $f) {
    $warnings[] = "Arabic text hard-coded outside lang files: $f";
}

foreach ($warnings as $w) {
    echo "WARN  $w\n";
}
foreach ($errors as $e) {
    echo "ERROR $e\n";
}
printf("\n%d literal keys, %d dynamic prefixes, %d errors, %d warnings\n", count($used), count($dynamic), count($errors), count($warnings));
exit($errors === [] ? 0 : 1);
