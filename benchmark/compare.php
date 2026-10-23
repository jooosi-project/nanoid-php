<?php

if ($argc < 3) {
    fwrite(STDERR, "Usage: php compare.php current.json hidehalo.json [hidehalo-ref] [hidehalo-sha] [current-sha]\n");
    exit(2);
}

$current = readBenchmark($argv[1]);
$hidehalo = readBenchmark($argv[2]);
$hidehaloRef = $argv[3] ?? 'unknown';
$hidehaloSha = $argv[4] ?? 'unknown';
$currentSha = $argv[5] ?? 'unknown';

if ($current['target'] !== 'current' || $hidehalo['target'] !== 'hidehalo') {
    throw new RuntimeException('Expected current and hidehalo benchmark result files.');
}
if ($current['php_version'] !== $hidehalo['php_version']) {
    throw new RuntimeException('Benchmark results use different PHP versions.');
}
if ($current['iterations_per_round'] !== $hidehalo['iterations_per_round'] ||
    $current['repetitions'] !== $hidehalo['repetitions']) {
    throw new RuntimeException('Benchmark result files use different run settings.');
}

$labels = [
    'secure_url_21' => 'Secure URL ID (21 characters)',
    'secure_custom_26x21' => 'Secure custom alphabet (26 symbols, 21 characters)',
    'non_secure_url_21' => 'Non-secure URL ID (21 characters)',
];

$rows = [];
foreach ($labels as $key => $label) {
    if (!isset($current['results'][$key]['median_ids_per_second'], $hidehalo['results'][$key]['median_ids_per_second'])) {
        throw new RuntimeException('Missing benchmark case: ' . $key);
    }

    $currentRate = (float) $current['results'][$key]['median_ids_per_second'];
    $hidehaloRate = (float) $hidehalo['results'][$key]['median_ids_per_second'];
    $ratio = $hidehaloRate > 0 ? $currentRate / $hidehaloRate : 0;

    $rows[] = sprintf(
        '| %s | %s | %s | %.2f× |',
        $label,
        number_format($currentRate, 0, '.', ','),
        number_format($hidehaloRate, 0, '.', ','),
        $ratio
    );
}

$tagUrl = 'https://github.com/hidehalo/nanoid-php/releases/tag/' . rawurlencode($hidehaloRef);

echo "## Benchmark: this branch vs Hidehalo Nano ID\n\n";
echo sprintf(
    "- Current commit: `%s` (`%s`)\n- Hidehalo release: [`%s`](%s) (`%s`)\n- PHP: `%s`; `%s` IDs per round, `%s` measured rounds, median timings after `%s` warmup IDs per workload.\n\n",
    $currentSha,
    $current['implementation'],
    $hidehaloRef,
    $tagUrl,
    $hidehaloSha,
    $current['php_version'],
    number_format((int) $current['iterations_per_round'], 0, '.', ','),
    $current['repetitions'],
    number_format((int) $current['warmup_iterations'], 0, '.', ',')
);
echo "| Workload | This branch (IDs/s) | Hidehalo (IDs/s) | Speed ratio (this / Hidehalo) |\n";
echo "| --- | ---: | ---: | ---: |\n";
echo implode("\n", $rows) . "\n\n";
echo "Results are informational; hosted-runner load affects timings and does not fail CI on a slower sample.\n";

/** @return array<string, mixed> */
function readBenchmark($path)
{
    if (!is_file($path)) {
        throw new RuntimeException('Benchmark result file not found: ' . $path);
    }

    $decoded = json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid benchmark result file: ' . $path);
    }

    return $decoded;
}
