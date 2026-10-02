<?php

require __DIR__.'/bootstrap.php';

use Illuminate\Benchmarks\Validation\RuleWorkloads;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Fluent;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;

$normalize = function ($value) use (&$normalize) {
    if (is_array($value)) {
        return array_map($normalize, $value);
    }

    if ($value instanceof Rule) {
        return ['rule-object'];
    }

    if ($value instanceof Fluent) {
        return ['fluent', $value->getAttributes()];
    }

    return $value;
};

$hash = hash_init('sha256');
$checks = 0;
$emit = static function ($value) use ($hash, &$checks, $normalize): void {
    hash_update($hash, serialize($normalize($value)));
    $checks++;
};

foreach (RuleWorkloads::parserCases() as $case) {
    $rules = RuleWorkloads::parserRules($case);
    for ($i = 0; $i < 20000; $i++) {
        $parsed = ValidationRuleParser::parse($rules[$i % count($rules)]);
        $emit([$case, $parsed]);
        $parsed[0] = 'Modified';
        $parsed[1][] = 'modified';
    }
}

$translator = new Translator(new ArrayLoader, 'en');
foreach (RuleWorkloads::validatorCases() as $case) {
    for ($request = 0; $request < 32; $request++) {
        [$data, $rules, $expected] = RuleWorkloads::validationInput($case, $request);
        $validator = new Validator($translator, $data, $rules);
        $valid = $validator->passes();
        if ($valid !== $expected) {
            throw new RuntimeException('Unexpected validation outcome for '.$case);
        }
        $emit([$case, $valid, $validator->errors()->messages()]);
    }
}

echo json_encode(['checks' => $checks, 'sha256' => hash_final($hash)], JSON_PRETTY_PRINT).PHP_EOL;
