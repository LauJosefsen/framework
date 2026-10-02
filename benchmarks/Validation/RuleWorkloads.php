<?php

namespace Illuminate\Benchmarks\Validation;

use Illuminate\Contracts\Validation\Rule as RuleContract;
use Illuminate\Support\Fluent;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Stringable;

class RuleWorkloads
{
    public static function parserCases(): array
    {
        return [
            'simple', 'parameters', 'csv', 'regex', 'aliases', 'unicode', 'controls',
            'length_255', 'length_256', 'length_257', 'array_rules', 'rule_objects',
            'stringable', 'capacity_128', 'capacity_256', 'capacity_257', 'unique_4096', 'mixed',
        ];
    }

    public static function parserRules(string $case): array
    {
        return match ($case) {
            'simple' => ['required', 'nullable', 'integer', 'string', 'sometimes', 'bail'],
            'parameters' => ['min:1', 'max:255', 'between:1,100', 'digits_between:2,8', 'date_format:Y-m-d'],
            'csv' => ['in:"red,blue",green', 'in:"a""b",c', 'in:a\\b,c', 'required_if:status,ready,done'],
            'regex' => ['regex:/^[a-z,|]+$/i', 'not_regex:/^(red|blue)$/', 'notregex:/^x:y$/'],
            'aliases' => ['int', 'bool', 'INT', 'Bool', '0', '00', '', ' max :10 '],
            'unicode' => ['in:café,tea', 'in:æ,ø,å', 'regex:/^[æøå]+$/u', "in:\xE9,x"],
            'controls' => ["in:a\nb,c", "in:x\0y,z", "in:a\tb,c", "in:a\rb,c"],
            'length_255', 'length_256', 'length_257' => ['in:'.str_repeat('x', (int) substr($case, 7) - 3)],
            'array_rules' => [['min', 1], ['between', 1, 100], ['in', new Fluent(['value' => 'red'])]],
            'rule_objects' => [new class implements RuleContract
            {
                public function passes($attribute, $value)
                {
                    return true;
                }

                public function message()
                {
                    return 'invalid';
                }
            }],
            'stringable' => [new class implements Stringable
            {
                public function __toString(): string
                {
                    return 'in:red,green,blue';
                }
            }],
            'capacity_128', 'capacity_256', 'capacity_257', 'unique_4096' => array_map(
                static fn (int $i) => 'min:'.$i,
                range(0, (int) substr($case, strrpos($case, '_') + 1) - 1)
            ),
            'mixed' => array_merge(...array_map(
                static fn (int $i) => ['required', 'integer', 'nullable', 'min:'.$i],
                range(0, 4095)
            )),
            default => throw new InvalidArgumentException('Unknown parser case: '.$case),
        };
    }

    public static function validatorCases(): array
    {
        return [
            'scalars', 'invalid_scalars', 'nullable', 'numeric', 'csv', 'regex', 'unicode',
            'dates', 'conditional', 'confirmed', 'objects', 'wildcards_25',
            'invalid_wildcards_100', 'dynamic_in', 'dynamic_minimums',
        ];
    }

    public static function validationInput(string $case, int $request = 0): array
    {
        return match ($case) {
            'scalars', 'invalid_scalars' => [
                $case === 'scalars'
                    ? ['name' => 'Synthetic', 'age' => 42, 'terms' => '1']
                    : ['name' => 'x', 'age' => 'invalid', 'terms' => '0'],
                ['name' => 'required|string|min:2|max:80', 'age' => 'required|integer|min:18|max:120', 'terms' => 'accepted'],
                $case === 'scalars',
            ],
            'nullable' => [
                ['name' => null, 'age' => null],
                ['name' => 'nullable|string|max:80', 'age' => 'nullable|integer|min:18', 'note' => 'sometimes|string'],
                true,
            ],
            'numeric' => [
                ['amount' => '12.50', 'count' => 4, 'code' => '123456'],
                ['amount' => 'required|numeric|decimal:2', 'count' => 'integer|between:1,10|multiple_of:2', 'code' => 'digits:6'],
                true,
            ],
            'csv' => [['color' => 'red,blue'], ['color' => ['required', 'in:"red,blue",green']], true],
            'regex' => [
                ['code' => 'ab,cd'],
                ['code' => ['required', 'regex:/^[a-z,|]+$/', 'not_regex:/^x:y$/']],
                true,
            ],
            'unicode' => [
                ['drink' => 'café', 'code' => 'øå'],
                ['drink' => 'in:café,tea', 'code' => ['regex:/^[æøå]+$/u']],
                true,
            ],
            'dates' => [
                ['start' => '2026-01-02', 'end' => '2026-01-03'],
                ['start' => 'required|date_format:Y-m-d|before:end', 'end' => 'required|date_format:Y-m-d|after:start'],
                true,
            ],
            'conditional' => [
                ['flag' => 'yes', 'details' => 'Synthetic'],
                ['flag' => 'in:yes,no', 'details' => 'required_if:flag,yes|string|min:2', 'discount' => 'exclude_unless:flag,no|integer'],
                true,
            ],
            'confirmed' => [
                ['secret' => 'Synthetic1234', 'secret_confirmation' => 'Synthetic1234'],
                ['secret' => 'required|string|min:8|confirmed'],
                true,
            ],
            'objects' => [
                ['color' => 'red', 'count' => 2],
                ['color' => ['required', Rule::in(['red', 'blue'])], 'count' => [Rule::requiredIf(true), 'integer']],
                true,
            ],
            'wildcards_25', 'invalid_wildcards_100' => [
                ['items' => array_map(static fn (int $i) => [
                    'id' => $case === 'invalid_wildcards_100' && $i === 80 ? 'invalid' : $i,
                    'name' => 'Synthetic '.$i,
                ], range(1, $case === 'wildcards_25' ? 25 : 100))],
                ['items' => 'required|array', 'items.*.id' => 'required|integer|distinct', 'items.*.name' => 'required|string|max:40'],
                $case === 'wildcards_25',
            ],
            'dynamic_in' => [
                ['color' => $request % 2 === 0 ? 'red' : 'green'],
                ['color' => ['required', Rule::in($request % 2 === 0 ? ['red', 'blue'] : ['green', 'yellow'])]],
                true,
            ],
            'dynamic_minimums' => [
                array_fill_keys(array_map(static fn (int $i) => 'field_'.$i, range(0, 15)), 1000000),
                array_combine(
                    array_map(static fn (int $i) => 'field_'.$i, range(0, 15)),
                    array_map(static fn (int $i) => 'required|integer|min:'.(($request * 16 + $i) % 4096), range(0, 15))
                ),
                true,
            ],
            default => throw new InvalidArgumentException('Unknown validator case: '.$case),
        };
    }
}
