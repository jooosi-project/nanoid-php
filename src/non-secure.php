<?php
namespace Hidehalo\Nanoid\NonSecure;

use Hidehalo\Nanoid\Alphabet;
use const Hidehalo\Nanoid\URL_ALPHABET;

/**
 * Create a non-secure ID generator using mt_rand().
 *
 * @return \Closure(int|null=): string
 */
function customAlphabet(string $alphabet, int $defaultSize = 21): \Closure
{
    $symbols = Alphabet::symbols($alphabet, null);
    $alphabetLength = count($symbols);
    $singleByteAlphabet = true;

    foreach ($symbols as $symbol) {
        if (strlen($symbol) !== 1) {
            $singleByteAlphabet = false;
            break;
        }
    }

    if ($singleByteAlphabet) {
        return static function (?int $size = null) use ($alphabet, $alphabetLength, $defaultSize): string {
            if (func_num_args() === 0) {
                $size = $defaultSize;
            }
            $size = $size ?? 0;
            $id = '';
            for ($index = 0; $index < $size; $index++) {
                $random = mt_rand() / (mt_getrandmax() + 1);
                $symbolIndex = (int) ($random * $alphabetLength);
                $id .= $alphabet[$symbolIndex];
            }

            return $id;
        };
    }

    return static function (?int $size = null) use ($symbols, $alphabetLength, $defaultSize): string {
        if (func_num_args() === 0) {
            $size = $defaultSize;
        }
        $size = $size ?? 0;
        $id = '';
        for ($index = 0; $index < $size; $index++) {
            $random = mt_rand() / (mt_getrandmax() + 1);
            $symbolIndex = (int) ($random * $alphabetLength);
            $id .= $symbols[$symbolIndex];
        }

        return $id;
    };
}

/** Generate a non-secure URL-friendly ID. */
function nanoid(?int $size = 21): string
{
    static $generator;
    $generator ??= customAlphabet(URL_ALPHABET);

    return $generator($size ?? 0);
}
