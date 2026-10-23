<?php
namespace Hidehalo\Nanoid;

/** URL-friendly alphabet exported as urlAlphabet by Nano ID 6.0.2. */
const urlAlphabet = '57X6y3wQZNzYjJB2L_10pxbackgroundhimselfUTF-8DEMOGRAPHICSVqvt4KW9';
const URL_ALPHABET = urlAlphabet;

/**
 * Generate cryptographically strong random bytes.
 *
 * The returned PHP string is binary data and may contain null bytes.
 */
function random(int $bytes): string
{
    if ($bytes < 0) {
        throw new \RangeException('Wrong ID size.');
    }
    if ($bytes === 0) {
        return '';
    }

    return \random_bytes($bytes);
}

/**
 * Create an ID generator using a custom alphabet and random byte callback.
 *
 * The callback receives a byte count and must return that many raw bytes in
 * a binary PHP string.
 *
 * @return callable(int|null): string
 */
function customRandom(string $alphabet, int $defaultSize, callable $getRandom): \Closure
{
    $generator = new CustomRandomGenerator($alphabet, $defaultSize, $getRandom);

    return static function (?int $size = null) use ($generator, $defaultSize): string {
        if (func_num_args() === 0) {
            $size = $defaultSize;
        }

        return $generator($size ?? 0);
    };
}

/**
 * Create a secure ID generator for an alphabet.
 *
 * ASCII alphabets use the pooled fast path. Other valid UTF-8 alphabets use
 * rejection sampling through the generic custom-random path.
 *
 * @return callable(int|null): string
 */
function customAlphabet(string $alphabet, int $defaultSize = 21): \Closure
{
    $symbols = Alphabet::symbols($alphabet);
    $getRandom = static function (int $bytes): string {
        return random($bytes);
    };

    foreach ($symbols as $symbol) {
        if (strlen($symbol) !== 1) {
            return customRandom($alphabet, $defaultSize, $getRandom);
        }
    }

    $generator = new CustomAlphabetGenerator($alphabet, $defaultSize, $getRandom);

    return static function (?int $size = null) use ($generator, $defaultSize): string {
        if (func_num_args() === 0) {
            $size = $defaultSize;
        }

        return $generator($size ?? 0);
    };
}

/** Generate a secure URL-friendly ID. */
function nanoid(?int $size = 21): string
{
    static $generator;
    $generator ??= customAlphabet(URL_ALPHABET);

    return $generator($size ?? 0);
}
