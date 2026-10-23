<?php
namespace Hidehalo\Nanoid\Test;

use PHPUnit\Framework\TestCase;
use function Hidehalo\Nanoid\NonSecure\customAlphabet;
use function Hidehalo\Nanoid\NonSecure\nanoid;
use const Hidehalo\Nanoid\URL_ALPHABET;

/** Adapted from Nano ID's test/non-secure.test.js. */
class NonSecureTest extends TestCase
{
    public function testNanoidReturnsUrlFriendlyIdsAtRequestedSizes()
    {
        $this->assertSame('', nanoid(0));
        $this->assertSame(21, strlen(nanoid()));
        $this->assertSame(10, strlen(nanoid(10)));

        for ($index = 0; $index < 10; $index++) {
            $id = nanoid();
            $this->assertSame(strlen($id), strspn($id, URL_ALPHABET));
        }
    }

    public function testNanoidHasNoCollisionsAcrossOneHundredThousandCalls()
    {
        $seen = [];
        for ($index = 0; $index < 100000; $index++) {
            $id = nanoid();
            $this->assertArrayNotHasKey($id, $seen);
            $seen[$id] = true;
        }
    }

    public function testNanoidCharacterDistributionIsFlat()
    {
        $counts = array_fill_keys(str_split(URL_ALPHABET), 0);
        $iterations = 100000;

        for ($index = 0; $index < $iterations; $index++) {
            foreach (str_split(nanoid()) as $symbol) {
                $counts[$symbol]++;
            }
        }

        $this->assertFlatDistribution($counts, 64, $iterations * 21);
    }

    public function testCustomAlphabetSupportsRequestedSizesAndFlatDistribution()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $generate = customAlphabet($alphabet, 30);

        $this->assertSame('', $generate(0));
        $this->assertSame(30, strlen($generate()));
        $this->assertSame(10, strlen($generate(10)));

        $counts = array_fill_keys(str_split($alphabet), 0);
        $iterations = 100000;
        for ($index = 0; $index < $iterations; $index++) {
            foreach (str_split($generate()) as $symbol) {
                $counts[$symbol]++;
            }
        }

        $this->assertFlatDistribution($counts, strlen($alphabet), $iterations * 30);
    }

    public function testCustomAlphabetSupportsUnicodeCodePoints()
    {
        $alphabet = 'абв';
        $generate = customAlphabet($alphabet, 0);
        $this->assertSame('', $generate());

        $symbols = preg_split('//u', $generate(8), -1, PREG_SPLIT_NO_EMPTY);
        $allowed = preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY);
        $this->assertNotFalse($symbols);
        $this->assertNotFalse($allowed);
        $this->assertCount(8, $symbols);
        foreach ($symbols as $symbol) {
            $this->assertContains($symbol, $allowed);
        }
    }

    public function testNegativeSizesReturnEmptyStrings()
    {
        $this->assertSame('', nanoid(-1));
        $this->assertSame('', nanoid(-100));
        $this->assertSame('', customAlphabet('abcdef')(-1));
        $this->assertSame('', customAlphabet('abcdef', -5)());
    }

    private function assertFlatDistribution($counts, $alphabetLength, $totalCharacters)
    {
        $this->assertSame($alphabetLength, count($counts));
        $distribution = [];
        foreach ($counts as $count) {
            $distribution[] = ($count * $alphabetLength) / $totalCharacters;
        }

        $this->assertLessThanOrEqual(0.05, max($distribution) - min($distribution));
    }
}
