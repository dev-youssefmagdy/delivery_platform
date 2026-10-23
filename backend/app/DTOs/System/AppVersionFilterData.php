<?php

namespace App\DTOs\System;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use App\Enums\System\AppPlatform;
use App\Enums\System\AppTarget;
use Illuminate\Http\Request;

final readonly class AppVersionFilterData implements FilterData
{
    use ParsesFilterInput;

    /**
     * @param list<AppTarget> $apps
     * @param list<AppPlatform> $platforms
     */
    public function __construct(public array $apps = [], public array $platforms = [], public ?bool $forceUpdate = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(
            self::enumListFilter($input, 'apps', AppTarget::class),
            self::enumListFilter($input, 'platforms', AppPlatform::class),
            self::boolFilter($input, 'force_update'),
        );
    }

    public function activeCount(): int
    {
        return self::countActive([$this->apps, $this->platforms, $this->forceUpdate]);
    }

    public function toArray(): array
    {
        return array_filter([
            'apps' => array_map(static fn (AppTarget $c): string => $c->value, $this->apps),
            'platforms' => array_map(static fn (AppPlatform $c): string => $c->value, $this->platforms),
            'force_update' => $this->forceUpdate,
        ], static fn (mixed $v): bool => $v !== [] && $v !== null);
    }
}
