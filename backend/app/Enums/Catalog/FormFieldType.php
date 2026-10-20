<?php

namespace App\Enums\Catalog;

use App\Enums\Concerns\EnumHelpers;
use App\Enums\Contracts\HasLabel;

/**
 * Field types of data-driven service forms (Plan §2.3). Backend ServiceFormValidator compiles them to Laravel
 * rules; Flutter's DynamicFormRenderer renders the same schema. Adding a type means updating both.
 */
enum FormFieldType: string implements HasLabel
{
    use EnumHelpers;

    case Text = 'text';
    case Textarea = 'textarea';
    case Integer = 'integer';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Select = 'select';
    case Multiselect = 'multiselect';
    case Date = 'date';
    case Time = 'time';
    case Datetime = 'datetime';
    case Contact = 'contact';
    case Photo = 'photo';
    case ItemChecklist = 'item_checklist';
    case Dimensions = 'dimensions';
    case Weight = 'weight';

    public function label(): string
    {
        return __("enums.form_field_type.{$this->value}");
    }

    /**
     * Types whose answer is chosen from schema-defined options.
     */
    public function usesOptions(): bool
    {
        return in_array($this, [self::Select, self::Multiselect, self::ItemChecklist], true);
    }

    /**
     * Types where `min` / `max` bound a number (value, each dimension, weight) or a length / count.
     */
    public function supportsRange(): bool
    {
        return ! in_array($this, [self::Boolean, self::Select, self::Date, self::Time, self::Datetime, self::Contact], true);
    }

    public function isNumeric(): bool
    {
        return in_array($this, [self::Integer, self::Decimal, self::Weight], true);
    }
}
