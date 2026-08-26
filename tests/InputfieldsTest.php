<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Inputfields;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Inputfields::convertToBytes().
 *
 * Pure static calculation method, no ProcessWire dependency - called
 * directly as Inputfields::convertToBytes(...), no instance needed.
 *
 * All expected outputs were confirmed by running the actual algorithm
 * standalone with the exact same inputs before writing the assertions.
 */
final class InputfieldsTest extends TestCase
{
    /**
     * 1) Standard byte-unit suffixes ("KB", "MB", "GB", ...) are converted
     * using 1024 as the base, matching binary (not decimal) size units.
     */
    public function testConvertsStandardByteUnits(): void
    {
        $this->assertSame(8 * 1024 * 1024, Inputfields::convertToBytes('8MB'));
        $this->assertSame(2 * 1024 * 1024 * 1024, Inputfields::convertToBytes('2GB'));
        $this->assertSame(100, Inputfields::convertToBytes('100B'));
    }

    /**
     * 2) With $ini = true, single-letter PHP-ini-style suffixes ("K", "M",
     * "G", ...) as returned by ini_get('upload_max_filesize') etc. are
     * converted the same way.
     */
    public function testConvertsIniStyleUnitsWhenIniFlagIsSet(): void
    {
        $this->assertSame(8 * 1024 * 1024, Inputfields::convertToBytes('8M', true));
        $this->assertSame(2 * 1024 * 1024 * 1024, Inputfields::convertToBytes('2G', true));
        $this->assertSame(100, Inputfields::convertToBytes('100', true));
    }

    /**
     * 3) An integer input (already a byte count) is returned as-is.
     */
    public function testIntegerInputIsReturnedAsIs(): void
    {
        $this->assertSame(1024, Inputfields::convertToBytes(1024));
        $this->assertSame(100, Inputfields::convertToBytes(100));
    }

    /**
     * 4) A value with an unrecognized unit suffix returns null rather than
     * throwing or silently returning a wrong number.
     */
    public function testUnrecognizedUnitReturnsNull(): void
    {
        $this->assertNull(Inputfields::convertToBytes('8XY'));
    }

    /**
     * 5) A completely non-numeric, non-unit string returns null.
     */
    public function testGarbageInputReturnsNull(): void
    {
        $this->assertNull(Inputfields::convertToBytes('abc'));
    }
}
