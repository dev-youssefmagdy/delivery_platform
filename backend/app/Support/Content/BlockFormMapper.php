<?php

namespace App\Support\Content;

/**
 * Converts between the flat rows of the block editor (blocks[0][title_ar], blocks[0][items_en] ...) and the stored
 * block structure. Items are typed one per line as "title | text"; Arabic and English lines are paired by position.
 */
final class BlockFormMapper
{
    public const MAX_ITEMS = 12;

    /**
     * @param array<int|string, mixed> $rows
     * @return list<array<string, mixed>>
     */
    public static function fromInput(array $rows): array
    {
        $blocks = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['type'] ?? null)) {
                continue;
            }

            $text = static fn (string $key): ?string => isset($row[$key]) && is_string($row[$key]) && trim($row[$key]) !== '' ? trim($row[$key]) : null;
            $pair = static fn (string $base): array => array_filter(['ar' => $text($base.'_ar'), 'en' => $text($base.'_en')], static fn (?string $value): bool => $value !== null);

            $blocks[] = [
                'type' => $row['type'],
                'title' => $pair('title'),
                'body' => $pair('body'),
                'items' => self::items($text('items_ar'), $text('items_en')),
                'cta' => ['label' => $pair('cta_label'), 'url' => $text('cta_url')],
                'ref' => $text('ref'),
            ];
        }

        return $blocks;
    }

    /**
     * @param list<array<string, mixed>>|null $blocks
     * @return list<array<string, mixed>>
     */
    public static function toRows(?array $blocks): array
    {
        $rows = [];

        foreach ($blocks ?? [] as $block) {
            $items = (array) ($block['items'] ?? []);
            $line = static fn (string $locale): string => implode("\n", array_map(
                static fn (array $item): string => trim(($item['title'][$locale] ?? '').' | '.($item['text'][$locale] ?? ''), ' |'),
                $items,
            ));

            $rows[] = [
                'type' => $block['type'] ?? null,
                'title_ar' => $block['title']['ar'] ?? null,
                'title_en' => $block['title']['en'] ?? null,
                'body_ar' => $block['body']['ar'] ?? null,
                'body_en' => $block['body']['en'] ?? null,
                'items_ar' => $line('ar') ?: null,
                'items_en' => $line('en') ?: null,
                'cta_label_ar' => $block['cta']['label']['ar'] ?? null,
                'cta_label_en' => $block['cta']['label']['en'] ?? null,
                'cta_url' => $block['cta']['url'] ?? null,
                'ref' => $block['ref'] ?? null,
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{title: array<string, string>, text: array<string, string>}>
     */
    private static function items(?string $arabic, ?string $english): array
    {
        $parse = static function (?string $lines): array {
            $parsed = [];

            foreach (preg_split('/\R/u', (string) $lines) ?: [] as $line) {
                $line = trim($line);

                if ($line === '') {
                    continue;
                }

                [$title, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
                $parsed[] = ['title' => $title, 'text' => $text];
            }

            return $parsed;
        };

        $ar = $parse($arabic);
        $en = $parse($english);
        $items = [];

        foreach (array_slice($ar, 0, self::MAX_ITEMS) as $index => $item) {
            $items[] = [
                'title' => array_filter(['ar' => $item['title'], 'en' => $en[$index]['title'] ?? ''], static fn (string $value): bool => $value !== ''),
                'text' => array_filter(['ar' => $item['text'], 'en' => $en[$index]['text'] ?? ''], static fn (string $value): bool => $value !== ''),
            ];
        }

        return $items;
    }
}
