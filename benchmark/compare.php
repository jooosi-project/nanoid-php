<?php

if ($argc < 6 || $argc > 7) {
    fwrite(STDERR, "Usage: php compare.php current.json hidehalo.json hidehalo-ref hidehalo-sha current-sha [previous.json]\n");
    exit(2);
}

$current = readBenchmark($argv[1]);
$hidehalo = readBenchmark($argv[2]);
$hidehaloRef = $argv[3];
$hidehaloSha = $argv[4];
$currentSha = $argv[5];
$previous = $argc === 7 ? readBenchmark($argv[6]) : null;

if ($current['target'] !== 'current' || $hidehalo['target'] !== 'hidehalo') {
    throw new RuntimeException('Expected current and hidehalo benchmark results.');
}
if ($previous !== null && $previous['target'] !== 'current') {
    throw new RuntimeException('Expected the previous Nano ID benchmark result.');
}

assertSameBenchmarkSettings($current, $hidehalo);
if ($previous !== null) {
    assertSameBenchmarkSettings($current, $previous);
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
    if ($hidehaloRate <= 0) {
        throw new RuntimeException('Invalid Hidehalo benchmark rate for case: ' . $key);
    }
    $hidehaloChange = sprintf('%+.2f%%', (($currentRate - $hidehaloRate) / $hidehaloRate) * 100);

    $previousRate = null;
    $change = '—';
    if ($previous !== null) {
        if (!isset($previous['results'][$key]['median_ids_per_second'])) {
            throw new RuntimeException('Missing previous benchmark case: ' . $key);
        }

        $previousRate = (float) $previous['results'][$key]['median_ids_per_second'];
        if ($previousRate > 0) {
            $change = sprintf('%+.2f%%', (($currentRate - $previousRate) / $previousRate) * 100);
        }
    }

    $rows[] = sprintf(
        '| %s | %s | %s | %s | %s | %s |',
        $label,
        number_format($currentRate, 0, '.', ','),
        $previousRate === null ? '—' : number_format($previousRate, 0, '.', ','),
        number_format($hidehaloRate, 0, '.', ','),
        $change,
        $hidehaloChange
    );
}

$tagUrl = 'https://github.com/hidehalo/nanoid-php/releases/tag/' . rawurlencode($hidehaloRef);

echo "## Nano ID benchmark comparison\n\n";
echo sprintf("- Current commit: `%s` (`%s`)\n", $currentSha, $current['implementation']);
echo sprintf("- Hidehalo release: [`%s`](%s) (`%s`)\n", $hidehaloRef, $tagUrl, $hidehaloSha);
echo sprintf(
    "- PHP: `%s`; `%s` IDs per round, `%s` measured rounds, median timings after `%s` warmup IDs per workload.\n\n",
    $current['php_version'],
    number_format((int) $current['iterations_per_round'], 0, '.', ','),
    $current['repetitions'],
    number_format((int) $current['warmup_iterations'], 0, '.', ',')
);
echo "| Workload | Current (IDs/s) | Previous commit (IDs/s) | Hidehalo (IDs/s) | Change vs previous | Change vs Hidehalo |\n";
echo "| --- | ---: | ---: | ---: | ---: | ---: |\n";
echo implode("\n", $rows) . "\n\n";
echo "Results are informational; hosted-runner load affects timings and does not fail CI on a slower sample.\n";

/** @param array<string, mixed> $current
 *  @param array<string, mixed> $benchmark
 */
function assertSameBenchmarkSettings(array $current, array $benchmark): void
{
    if ($current['php_version'] !== $benchmark['php_version']) {
        throw new RuntimeException('Benchmark results use different PHP versions.');
    }
    if ($current['iterations_per_round'] !== $benchmark['iterations_per_round'] ||
        $current['repetitions'] !== $benchmark['repetitions']) {
        throw new RuntimeException('Benchmark result files use different run settings.');
    }
}

/** @return array<string, mixed> */
function readBenchmark(string $path): array
{
    if (!is_file($path)) {
        throw new RuntimeException('Benchmark result file not found: ' . $path);
    }

    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Unable to read benchmark result file: ' . $path);
    }

    $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid benchmark result file: ' . $path);
    }

    return $decoded;
}
