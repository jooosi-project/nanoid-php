<?php
namespace Hidehalo\Nanoid\Test;

use PHPUnit\Framework\TestCase;
use function Hidehalo\Nanoid\random;

/** Adapted from the random cases in Nano ID's test/index.test.js. */
class RandomTest extends TestCase
{
    public function testRandomReturnsTheRequestedNumberOfBytes()
    {
        $this->assertSame('', random(0));
        $this->assertSame(10, strlen(random(10)));
    }

    public function testRandomBytesAreUnsignedEightBitValues()
    {
        $bytes = random(1000);
        $this->assertSame(1000, strlen($bytes));
        $values = unpack('C*', $bytes);

        $this->assertNotFalse($values);
        $this->assertCount(1000, $values);
        foreach ($values as $value) {
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(255, $value);
        }
    }

    public function testRandomRejectsNegativeSize()
    {
        $this->expectException(\RangeException::class);
        random(-1);
    }
}
