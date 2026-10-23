<?php
namespace Hidehalo\Nanoid\Test;

use PHPUnit\Framework\TestCase;
use function Hidehalo\Nanoid\customAlphabet;
use function Hidehalo\Nanoid\nanoid;
use const Hidehalo\Nanoid\URL_ALPHABET;
use const Hidehalo\Nanoid\urlAlphabet;

/** Adapted from Nano ID's test/index.test.js and test/pool.test.js. */
class NanoidTest extends TestCase
{
    public function testUrlAlphabetHasSixtyFourUniqueSymbols()
    {
        $this->assertSame(urlAlphabet, URL_ALPHABET);
        $this->assertSame(64, strlen(URL_ALPHABET));
        $this->assertSame(64, count(array_unique(str_split(URL_ALPHABET))));
    }

    public function testNanoidReturnsUrlFriendlyIdsAtRequestedSizes()
    {
        $this->assertSame('', nanoid(0));
        $this->assertSame(21, strlen(nanoid()));
        $this->assertSame(10, strlen(nanoid(10)));

        for ($index = 0; $index < 100; $index++) {
            $this->assertUrlAlphabetOnly(nanoid());
        }
    }

    public function testNanoidSupportsLargeIdsAndPoolRefills()
    {
        $this->assertUrlAlphabetOnly(nanoid(1000));
        $this->assertUrlAlphabetOnly(nanoid(70000));
        $this->assertUrlAlphabetOnly(nanoid(100));
    }

    public function testPoolRemainsValidAfterAlternatingLargeRequests()
    {
        nanoid(1000);
        nanoid(500);
        $this->assertUrlAlphabetOnly(nanoid(100));
    }

    public function testNanoidHasNoCollisionsAcrossFiftyThousandCalls()
    {
        $seen = [];
        for ($index = 0; $index < 50000; $index++) {
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

    public function testNegativeSecureSizeThrowsRangeException()
    {
        $this->expectException(\RangeException::class);
        nanoid(-10);
    }

    public function testGeneratorRecoversAfterRejectedNegativeSize()
    {
        try {
            nanoid(-1);
            $this->fail('Expected a negative size to be rejected.');
        } catch (\RangeException $exception) {
            $this->assertSame(21, strlen(nanoid()));
        }
    }

    public function testCustomAlphabetSupportsDefaultRequestedAndZeroSizes()
    {
        $fixed = customAlphabet('a', 5);
        $this->assertSame('aaaaa', $fixed());
        $this->assertSame('aaaaaaaa', $fixed(8));
        $this->assertSame('', $fixed(0));

        $generate = customAlphabet('0123456789abcdef', 10);
        $this->assertSame(10, strlen($generate()));
        $this->assertSame(6, strlen($generate(6)));
        $this->assertSame('', $generate(0));
        $this->assertAlphabetOnly($generate(100), '0123456789abcdef');
    }

    public function testCustomAlphabetCharacterDistributionIsFlat()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $generate = customAlphabet($alphabet, 30);
        $counts = array_fill_keys(str_split($alphabet), 0);
        $iterations = 50000;

        for ($index = 0; $index < $iterations; $index++) {
            foreach (str_split($generate()) as $symbol) {
                $counts[$symbol]++;
            }
        }

        $this->assertFlatDistribution($counts, strlen($alphabet), $iterations * 30);
    }

    public function testCustomAlphabetSupportsUnicodeWithZeroDefaultSize()
    {
        $alphabet = 'абв';
        $generate = customAlphabet($alphabet, 0);

        $this->assertSame('', $generate());
        $this->assertUnicodeAlphabetOnly($generate(8), $alphabet, 8);
    }

    public function testCustomAlphabetCountsAstralSymbolsAsCodePoints()
    {
        $alphabet = '😀🐱🌍';
        $this->assertUnicodeAlphabetOnly(customAlphabet($alphabet, 10)(), $alphabet, 10);
    }

    public function testCustomAlphabetRejectsMoreThanTwoHundredFiftySixSymbols()
    {
        $this->expectException(\InvalidArgumentException::class);
        customAlphabet(str_repeat('a', 257));
    }

    private function assertUrlAlphabetOnly($id)
    {
        $this->assertSame(strlen($id), strspn($id, URL_ALPHABET));
    }

    private function assertAlphabetOnly($id, $alphabet)
    {
        $this->assertSame(strlen($id), strspn($id, $alphabet));
    }

    private function assertUnicodeAlphabetOnly($id, $alphabet, $expectedLength)
    {
        $symbols = preg_split('//u', $id, -1, PREG_SPLIT_NO_EMPTY);
        $allowed = preg_split('//u', $alphabet, -1, PREG_SPLIT_NO_EMPTY);

        $this->assertNotFalse($symbols);
        $this->assertNotFalse($allowed);
        $this->assertCount($expectedLength, $symbols);

        foreach ($symbols as $symbol) {
            $this->assertContains($symbol, $allowed);
        }
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
