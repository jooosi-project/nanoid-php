<?php
namespace Hidehalo\Nanoid;

/** @internal */
final class CustomRandomGenerator
{
    /** @var string[] */
    private $symbols;

    /** @var int */
    private $defaultSize;

    /** @var \Closure */
    private $getRandom;

    public function __construct(string $alphabet, int $defaultSize, callable $getRandom)
    {
        $this->symbols = Alphabet::symbols($alphabet);
        $this->defaultSize = $defaultSize;
        $this->getRandom = \Closure::fromCallable($getRandom);
    }

    public function __invoke(?int $size = null): string
    {
        $size = $size ?? $this->defaultSize;
        if ($size < 0) {
            throw new \RangeException('Wrong ID size.');
        }
        if ($size === 0) {
            return '';
        }

        $alphabetLength = count($this->symbols);
        $safeByteCutoff = 256 - (256 % $alphabetLength);
        $id = '';
        $accepted = 0;

        if ($safeByteCutoff === 256) {
            $mask = $alphabetLength - 1;

            while ($accepted < $size) {
                $bytes = $this->readRandom($size);
                for ($index = $size - 1; $index >= 0 && $accepted < $size; $index--) {
                    $id .= $this->symbols[ord($bytes[$index]) & $mask];
                    $accepted++;
                }
            }

            return $id;
        }

        $step = (int) ceil((1.6 * 256 * $size) / $safeByteCutoff);
        while ($accepted < $size) {
            $bytes = $this->readRandom($step);
            for ($index = $step - 1; $index >= 0 && $accepted < $size; $index--) {
                $byte = ord($bytes[$index]);
                if ($byte < $safeByteCutoff) {
                    $id .= $this->symbols[$byte % $alphabetLength];
                    $accepted++;
                }
            }
        }

        return $id;
    }

    private function readRandom(int $size): string
    {
        $bytes = ($this->getRandom)($size);
        if (!is_string($bytes) || strlen($bytes) < $size) {
            throw new \UnexpectedValueException('Random byte callback must return a binary string of the requested size.');
        }

        return $bytes;
    }
}
