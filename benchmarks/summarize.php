<?php

$rounds = (int) ($argv[1] ?? 3);
$directory = __DIR__.'/results';
$median = static function (array $values): float {
    sort($values);
    $count = count($values);

    return $count % 2 ? $values[intdiv($count, 2)] : ($values[$count / 2 - 1] + $values[$count / 2]) / 2;
};

$rows = [];
for ($round = 0; $round < $rounds; $round++) {
    foreach (['baseline', 'cached'] as $implementation) {
        $xml = simplexml_load_file($directory.'/'.$implementation.'-'.$round.'.xml');
        foreach ($xml->suite->benchmark as $benchmark) {
            $worker = str_contains((string) $benchmark['class'], 'NonPersistent') ? 'non-persistent' : 'persistent';
            foreach ($benchmark->subject as $subject) {
                foreach ($subject->variant as $variant) {
                    $revs = (int) $variant['revs'];
                    $warmup = (int) $variant['warmup'];
                    $expectedRevs = $worker === 'non-persistent' ? 1 : ((string) $subject['name'] === 'benchParseRequest' ? 2000 : 200);
                    $expectedIterations = $worker === 'non-persistent' ? 30 : 10;
                    if ($revs !== $expectedRevs || count($variant->iteration) !== $expectedIterations
                        || ($worker === 'non-persistent' && $warmup !== 0)
                        || ($worker === 'persistent' && $warmup === 0)) {
                        throw new RuntimeException('Unexpected process-lifetime or iteration settings.');
                    }
                    $set = (string) $variant->{'parameter-set'}['name'];
                    $key = $worker.'/'.(string) $subject['name'].'/'.$set;
                    $samples = [];
                    foreach ($variant->iteration as $iteration) {
                        $samples[] = (float) $iteration['time-avg'];
                    }
                    $rows[$key]['worker'] = $worker;
                    $rows[$key]['subject'] = (string) $subject['name'];
                    $rows[$key]['case'] = $set;
                    $rows[$key][$implementation][$round] = $median($samples);
                    $rows[$key]['samples'][$implementation][$round] = $samples;
                }
            }
        }
    }
}

ksort($rows);
$summary = [];
foreach ($rows as $row) {
    if (count($row['baseline']) !== $rounds || count($row['cached']) !== $rounds) {
        throw new RuntimeException('Missing comparison rounds.');
    }
    $changes = [];
    foreach ($row['baseline'] as $round => $time) {
        $changes[] = 100 * (1 - $row['cached'][$round] / $time);
    }
    $summary[] = [
        'worker' => $row['worker'], 'subject' => $row['subject'], 'case' => $row['case'],
        'baseline_us' => $median($row['baseline']), 'cached_us' => $median($row['cached']),
        'paired_reduction_percent' => $median($changes),
        'paired_min_percent' => min($changes), 'paired_max_percent' => max($changes),
        'per_round_baseline_us' => $row['baseline'], 'per_round_cached_us' => $row['cached'],
        'samples_us' => $row['samples'],
    ];
}
if (count($summary) !== 66) {
    throw new RuntimeException('Expected all 66 benchmark variants.');
}
file_put_contents($directory.'/summary.json', json_encode($summary, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL);
echo 'Completed '.count($summary).' variants in '.$rounds.' paired rounds.'.PHP_EOL;
