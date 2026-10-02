<?php

namespace Illuminate\Tests\Validation;

use Illuminate\Support\Fluent;
use Illuminate\Validation\ValidationRuleParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class ValidationRuleParserCacheTest extends TestCase
{
    #[DataProvider('stringRules')]
    public function testStringRuleResultsRemainStableAcrossRepetitionAndEviction($rule, $expected)
    {
        $this->assertSame($expected, ValidationRuleParser::parse($rule));
        $parsed = ValidationRuleParser::parse($rule);
        $parsed[0] = 'Modified';
        $parsed[1][] = 'modified';
        $this->assertSame($expected, ValidationRuleParser::parse($rule));

        for ($i = 0; $i < 300; $i++) {
            ValidationRuleParser::parse('min:'.$i);
        }

        $this->assertSame($expected, ValidationRuleParser::parse($rule));
    }

    public static function stringRules(): array
    {
        $cases = [
            'empty' => ['', ['', []]],
            'spaces' => ['  required  ', ['Required', []]],
            'required' => ['required', ['Required', []]],
            'nullable' => ['nullable', ['Nullable', []]],
            'integer' => ['integer', ['Integer', []]],
            'integer alias' => ['int', ['Integer', []]],
            'boolean alias' => ['bool', ['Boolean', []]],
            'boolean' => ['boolean', ['Boolean', []]],
            'capitalized alias' => ['Bool', ['Boolean', []]],
            'numeric name' => ['0', ['0', []]],
            'leading zero name' => ['00', ['00', []]],
            'min' => ['min:1', ['Min', ['1']]],
            'negative minimum' => ['min:-1', ['Min', ['-1']]],
            'decimal minimum' => ['min:1.5', ['Min', ['1.5']]],
            'max' => ['max:255', ['Max', ['255']]],
            'between' => ['between:1,9', ['Between', ['1', '9']]],
            'decimal' => ['decimal:2,4', ['Decimal', ['2', '4']]],
            'digits' => ['digits_between:2,8', ['DigitsBetween', ['2', '8']]],
            'date' => ['date_format:Y-m-d', ['DateFormat', ['Y-m-d']]],
            'date punctuation' => ['date_format:Y-m-d H:i:s', ['DateFormat', ['Y-m-d H:i:s']]],
            'confirmation field' => ['confirmed:confirmation', ['Confirmed', ['confirmation']]],
            'required if' => ['required_if:status,ready', ['RequiredIf', ['status', 'ready']]],
            'exclude unless' => ['exclude_unless:status,ready', ['ExcludeUnless', ['status', 'ready']]],
            'required without' => ['required_without:first,second', ['RequiredWithout', ['first', 'second']]],
            'empty parameter' => ['in:', ['In', [null]]],
            'empty csv fields' => ['in:,', ['In', ['', '']]],
            'trailing csv field' => ['in:red,', ['In', ['red', '']]],
            'quoted comma' => ['in:"red,blue",green', ['In', ['red,blue', 'green']]],
            'escaped quote' => ['in:"a""b",c', ['In', ['a"b', 'c']]],
            'backslash' => ['in:a\\b,c', ['In', ['a\\b', 'c']]],
            'regex punctuation' => ['regex:/^(red|blue),green$/', ['Regex', ['/^(red|blue),green$/']]],
            'not regex' => ['not_regex:/^x:y$/i', ['NotRegex', ['/^x:y$/i']]],
            'unicode parameters' => ['in:café,tea', ['In', ['café', 'tea']]],
            'nul' => ["in:x\0y,z", ['In', ["x\0y", 'z']]],
            'line break' => ["in:a\nb,c", ['In', ["a\nb", 'c']]],
            'tab' => ["in:a\tb,c", ['In', ["a\tb", 'c']]],
        ];

        foreach ([254, 255, 256, 257, 258, 4096] as $length) {
            $parameter = str_repeat('x', $length - 3);
            $cases['length '.$length] = ['in:'.$parameter, ['In', [$parameter]]];
        }

        return $cases;
    }

    public function testArrayParametersRetainTheirTypesAndObjectIdentity()
    {
        $value = new Fluent(['value' => 'red']);
        $rule = ['in', 1, null, false, $value];
        $expected = ['In', [1, null, false, $value]];

        $this->assertSame($expected, ValidationRuleParser::parse($rule));
        $value->value = 'blue';
        $this->assertSame($expected, ValidationRuleParser::parse($rule));
        $this->assertSame('blue', ValidationRuleParser::parse($rule)[1][3]->value);
    }

    public function testNonCacheableRulesDoNotRetainParameters()
    {
        $cache = new ReflectionProperty(ValidationRuleParser::class, 'parsedStringRules');
        $previous = $cache->getValue();
        $cache->setValue(null, []);

        try {
            foreach (['in:café,tea', "in:a\nb,c", "in:x\0y,z", 'in:'.str_repeat('x', 254)] as $rule) {
                ValidationRuleParser::parse($rule);
            }

            $this->assertSame([], $cache->getValue());
        } finally {
            $cache->setValue(null, $previous);
        }
    }

    public function testPrintableRulesAreIndependentOfLocaleChanges()
    {
        $previous = setlocale(LC_CTYPE, 0);
        $rules = [
            ['in:"red,blue",green', ['In', ['red,blue', 'green']]],
            ['date_format:Y-m-d H:i:s', ['DateFormat', ['Y-m-d H:i:s']]],
            ['required_if:flag,yes', ['RequiredIf', ['flag', 'yes']]],
        ];

        try {
            foreach (['C', 'C.UTF-8', 'C'] as $locale) {
                if (setlocale(LC_CTYPE, $locale) === false) {
                    continue;
                }

                foreach ($rules as [$rule, $expected]) {
                    $this->assertSame($expected, ValidationRuleParser::parse($rule));
                }
            }
        } finally {
            setlocale(LC_CTYPE, $previous);
        }
    }
}
