<?php

namespace App\DataTables\Admin\Content\Concerns;

use App\Enums\Common\Locale;
use App\View\Components\Admin\Badge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Yajra\DataTables\EloquentDataTable;

/**
 * "Locales" column of the CMS listings: which languages have text (Arabic is required, English optional).
 *
 * @mixin \App\DataTables\Admin\AdminDataTable
 */
trait RendersLocales
{
    protected function localesColumn(EloquentDataTable $table, string $attribute): void
    {
        $table->addColumn('locales', static function (Model $row) use ($attribute): string {
            /** @var \Spatie\Translatable\HasTranslations&Model $row */
            return collect(Locale::cases())
                ->map(static fn (Locale $locale): string => Blade::renderComponent(new Badge(
                    label: strtoupper($locale->value),
                    color: trim((string) $row->getTranslation($attribute, $locale->value, false)) !== '' ? 'success' : 'neutral',
                )))
                ->implode(' ');
        });

        $this->rawColumn('locales');
    }
}
