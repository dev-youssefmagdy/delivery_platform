<?php

namespace App\DTOs\Catalog;

use App\Http\Requests\Admin\Catalog\UpdateServiceFormSchemaRequest;
use App\Services\Catalog\Forms\FormSchemaBuilderMapper;

/**
 * Validated input of the form-schema builder: the canonical fields of the category's draft schema.
 */
final readonly class ServiceFormSchemaData
{
    /**
     * @param list<array<string, mixed>> $fields canonical field arrays (validated by FormSchemaValidator)
     */
    public function __construct(public array $fields, public ?int $lockVersion = null) {}

    public static function fromRequest(UpdateServiceFormSchemaRequest $request): self
    {
        /** @var array<string, mixed> $data */
        $data = $request->validated();

        return new self(
            fields: array_map(
                static fn (array $field): array => FormFieldDefinition::fromArray($field)->toArray(),
                FormSchemaBuilderMapper::fromBuilder((array) ($data['fields'] ?? [])),
            ),
            lockVersion: isset($data['lock_version']) ? (int) $data['lock_version'] : null,
        );
    }
}
