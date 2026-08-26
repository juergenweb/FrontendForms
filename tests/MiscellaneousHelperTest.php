<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\MiscellaneousHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MiscellaneousHelper.
 *
 * This class has no functionality of its own beyond its constructor - the
 * "assertAtLeastTwoFields" behaviour it's used for lives on BaseHelper and
 * is already covered by BaseHelperTest.
 */
final class MiscellaneousHelperTest extends TestCase
{
    /**
     * 1) The class can be instantiated without error.
     */
    public function testCanBeInstantiated(): void
    {
        $helper = new MiscellaneousHelper();

        $this->assertInstanceOf(MiscellaneousHelper::class, $helper);
    }
}
