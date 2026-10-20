<?php

namespace Tests\Unit\Catalog;

use App\Exceptions\BusinessRuleViolation;
use App\Services\Catalog\Forms\FormSchemaValidator;
use Tests\TestCase;

/**
 * Schema-level validation (Plan P1.4 step 2): invalid schemas are rejected before they can be saved or published.
 */
class FormSchemaValidatorTest extends TestCase
{
    private FormSchemaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new FormSchemaValidator;
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function field(array $override = []): array
    {
        return $override + ['key' => 'name', 'type' => 'text', 'label' => ['ar' => 'الاسم', 'en' => 'Name'], 'required' => false, 'options' => []];
    }

    public function test_a_valid_schema_has_no_errors(): void
    {
        $errors = $this->validator->validate([
            $this->field(),
            $this->field(['key' => 'kind', 'type' => 'select', 'options' => [['value' => 'a', 'label' => ['ar' => 'أ']], ['value' => 'b', 'label' => ['ar' => 'ب']]]]),
            $this->field(['key' => 'extra', 'visible_if' => ['field' => 'kind', 'op' => 'eq', 'value' => 'a']]),
            $this->field(['key' => 'age', 'type' => 'integer', 'min' => 1, 'max' => 99]),
        ]);

        $this->assertSame([], $errors);
    }

    public function test_keys_must_be_well_formed_and_unique(): void
    {
        $errors = $this->validator->validate([
            $this->field(['key' => 'Bad Key']),
            $this->field(['key' => 'dup']),
            $this->field(['key' => 'dup']),
        ]);

        $this->assertArrayHasKey('0.key', $errors);
        $this->assertArrayNotHasKey('1.key', $errors);
        $this->assertArrayHasKey('2.key', $errors);
    }

    public function test_type_and_arabic_label_are_required(): void
    {
        $errors = $this->validator->validate([$this->field(['type' => 'colour', 'label' => ['en' => 'x']])]);

        $this->assertArrayHasKey('0.type', $errors);
        $this->assertArrayHasKey('0.label_ar', $errors);
    }

    public function test_option_types_need_valid_unique_options_and_other_types_none(): void
    {
        $errors = $this->validator->validate([
            $this->field(['key' => 'a', 'type' => 'select', 'options' => []]),
            $this->field(['key' => 'b', 'type' => 'multiselect', 'options' => [['value' => 'x y', 'label' => ['ar' => 'أ']], ['value' => 'ok', 'label' => []], ['value' => 'ok', 'label' => ['ar' => 'ب']]]]),
            $this->field(['key' => 'c', 'type' => 'text', 'options' => [['value' => 'x', 'label' => ['ar' => 'أ']]]]),
        ]);

        $this->assertArrayHasKey('0.options', $errors);
        $this->assertCount(3, $errors['1.options']);
        $this->assertArrayHasKey('2.options', $errors);
    }

    public function test_ranges_are_checked_against_the_field_type(): void
    {
        $errors = $this->validator->validate([
            $this->field(['key' => 'a', 'type' => 'boolean', 'min' => 1]),
            $this->field(['key' => 'b', 'type' => 'integer', 'min' => 5, 'max' => 2]),
            $this->field(['key' => 'c', 'type' => 'text', 'min' => -1]),
            $this->field(['key' => 'd', 'type' => 'integer', 'min' => 1.5]),
            $this->field(['key' => 'e', 'type' => 'decimal', 'min' => 1.5, 'max' => 2.5]),
            $this->field(['key' => 'f', 'type' => 'weight', 'min' => 'abc']),
        ]);

        $this->assertArrayHasKey('0.min', $errors);
        $this->assertArrayHasKey('1.max', $errors);
        $this->assertArrayHasKey('2.min', $errors);
        $this->assertArrayHasKey('3.min', $errors);
        $this->assertArrayNotHasKey('4.min', $errors);
        $this->assertArrayHasKey('5.min', $errors);
    }

    public function test_visible_if_must_reference_an_earlier_field_with_a_valid_condition(): void
    {
        $errors = $this->validator->validate([
            $this->field(['key' => 'a', 'visible_if' => ['field' => 'b', 'op' => 'eq', 'value' => 1]]),
            $this->field(['key' => 'b', 'type' => 'integer']),
            $this->field(['key' => 'c', 'visible_if' => ['field' => 'b', 'op' => 'nope', 'value' => 1]]),
            $this->field(['key' => 'd', 'visible_if' => ['field' => 'b', 'op' => 'in', 'value' => 'single']]),
            $this->field(['key' => 'e', 'visible_if' => ['field' => 'b', 'op' => 'gt', 'value' => 'abc']]),
            $this->field(['key' => 'f', 'visible_if' => ['field' => 'b', 'op' => 'filled']]),
            $this->field(['key' => 'g', 'visible_if' => ['field' => 'g', 'op' => 'filled']]),
        ]);

        $this->assertArrayHasKey('0.visible_if', $errors);
        $this->assertArrayHasKey('2.visible_if', $errors);
        $this->assertArrayHasKey('3.visible_if', $errors);
        $this->assertArrayHasKey('4.visible_if', $errors);
        $this->assertArrayNotHasKey('5.visible_if', $errors);
        $this->assertArrayHasKey('6.visible_if', $errors);
    }

    public function test_too_many_fields_are_rejected(): void
    {
        $fields = [];

        for ($i = 0; $i <= FormSchemaValidator::MAX_FIELDS; $i++) {
            $fields[] = $this->field(['key' => 'f'.$i]);
        }

        $this->assertArrayHasKey('schema', $this->validator->validate($fields));
    }

    public function test_assert_valid_throws_a_business_rule_violation_and_returns_definitions(): void
    {
        $definitions = $this->validator->assertValid([$this->field(['help' => ['ar' => 'مساعدة']])]);
        $this->assertSame('name', $definitions[0]->key);
        $this->assertSame(['ar' => 'مساعدة'], $definitions[0]->help);

        $this->expectException(BusinessRuleViolation::class);
        $this->validator->assertValid([$this->field(['key' => 'BAD'])]);
    }
}
