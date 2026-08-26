<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputWrapper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputWrapper.
 */
final class InputWrapperTest extends TestCase
{
    /**
     * 1) The element's tag is inherited from Wrapper ("div").
     */
    public function testConstructorSetsDivTag(): void
    {
        $wrapper = new InputWrapper();

        $this->assertSame('div', $wrapper->getTag());
    }

    /**
     * 2) The fixed "inputwrap" marker class is always present, regardless
     * of the live framework config (unlike the framework-derived class,
     * this one is hardcoded and safe to assert on exactly).
     */
    public function testConstructorAlwaysIncludesInputwrapClass(): void
    {
        $wrapper = new InputWrapper();

        $this->assertContains('inputwrap', $wrapper->getAttribute('class'));
    }

    /**
     * 3) A framework-derived CSS class is applied in addition to the fixed
     * "inputwrap" class - checked for presence only, since the exact class
     * name depends on the live "input_wrapperClass" framework config
     * (same reasoning as FieldWrapperTest/AlertTest).
     */
    public function testConstructorAddsAdditionalFrameworkClass(): void
    {
        $wrapper = new InputWrapper();

        $classes = $wrapper->getAttribute('class');

        $this->assertGreaterThanOrEqual(2, count($classes));
    }
}
