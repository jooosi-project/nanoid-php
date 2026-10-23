<?php
require __DIR__ . '/../vendor/autoload.php';

use function Hidehalo\Nanoid\customAlphabet;
use function Hidehalo\Nanoid\nanoid;
use function Hidehalo\Nanoid\NonSecure\nanoid as nonSecureNanoid;

$iterations = max(1, (int) (getenv('NANOID_BENCH_ITERATIONS') ?: 100000));
$repetitions = max(1, (int) (getenv('NANOID_BENCH_REPETITIONS') ?: 7));
$warmupIterations = min(3000, $iterations);
$custom = customAlphabet('abcdefghijklmnopqrstuvwxyz', 21);

$cases = [
    'secure_url_21' => static function () {
        return nanoid();
    },
    'secure_custom_26x21' => static function () use ($custom) {
        return $custom();
    },
    'non_secure_url_21' => static function () {
        return nonSecureNanoid();
    },
];

gc_disable();
$results = [];
foreach ($cases as $name => $generate) {
    $warmupCharacters = 0;
    for ($index = 0; $index < $warmupIterations; $index++) {
        $warmupCharacters += strlen($generate());
    }

    $samples = [];
    $characters = 0;
    for ($round = 0; $round < $repetitions; $round++) {
        $roundCharacters = 0;
        $startedAt = hrtime(true);
        for ($index = 0; $index < $iterations; $index++) {
            $roundCharacters += strlen($generate());
        }
        $samples[] = (hrtime(true) - $startedAt) / 1e9;
        $characters = $roundCharacters;
    }

    sort($samples);
    $medianSeconds = $samples[(int) floor(count($samples) / 2)];
    $results[$name] = [
        'median_ids_per_second' => $iterations / $medianSeconds,
        'median_microseconds_per_id' => ($medianSeconds * 1e6) / $iterations,
        'median_seconds' => $medianSeconds,
        'sample_seconds' => $samples,
        'verified_characters_per_round' => $characters,
        'warmup_characters' => $warmupCharacters,
    ];
}

echo json_encode([
    'implementation' => 'hidehalo/nanoid-php 2.x function API',
    'php_version' => PHP_VERSION,
    'iterations_per_round' => $iterations,
    'repetitions' => $repetitions,
    'warmup_iterations' => $warmupIterations,
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
