<?php

namespace App\Enums\Provider;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * What a document type is about: the provider as a person/business, or one of their vehicles.
 */
enum DocumentSubject: string implements HasLabel
{
    use EnumHelpers;

    case Provider = 'provider';
    case Vehicle = 'vehicle';

    public function label(): string
    {
        return __("enums.document_subject.{$this->value}");
    }
}
