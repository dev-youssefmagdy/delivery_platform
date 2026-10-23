<?php

namespace App\Support\Load;

use Carbon\CarbonImmutable;

/**
 * Reads the load of a moving / freight request out of the validated service details (SRS §5.1-D, §5.1-E): the volume, the
 * pickup window, the structured items and the attachment references. The form schema stays the single source of validation;
 * these helpers only copy answers into searchable columns / rows under fixed field keys, and ignore categories without them.
 */
final class LoadDetails
{
    private const FORMAT = 'Y-m-d\TH:i';

    /**
     * Cubic metres: an explicit estimate wins; otherwise a freight load's dimensions x pieces, otherwise the itemised rows.
     *
     * @param array<string, mixed> $details
     */
    public static function volumeM3(array $details): ?float
    {
        if (isset($details['estimated_volume']) && is_numeric($details['estimated_volume'])) {
            return round((float) $details['estimated_volume'], 3);
        }

        $dimensions = self::cubic($details['cargo_dimensions'] ?? null);

        if ($dimensions !== null) {
            return round($dimensions * max(1, (int) ($details['pieces'] ?? 1)), 3);
        }

        $sum = 0.0;

        foreach (self::checklistRows($details) as $row) {
            $sum += (self::cubic($row['dimensions']) ?? 0.0) * $row['quantity'];
        }

        return $sum > 0 ? round($sum, 3) : null;
    }

    /**
     * @param array<string, mixed> $details
     * @return array{0: CarbonImmutable|null, 1: CarbonImmutable|null}
     */
    public static function pickupWindow(array $details, string $timezone): array
    {
        $parse = static function (mixed $value) use ($timezone): ?CarbonImmutable {
            if (! is_string($value) || $value === '') {
                return null;
            }

            $date = CarbonImmutable::createFromFormat(self::FORMAT, $value, $timezone);

            return $date instanceof CarbonImmutable ? $date : null;
        };

        return [$parse($details['pickup_window_start'] ?? null), $parse($details['pickup_window_end'] ?? null)];
    }

    /**
     * @param array<string, mixed> $details
     * @param list<array<string, mixed>> $fields the schema's fields
     * @return list<array<string, mixed>> BookingItem attributes (no booking id, no position)
     */
    public static function items(array $details, array $fields): array
    {
        $items = [];
        $labels = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? null) === 'item_checklist') {
                foreach ((array) ($field['options'] ?? []) as $option) {
                    $labels[(string) ($field['key'] ?? '').'.'.$option['value']] = (string) ($option['label']['en'] ?? $option['value']);
                }
            }
        }

        foreach ($fields as $field) {
            if (($field['type'] ?? null) !== 'item_checklist') {
                continue;
            }

            foreach ((array) ($details[$field['key']] ?? []) as $row) {
                $dimensions = (array) ($row['dimensions'] ?? []);
                $cubic = self::cubic($dimensions === [] ? null : $dimensions);
                $items[] = [
                    'kind' => 'checklist',
                    'code' => (string) $row['item'],
                    'label' => $labels[$field['key'].'.'.$row['item']] ?? null,
                    'quantity' => (int) $row['quantity'],
                    'length_cm' => $dimensions['length'] ?? null,
                    'width_cm' => $dimensions['width'] ?? null,
                    'height_cm' => $dimensions['height'] ?? null,
                    'volume_m3' => $cubic === null ? null : round($cubic, 3),
                    'weight_kg' => isset($row['weight_kg']) ? $row['weight_kg'] : null,
                    'note' => isset($row['note']) ? mb_substr((string) $row['note'], 0, 255) : null,
                ];
            }
        }

        foreach (preg_split('/\R/u', trim((string) ($details['items_notes'] ?? ''))) ?: [] as $line) {
            $line = trim($line);

            if ($line !== '' && count($items) < 60) {
                $items[] = ['kind' => 'free', 'code' => null, 'label' => mb_substr($line, 0, 255), 'quantity' => 1];
            }
        }

        if (isset($details['cargo_type'])) {
            $dimensions = is_array($details['cargo_dimensions'] ?? null) ? $details['cargo_dimensions'] : [];
            $cubic = self::cubic($dimensions === [] ? null : $dimensions);
            $items[] = [
                'kind' => 'cargo',
                'code' => (string) $details['cargo_type'],
                'label' => isset($details['cargo_description']) ? mb_substr((string) $details['cargo_description'], 0, 255) : null,
                'quantity' => max(1, (int) ($details['pieces'] ?? 1)),
                'length_cm' => $dimensions['length'] ?? null,
                'width_cm' => $dimensions['width'] ?? null,
                'height_cm' => $dimensions['height'] ?? null,
                'volume_m3' => $cubic === null ? null : round($cubic, 3),
                'weight_kg' => isset($details['cargo_weight']) && is_numeric($details['cargo_weight']) ? $details['cargo_weight'] : null,
            ];
        }

        return $items;
    }

    /**
     * Attachment uuids by field key for the schema's `photo` fields.
     *
     * @param array<string, mixed> $details
     * @param list<array<string, mixed>> $fields
     * @return array<string, list<string>>
     */
    public static function attachmentRefs(array $details, array $fields): array
    {
        $refs = [];

        foreach ($fields as $field) {
            if (($field['type'] ?? null) === 'photo' && isset($details[$field['key']])) {
                $refs[(string) $field['key']] = array_values(array_map('strval', (array) $details[$field['key']]));
            }
        }

        return $refs;
    }

    /**
     * @param array<string, mixed> $details
     * @return list<array{quantity: int, dimensions: mixed}>
     */
    private static function checklistRows(array $details): array
    {
        $rows = [];

        foreach ($details as $value) {
            if (is_array($value) && array_is_list($value) && isset($value[0]['item'], $value[0]['quantity'])) {
                foreach ($value as $row) {
                    $rows[] = ['quantity' => (int) $row['quantity'], 'dimensions' => $row['dimensions'] ?? null];
                }
            }
        }

        return $rows;
    }

    private static function cubic(mixed $dimensions): ?float
    {
        if (! is_array($dimensions) || ! isset($dimensions['length'], $dimensions['width'], $dimensions['height'])) {
            return null;
        }

        return (float) $dimensions['length'] * (float) $dimensions['width'] * (float) $dimensions['height'] / 1_000_000;
    }
}
