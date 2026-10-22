<?php

namespace App\DTOs\Courier;

use App\Enums\Catalog\ProofType;
use Illuminate\Http\UploadedFile;

/**
 * One proof the provider submits (SRS §7.5). Which fields are filled depends on the type: a picture for photo and
 * signature, answers for a checklist, a measured weight for weight. The stage is never part of it: the server derives it.
 */
final readonly class ProofUploadData
{
    /**
     * @param list<array{code: string, checked: bool}> $checklist
     */
    public function __construct(
        public ProofType $type,
        public ?UploadedFile $file = null,
        public array $checklist = [],
        public ?string $weightKg = null,
        public ?string $note = null,
    ) {}
}
