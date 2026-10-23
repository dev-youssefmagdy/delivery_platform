<?php

namespace Tests\Unit\Catalog;

use App\Services\Catalog\Forms\ServiceFormValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Compiles schemas to Laravel rules and validates `service_details` (Plan P1.4 step 2).
 */
class ServiceFormValidatorTest extends TestCase
{
    private ServiceFormValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->validator = new ServiceFormValidator;
    }

    /**
     * @param array<string, mixed> $override
     * @return array<string, mixed>
     */
    private function field(string $key, string $type, array $override = []): array
    {
        return $override + ['key' => $key, 'type' => $type, 'label' => ['ar' => $key], 'required' => false, 'options' => []];
    }

    /**
     * @param list<array<string, mixed>> $schema
     * @param array<string, mixed> $details
     * @return array<string, list<string>>
     */
    private function errors(array $schema, array $details): array
    {
        try {
            $this->validator->validate($schema, $details);
        } catch (ValidationException $exception) {
            return $exception->errors();
        }

        return [];
    }

    private function option(string $value): array
    {
        return ['value' => $value, 'label' => ['ar' => $value]];
    }

    public function test_valid_details_are_returned_for_every_field_type(): void
    {
        $schema = [
            $this->field('t', 'text', ['required' => true, 'max' => 10]),
            $this->field('ta', 'textarea'),
            $this->field('i', 'integer', ['min' => 1, 'max' => 5]),
            $this->field('d', 'decimal', ['min' => 0.5]),
            $this->field('b', 'boolean', ['required' => true]),
            $this->field('s', 'select', ['options' => [$this->option('x'), $this->option('y')]]),
            $this->field('m', 'multiselect', ['options' => [$this->option('x'), $this->option('y')], 'max' => 2]),
            $this->field('dt', 'date'),
            $this->field('tm', 'time'),
            $this->field('dtt', 'datetime'),
            $this->field('c', 'contact'),
            $this->field('p', 'photo', ['max' => 2]),
            $this->field('ic', 'item_checklist', ['options' => [$this->option('sofa'), $this->option('bed')]]),
            $this->field('dim', 'dimensions', ['max' => 300]),
            $this->field('w', 'weight', ['max' => 100]),
        ];
        $details = [
            't' => 'hello', 'ta' => 'long text', 'i' => 3, 'd' => '1.25', 'b' => false, 's' => 'x', 'm' => ['x', 'y'],
            'dt' => '2026-10-20', 'tm' => '09:30', 'dtt' => '2026-10-20T09:30',
            'c' => ['name' => 'Ali', 'phone' => '+201001234567'], 'p' => ['9b1f7c1e-52a4-4e57-9a53-1a2b3c4d5e6f'],
            'ic' => [['item' => 'sofa', 'quantity' => 2]], 'dim' => ['length' => 100, 'width' => 50, 'height' => 40], 'w' => 12.5,
        ];

        $this->assertSame($details, $this->validator->validate($schema, $details));
    }

    public function test_required_and_type_errors_use_service_details_keys(): void
    {
        $schema = [
            $this->field('t', 'text', ['required' => true]),
            $this->field('i', 'integer', ['min' => 1, 'max' => 5]),
            $this->field('s', 'select', ['options' => [$this->option('x')]]),
            $this->field('dtt', 'datetime'),
        ];

        $errors = $this->errors($schema, ['i' => 9, 's' => 'nope', 'dtt' => 'tomorrow']);

        $this->assertArrayHasKey('service_details.t', $errors);
        $this->assertArrayHasKey('service_details.i', $errors);
        $this->assertArrayHasKey('service_details.s', $errors);
        $this->assertArrayHasKey('service_details.dtt', $errors);
    }

    public function test_structured_fields_reject_bad_shapes(): void
    {
        $schema = [
            $this->field('c', 'contact', ['required' => true]),
            $this->field('ic', 'item_checklist', ['options' => [$this->option('sofa')]]),
            $this->field('dim', 'dimensions', ['max' => 100]),
            $this->field('m', 'multiselect', ['options' => [$this->option('x')]]),
        ];

        $errors = $this->errors($schema, [
            'c' => ['name' => 'Ali', 'phone' => '12'],
            'ic' => [['item' => 'piano', 'quantity' => 0]],
            'dim' => ['length' => 500, 'width' => 1],
            'm' => ['x', 'x', 'z'],
        ]);

        $this->assertArrayHasKey('service_details.c.phone', $errors);
        $this->assertArrayHasKey('service_details.ic.0.item', $errors);
        $this->assertArrayHasKey('service_details.ic.0.quantity', $errors);
        $this->assertArrayHasKey('service_details.dim', $errors);
        $this->assertArrayHasKey('service_details.dim.length', $errors);
        $this->assertArrayHasKey('service_details.m.1', $errors);
        $this->assertArrayHasKey('service_details.m.2', $errors);
    }

    public function test_a_contact_missing_its_phone_is_rejected(): void
    {
        $errors = $this->errors([$this->field('c', 'contact')], ['c' => ['name' => 'Ali']]);

        $this->assertArrayHasKey('service_details.c', $errors);
    }

    public function test_unknown_keys_are_rejected(): void
    {
        $errors = $this->errors([$this->field('t', 'text')], ['t' => 'ok', 'hacked' => 'yes']);

        $this->assertArrayHasKey('service_details.hacked', $errors);
        $this->assertArrayNotHasKey('service_details.t', $errors);
    }

    public function test_hidden_fields_are_not_required_and_are_dropped_from_the_result(): void
    {
        $schema = [
            $this->field('elevator', 'boolean', ['required' => true]),
            $this->field('carry', 'integer', ['required' => true, 'min' => 0, 'visible_if' => ['field' => 'elevator', 'op' => 'eq', 'value' => false]]),
        ];

        $this->assertSame(['elevator' => true], $this->validator->validate($schema, ['elevator' => true, 'carry' => 999]));
        $this->assertArrayHasKey('service_details.carry', $this->errors($schema, ['elevator' => false]));
        $this->assertSame(['elevator' => false, 'carry' => 20], $this->validator->validate($schema, ['elevator' => false, 'carry' => 20]));
    }

    public function test_visibility_chains_through_hidden_fields(): void
    {
        $schema = [
            $this->field('a', 'boolean'),
            $this->field('b', 'integer', ['visible_if' => ['field' => 'a', 'op' => 'eq', 'value' => true]]),
            $this->field('c', 'text', ['required' => true, 'visible_if' => ['field' => 'b', 'op' => 'gte', 'value' => 3]]),
        ];

        // a is false -> b hidden -> c hidden even though b was sent as 5.
        $this->assertSame(['a' => false], $this->validator->validate($schema, ['a' => false, 'b' => 5]));
        $this->assertArrayHasKey('service_details.c', $this->errors($schema, ['a' => true, 'b' => 5]));
    }

    public function test_compiled_rules_follow_current_visibility(): void
    {
        $schema = [
            $this->field('a', 'boolean'),
            $this->field('b', 'integer', ['visible_if' => ['field' => 'a', 'op' => 'eq', 'value' => true]]),
        ];

        $this->assertArrayNotHasKey('service_details.b', $this->validator->rules($schema, ['a' => false]));
        $this->assertArrayHasKey('service_details.b', $this->validator->rules($schema, ['a' => true]));
    }
}
