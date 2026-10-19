<?php

namespace App\DTOs\Demo;

use App\Enums\Demo\DemoItemStatus;
use App\Enums\Demo\DemoItemTag;
use App\Enums\Demo\DemoItemType;
use App\Http\Requests\Admin\Demo\StoreDemoItemRequest;
use App\Http\Requests\Admin\Demo\UpdateDemoItemRequest;
use App\Support\Money\CurrencyRegistry;
use App\Support\Money\Money;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;

/**
 * Validated input for creating/updating a DemoItem. Dates typed in the admin display timezone are converted
 * to UTC here; money arrives in minor units.
 */
final readonly class DemoItemData
{
    /**
     * @param array{ar: string, en?: string} $name
     * @param array{ar?: string, en?: string} $description
     * @param list<DemoItemTag> $tags
     * @param list<array{0: float, 1: float}>|null $area
     * @param array<string, string> $specs
     * @param list<array{label: string, price: int|null, is_default: bool}> $options
     */
    public function __construct(
        public array $name,
        public array $description,
        public string $code,
        public DemoItemType $type,
        public DemoItemStatus $status,
        public Money $price,
        public ?int $countryId,
        public array $tags,
        public bool $isFeatured,
        public bool $isPublic,
        public ?CarbonImmutable $availableOn,
        public ?CarbonImmutable $publishedAt,
        public ?CarbonImmutable $startsOn,
        public ?CarbonImmutable $endsOn,
        public ?UploadedFile $cover,
        public ?string $latitude,
        public ?string $longitude,
        public ?array $area,
        public array $specs,
        public array $options,
        public ?string $notes,
        public ?int $lockVersion = null,
    ) {}

    public static function fromRequest(StoreDemoItemRequest|UpdateDemoItemRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();
        $timezone = (string) config('admin.display_timezone', 'UTC');
        $date = static fn (mixed $value): ?CarbonImmutable => is_string($value) && $value !== '' ? CarbonImmutable::createFromFormat('!Y-m-d', $value) ?: null : null;

        $specs = [];

        foreach ((array) ($data['specs'] ?? []) as $pair) {
            $specs[(string) $pair['key']] = (string) ($pair['value'] ?? '');
        }

        $options = [];

        foreach ((array) ($data['options'] ?? []) as $option) {
            $options[] = [
                'label' => (string) $option['label'],
                'price' => isset($option['price']) && $option['price'] !== '' ? (int) $option['price'] : null,
                'is_default' => (bool) ($option['is_default'] ?? false),
            ];
        }

        $area = isset($data['area']) && is_string($data['area']) && $data['area'] !== '' ? json_decode($data['area'], true) : null;

        return new self(
            name: self::translations($data['name'] ?? []),
            description: self::translations($data['description'] ?? []),
            code: strtoupper((string) $data['code']),
            type: DemoItemType::from((string) $data['type']),
            status: DemoItemStatus::from((string) $data['status']),
            price: Money::of((int) $data['price'], app(CurrencyRegistry::class)->get((string) config('admin.default_currency'))),
            countryId: isset($data['country_id']) ? (int) $data['country_id'] : null,
            tags: array_map(static fn (string $tag): DemoItemTag => DemoItemTag::from($tag), array_values((array) ($data['tags'] ?? []))),
            isFeatured: (bool) ($data['is_featured'] ?? false),
            isPublic: (bool) ($data['is_public'] ?? false),
            availableOn: $date($data['available_on'] ?? null),
            publishedAt: isset($data['published_at']) && is_string($data['published_at'])
                ? CarbonImmutable::createFromFormat('Y-m-d H:i', $data['published_at'], $timezone)?->utc()
                : null,
            startsOn: $date($data['starts_on'] ?? null),
            endsOn: $date($data['ends_on'] ?? null),
            cover: $request->file('cover') instanceof UploadedFile ? $request->file('cover') : null,
            latitude: isset($data['latitude']) ? (string) $data['latitude'] : null,
            longitude: isset($data['longitude']) ? (string) $data['longitude'] : null,
            area: is_array($area) ? array_map(static fn (array $point): array => [(float) $point[0], (float) $point[1]], array_values($area)) : null,
            specs: $specs,
            options: $options,
            notes: isset($data['notes']) && $data['notes'] !== '' ? (string) $data['notes'] : null,
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }

    /**
     * Model attributes (cover and lock_version are handled by the service).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description === [] ? null : $this->description,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,
            'price' => $this->price,
            'country_id' => $this->countryId,
            'tags' => $this->tags === [] ? null : $this->tags,
            'is_featured' => $this->isFeatured,
            'is_public' => $this->isPublic,
            'available_on' => $this->availableOn,
            'published_at' => $this->publishedAt,
            'starts_on' => $this->startsOn,
            'ends_on' => $this->endsOn,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'area' => $this->area,
            'specs' => $this->specs === [] ? null : $this->specs,
            'options' => $this->options === [] ? null : $this->options,
            'notes' => $this->notes,
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function translations(mixed $values): array
    {
        return array_filter(
            array_map(static fn (mixed $value): string => trim((string) $value), is_array($values) ? $values : []),
            static fn (string $value): bool => $value !== '',
        );
    }
}
