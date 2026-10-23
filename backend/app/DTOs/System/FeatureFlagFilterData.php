<?php

namespace App\DTOs\System;

use App\DTOs\Concerns\ParsesFilterInput;
use App\DTOs\Contracts\FilterData;
use Illuminate\Http\Request;

final readonly class FeatureFlagFilterData implements FilterData
{
    use ParsesFilterInput;

    public function __construct(public ?string $key = null, public ?bool $enabled = null) {}

    public static function fromRequest(Request $request): static
    {
        $input = self::filterInput($request);

        return new self(self::stringFilter($input, 'key', 64), self::boolFilter($input, 'enabled'));
    }

    public function activeCount(): int
    {
        return self::countActive([$this->key, $this->enabled]);
    }

    public function toArray(): array
    {
        return array_filter(['key' => $this->key, 'enabled' => $this->enabled], static fn (mixed $v): bool => $v !== null);
    }
}
