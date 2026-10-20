<?php

namespace Tests\Unit\Catalog;

use App\Enums\Catalog\VisibilityOperator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VisibilityOperatorTest extends TestCase
{
    /**
     * @return array<string, array{VisibilityOperator, mixed, mixed, bool}>
     */
    public static function cases(): array
    {
        return [
            'eq bool vs 1' => [VisibilityOperator::Equals, true, 1, true],
            'eq string vs number' => [VisibilityOperator::Equals, '5', 5, true],
            'eq unanswered' => [VisibilityOperator::Equals, null, 'x', false],
            'neq unanswered' => [VisibilityOperator::NotEquals, null, 'x', true],
            'neq different' => [VisibilityOperator::NotEquals, 'a', 'b', true],
            'in list' => [VisibilityOperator::In, 'b', ['a', 'b'], true],
            'in multiselect' => [VisibilityOperator::In, ['x', 'b'], ['b'], true],
            'not_in' => [VisibilityOperator::NotIn, 'z', ['a', 'b'], true],
            'gt' => [VisibilityOperator::GreaterThan, 3, 2, true],
            'gt equal' => [VisibilityOperator::GreaterThan, 2, 2, false],
            'gte' => [VisibilityOperator::GreaterOrEqual, 2, 2, true],
            'lt non numeric' => [VisibilityOperator::LessThan, 'abc', 2, false],
            'lte' => [VisibilityOperator::LessOrEqual, '2', 2, true],
            'filled zero' => [VisibilityOperator::Filled, 0, null, true],
            'filled empty array' => [VisibilityOperator::Filled, [], null, false],
            'empty string' => [VisibilityOperator::Empty, '', null, true],
            'false is filled' => [VisibilityOperator::Filled, false, null, true],
        ];
    }

    #[DataProvider('cases')]
    public function test_matches(VisibilityOperator $operator, mixed $actual, mixed $expected, bool $result): void
    {
        $this->assertSame($result, $operator->matches($actual, $expected));
    }

    public function test_only_filled_and_empty_need_no_value(): void
    {
        $this->assertFalse(VisibilityOperator::Filled->needsValue());
        $this->assertFalse(VisibilityOperator::Empty->needsValue());
        $this->assertTrue(VisibilityOperator::Equals->needsValue());
    }
}
