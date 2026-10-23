<?php
namespace Hidehalo\Nanoid;

/** @internal */
final class CustomAlphabetGenerator
{
    const POOL_MAX = 32768;

    /** @var string */
    private $alphabet;

    /** @var int */
    private $alphabetLength;

    /** @var int */
    private $defaultSize;

    /** @var \Closure */
    private $getRandom;

    /** @var string */
    private $pool = '';

    /** @var int */
    private $poolOffset = 0;

    /** @var int */
    private $poolNext = 0;

    /** @var int */
    private $mask;

    /** @var bool */
    private $powerOfTwoAlphabet;

    public function __construct(string $alphabet, int $defaultSize, callable $getRandom)
    {
        $this->alphabet = $alphabet;
        $this->alphabetLength = strlen($alphabet);
        $this->defaultSize = $defaultSize;
        $this->getRandom = \Closure::fromCallable($getRandom);

        $this->mask = 1;
        while ($this->mask < $this->alphabetLength - 1) {
            $this->mask = ($this->mask << 1) | 1;
        }
        $this->powerOfTwoAlphabet = $this->mask === $this->alphabetLength - 1;
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

        if ($this->poolOffset + $size > strlen($this->pool)) {
            $target = max($this->poolNext, $size);
            $this->poolNext = $target >= intdiv(self::POOL_MAX, 16)
                ? self::POOL_MAX
                : $target * 16;
            $this->pool = $this->fillPool($target);
            $this->poolOffset = 0;
        }

        $offset = $this->poolOffset;
        $this->poolOffset += $size;

        return substr($this->pool, $offset, $size);
    }

    private function fillPool(int $target): string
    {
        $pool = str_repeat("\0", $target);
        $accepted = 0;

        if ($this->powerOfTwoAlphabet) {
            $bytes = $this->readRandom($target);
            for ($index = 0; $index < $target; $index++) {
                $pool[$accepted++] = $this->alphabet[ord($bytes[$index]) & $this->mask];
            }

            return $pool;
        }

        $step = (int) ceil((1.6 * ($this->mask + 1) * $target) / $this->alphabetLength);
        while ($accepted < $target) {
            $bytes = $this->readRandom($step);
            for ($index = 0; $index < $step && $accepted < $target; $index++) {
                $symbolIndex = ord($bytes[$index]) & $this->mask;
                if ($symbolIndex < $this->alphabetLength) {
                    $pool[$accepted++] = $this->alphabet[$symbolIndex];
                }
            }
        }

        return $pool;
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
