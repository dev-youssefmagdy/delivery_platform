<?php

namespace App\DTOs\Reporting;

use App\Enums\Reporting\ReportColumnType;

/**
 * One column of a report: the key is the select alias of the repository query, the label a translation key under `reporting.columns`.
 * `pii` columns are shown and exported only to an admin with `reports.export_pii`.
 */
final readonly class ReportColumn
{
    public function __construct(
        public string $key,
        public ReportColumnType $type = ReportColumnType::Integer,
        public bool $orderable = true,
        public bool $pii = false,
    ) {}

    public static function text(string $key, bool $pii = false): self
    {
        return new self($key, ReportColumnType::Text, true, $pii);
    }

    public static function integer(string $key): self
    {
        return new self($key, ReportColumnType::Integer);
    }

    public static function money(string $key): self
    {
        return new self($key, ReportColumnType::Money);
    }

    public static function percent(string $key): self
    {
        return new self($key, ReportColumnType::Percent);
    }

    public static function seconds(string $key): self
    {
        return new self($key, ReportColumnType::Seconds);
    }

    public function label(): string
    {
        return (string) __("reporting.columns.{$this->key}");
    }
}
