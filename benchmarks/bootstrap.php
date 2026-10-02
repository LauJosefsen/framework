<?php

$frameworkRoot = dirname(__DIR__);
$autoload = getenv('FRAMEWORK_BENCH_AUTOLOAD') ?: $frameworkRoot.'/vendor/autoload.php';
$baselineParser = getenv('FRAMEWORK_BENCH_PARSER');

require $autoload;

spl_autoload_register(static function (string $class) use ($frameworkRoot, $baselineParser): void {
    if ($class === Illuminate\Validation\ValidationRuleParser::class && $baselineParser) {
        require $baselineParser;

        return;
    }

    if (str_starts_with($class, 'Illuminate\\Benchmarks\\Validation\\')) {
        $path = __DIR__.'/Validation/'.substr($class, strlen('Illuminate\\Benchmarks\\Validation\\')).'.php';
    } elseif (str_starts_with($class, 'Illuminate\\')) {
        $path = $frameworkRoot.'/src/'.str_replace('\\', '/', $class).'.php';
    } else {
        return;
    }

    if (is_file($path)) {
        require $path;
    }
}, prepend: true);

$expectedParser = $baselineParser ?: $frameworkRoot.'/src/Illuminate/Validation/ValidationRuleParser.php';
$actualParser = (new ReflectionClass(Illuminate\Validation\ValidationRuleParser::class))->getFileName();
if (realpath($actualParser) !== realpath($expectedParser)) {
    throw new RuntimeException('The benchmark loaded an unexpected validation parser.');
}

$hasCache = property_exists(Illuminate\Validation\ValidationRuleParser::class, 'parsedStringRules');
if ($hasCache === (bool) $baselineParser) {
    throw new RuntimeException('The benchmark parser does not match its baseline/candidate role.');
}

if ($hasCache && (new ReflectionProperty(Illuminate\Validation\ValidationRuleParser::class, 'parsedStringRules'))->getValue() !== []) {
    throw new RuntimeException('Each benchmark process must start with an empty parsed-rule cache.');
}

if (function_exists('opcache_get_status')) {
    $jit = opcache_get_status(false)['jit'] ?? [];
    if (($jit['on'] ?? false) || ($jit['enabled'] ?? false)) {
        throw new RuntimeException('Validation benchmarks require JIT to be disabled.');
    }
}

if (function_exists('xdebug_info') && xdebug_info('mode')) {
    throw new RuntimeException('Validation benchmarks require Xdebug activity to be disabled.');
}
