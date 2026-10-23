<?php
namespace Hidehalo\Nanoid;

/** @internal */
final class Alphabet
{
    /**
     * Split a UTF-8 alphabet into Unicode code points and enforce the secure
     * random-byte alphabet limit.
     *
     * @return string[]
     */
    public static function symbols(string $alphabet, ?int $maximumLength = 256): array
    {
        $symbols = preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY);

        if ($symbols === false) {
            throw new \InvalidArgumentException('Alphabet must be valid UTF-8.');
        }

        $length = count($symbols);
        if ($length === 0 || ($maximumLength !== null && $length > $maximumLength)) {
            $description = $maximumLength === null
                ? 'Alphabet must contain at least one symbol.'
                : sprintf('Alphabet must contain between 1 and %d symbols.', $maximumLength);
            throw new \InvalidArgumentException($description);
        }

        return $symbols;
    }
}
