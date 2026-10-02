<?php

$root = dirname(__DIR__);
$baseline = $argv[1] ?? '13.x';
$phpbench = $argv[2] ?? __DIR__.'/vendor/bin/phpbench';
$rounds = (int) ($argv[3] ?? 3);
if ($rounds < 2 || ! is_file($phpbench)) {
    throw new InvalidArgumentException('Provide a PHPBench executable and at least two comparison rounds.');
}

$output = __DIR__.'/results';
if (! is_dir($output)) {
    mkdir($output, 0777, true);
}

$run = static function (array $command, string $stdout, string $stderr, ?array $env = null) use ($root): void {
    $process = proc_open($command, [0 => ['file', '/dev/null', 'r'], 1 => ['file', $stdout, 'w'], 2 => ['file', $stderr, 'w']], $pipes, $root, $env);
    if (! is_resource($process) || proc_close($process) !== 0) {
        throw new RuntimeException('Command failed: '.implode(' ', $command).PHP_EOL.file_get_contents($stdout).file_get_contents($stderr));
    }
};

$parser = $output.'/baseline-parser.php';
if (is_file($baseline)) {
    copy($baseline, $parser);
} else {
    $run(['git', 'show', $baseline.':src/Illuminate/Validation/ValidationRuleParser.php'], $parser, $output.'/git.log');
}

foreach (['baseline' => $parser, 'cached' => ''] as $variant => $source) {
    $env = array_merge(getenv(), ['FRAMEWORK_BENCH_PARSER' => $source, 'XDEBUG_MODE' => 'off']);
    $run([PHP_BINARY, '-d', 'opcache.jit=disable', '-d', 'opcache.jit_buffer_size=0', '-d', 'xdebug.mode=off', __DIR__.'/verify.php'], $output.'/'.$variant.'-checks.json', $output.'/'.$variant.'-checks.log', $env);
}
$original = json_decode(file_get_contents($output.'/baseline-checks.json'), true, flags: JSON_THROW_ON_ERROR);
$cached = json_decode(file_get_contents($output.'/cached-checks.json'), true, flags: JSON_THROW_ON_ERROR);
if ($original !== $cached) {
    throw new RuntimeException('Baseline and candidate correctness digests differ.');
}

for ($round = 0; $round < $rounds; $round++) {
    $variants = $round % 2 === 0 ? ['baseline', 'cached'] : ['cached', 'baseline'];
    foreach ($variants as $variant) {
        echo 'Round '.($round + 1).' / '.$rounds.': '.$variant.PHP_EOL;
        $env = array_merge(getenv(), ['FRAMEWORK_BENCH_PARSER' => $variant === 'baseline' ? $parser : '', 'XDEBUG_MODE' => 'off']);
        $run([
            PHP_BINARY, '-d', 'opcache.jit=disable', '-d', 'opcache.jit_buffer_size=0', '-d', 'xdebug.mode=off', $phpbench, 'run',
            '--config='.$root.'/phpbench.json.dist', '--no-ansi', '--progress=none',
            '--report=aggregate', '--dump-file='.$output.'/'.$variant.'-'.$round.'.xml',
        ], $output.'/'.$variant.'-'.$round.'.log', $output.'/'.$variant.'-'.$round.'.errors.log', $env);
    }
}

$run([PHP_BINARY, __DIR__.'/summarize.php', (string) $rounds], $output.'/summary.log', $output.'/summary.errors.log');
echo file_get_contents($output.'/summary.log');
