<?php
namespace Hidehalo\Nanoid\Test;

use PHPUnit\Framework\TestCase;
use function Hidehalo\Nanoid\customRandom;

/** Adapted from the customRandom cases in Nano ID's test/index.test.js. */
class CustomRandomTest extends TestCase
{
    public function testRejectionSamplingMatchesUpstreamDeterministicVectors()
    {
        $sequence = [2, 255, 3, 7, 7, 7, 7, 7, 0, 1];
        $getRandom = $this->byteSource($sequence);

        $four = customRandom('abcde', 4, $getRandom);
        $eighteen = customRandom('abcde', 18, $getRandom);

        $this->assertSame('cccc', $four());
        $this->assertSame('acccccdcbacccccdcb', $eighteen());
    }

    public function testPowerOfTwoAlphabetUsesMaskSampling()
    {
        $generate = customRandom('abcd', 4, $this->byteSource([2, 255, 3, 7]));

        $this->assertSame('dddc', $generate());
        $this->assertSame('', $generate(0));
    }

    public function testZeroSizeAndZeroDefaultDoNotRequestRandomBytes()
    {
        $calls = 0;
        $generate = customRandom('abc', 0, static function ($size) use (&$calls) {
            $calls++;
            return str_repeat("\x01", $size);
        });

        $this->assertSame('', $generate());
        $this->assertSame(0, $calls);
        $this->assertSame('bbbbbbbb', $generate(8));
        $this->assertGreaterThan(0, $calls);
    }

    public function testCallbackMustReturnTheRequestedNumberOfBytes()
    {
        $generate = customRandom('abcd', 1, static function () {
            return '';
        });

        $this->expectException(\UnexpectedValueException::class);
        $generate();
    }

    public function testEmptyAlphabetIsRejected()
    {
        $this->expectException(\InvalidArgumentException::class);
        customRandom('', 0, static function () {
            return '';
        });
    }

    private function byteSource(array $sequence)
    {
        return static function ($size) use ($sequence) {
            $bytes = '';
            $sequenceLength = count($sequence);
            for ($index = 0; $index < $size; $index++) {
                $bytes .= chr($sequence[$index % $sequenceLength]);
            }

            return $bytes;
        };
    }
}
