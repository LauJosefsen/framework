<?php

namespace Illuminate\Benchmarks\Validation;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\ValidationRuleParser;
use Illuminate\Validation\Validator;
use ReflectionProperty;
use RuntimeException;

abstract class WorkerBenchmark
{
    private array $rules;

    private string $case;

    private int $cursor = 0;

    private Translator $translator;

    private ?string $parserCase = null;

    public function parserCases(): iterable
    {
        foreach (RuleWorkloads::parserCases() as $case) {
            yield $case => ['case' => $case];
        }
    }

    public function validatorCases(): iterable
    {
        foreach (RuleWorkloads::validatorCases() as $case) {
            yield $case => ['case' => $case];
        }
    }

    public function prepareParser(array $params): void
    {
        $this->rules = RuleWorkloads::parserRules($params['case']);
        $this->parserCase = $params['case'];
        $this->cursor = 0;
    }

    public function prepareValidator(array $params): void
    {
        $this->case = $params['case'];
        $this->parserCase = null;
        $this->cursor = 0;
        $this->translator = new Translator(new ArrayLoader, 'en');
    }

    public function verifyState(): void
    {
        if (! property_exists(ValidationRuleParser::class, 'parsedStringRules')) {
            return;
        }

        $count = count((new ReflectionProperty(ValidationRuleParser::class, 'parsedStringRules'))->getValue());
        if ($count > 256) {
            throw new RuntimeException('Parsed-rule cache exceeded its capacity.');
        }

        if ($this->parserCase === null) {
            return;
        }

        $uncached = in_array($this->parserCase, ['unicode', 'controls', 'length_257', 'array_rules', 'rule_objects', 'stringable'], true);
        if (($count === 0) !== $uncached) {
            throw new RuntimeException('Unexpected cache state for '.$this->parserCase);
        }
    }

    protected function parseRequest(): void
    {
        $count = count($this->rules);
        for ($i = 0; $i < 32; $i++) {
            ValidationRuleParser::parse($this->rules[$this->cursor % $count]);
            $this->cursor++;
        }
    }

    protected function validateRequest(): void
    {
        [$data, $rules, $expected] = RuleWorkloads::validationInput($this->case, $this->cursor++);
        $validator = new Validator($this->translator, $data, $rules);

        if ($validator->passes() !== $expected) {
            throw new RuntimeException('Unexpected validation outcome for '.$this->case);
        }

        if (! $expected) {
            $validator->errors()->all();
        }
    }
}
