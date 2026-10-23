<?php

$target = getenv('NANOID_BENCH_TARGET') ?: 'current';
$implementation = getenv('NANOID_BENCH_LABEL') ?: null;

if ($target === 'current') {
    $autoload = getenv('NANOID_BENCH_AUTOLOAD') ?: __DIR__ . '/../vendor/autoload.php';
    if (!is_file($autoload)) {
        throw new RuntimeException('Composer autoload file not found: ' . $autoload);
    }
    require $autoload;

    $custom = \Hidehalo\Nanoid\customAlphabet('abcdefghijklmnopqrstuvwxyz', 21);
    $cases = [
        'secure_url_21' => static function () {
            return \Hidehalo\Nanoid\nanoid();
        },
        'secure_custom_26x21' => static function () use ($custom) {
            return $custom();
        },
        'non_secure_url_21' => static function () {
            return \Hidehalo\Nanoid\NonSecure\nanoid();
        },
    ];
    $implementation = $implementation ?: 'jooosi-project/nanoid-php';
} elseif ($target === 'hidehalo') {
    $legacyDirectory = getenv('NANOID_BENCH_LEGACY_DIR');
    if (!$legacyDirectory || !is_dir($legacyDirectory . '/src')) {
        throw new RuntimeException('Set NANOID_BENCH_LEGACY_DIR to the Hidehalo Nano ID checkout.');
    }

    require $legacyDirectory . '/src/GeneratorInterface.php';
    require $legacyDirectory . '/src/CoreInterface.php';
    require $legacyDirectory . '/src/Core.php';
    require $legacyDirectory . '/src/Generator.php';
    require $legacyDirectory . '/src/Client.php';

    $client = new \Hidehalo\Nanoid\Client(21);
    $cases = [
        'secure_url_21' => static function () use ($client) {
            return $client->generateId(21, \Hidehalo\Nanoid\Client::MODE_DYNAMIC);
        },
        'secure_custom_26x21' => static function () use ($client) {
            return $client->formattedId('abcdefghijklmnopqrstuvwxyz', 21);
        },
        'non_secure_url_21' => static function () use ($client) {
            return $client->generateId(21, \Hidehalo\Nanoid\Client::MODE_NORMAL);
        },
    ];
    $implementation = $implementation ?: 'hidehalo/nanoid-php';
} else {
    throw new InvalidArgumentException('NANOID_BENCH_TARGET must be "current" or "hidehalo".');
}

$iterations = max(1, (int) (getenv('NANOID_BENCH_ITERATIONS') ?: 100000));
$repetitions = max(1, (int) (getenv('NANOID_BENCH_REPETITIONS') ?: 7));
$warmupIterations = min(3000, $iterations);

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
    $middle = (int) floor(count($samples) / 2);
    $medianSeconds = count($samples) % 2 === 0
        ? ($samples[$middle - 1] + $samples[$middle]) / 2
        : $samples[$middle];
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
    'implementation' => $implementation,
    'target' => $target,
    'php_version' => PHP_VERSION,
    'iterations_per_round' => $iterations,
    'repetitions' => $repetitions,
    'warmup_iterations' => $warmupIterations,
    'results' => $results,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
