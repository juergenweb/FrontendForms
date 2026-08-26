<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FieldWrapper;
use PHPUnit\Framework\TestCase;

/**
 * A thin subclass exposing the protected getErrorClass()/getSuccessClass()
 * via public wrappers, since PHPUnit test classes aren't subclasses of
 * FieldWrapper.
 */
final class ExposedFieldWrapper extends FieldWrapper
{
    public function exposeGetErrorClass(): string
    {
        return $this->getErrorClass();
    }

    public function exposeGetSuccessClass(): string
    {
        return $this->getSuccessClass();
    }
}

/**
 * Unit tests for FieldWrapper.
 *
 * The exact CSS class names depend on the live "field_wrapperClass"/
 * "field_wrapper_errorClass"/"field_wrapper_successClass" framework
 * config, confirmed to be defined across all bundled framework JSON files
 * (uikit3, bulma1, none, pico2, bootstrap5) - so these tests check for
 * non-empty, distinct values rather than hardcoding a specific framework's
 * class names (same reasoning as AlertTest).
 */
final class FieldWrapperTest extends TestCase
{
    /**
     * 1) The element's tag is inherited from Wrapper ("div").
     */
    public function testConstructorSetsDivTag(): void
    {
        $wrapper = new FieldWrapper();

        $this->assertSame('div', $wrapper->getTag());
    }

    /**
     * 2) A non-empty CSS class is applied on construction.
     */
    public function testConstructorSetsNonEmptyCssClass(): void
    {
        $wrapper = new FieldWrapper();

        $this->assertNotEmpty($wrapper->getAttribute('class'));
    }

    /**
     * 3) getErrorClass() returns a non-empty CSS class string.
     */
    public function testGetErrorClassReturnsNonEmptyString(): void
    {
        $wrapper = new ExposedFieldWrapper();

        $this->assertNotSame('', $wrapper->exposeGetErrorClass());
    }

    /**
     * 4) getSuccessClass() returns a non-empty CSS class string.
     */
    public function testGetSuccessClassReturnsNonEmptyString(): void
    {
        $wrapper = new ExposedFieldWrapper();

        $this->assertNotSame('', $wrapper->exposeGetSuccessClass());
    }

    /**
     * 5) The error and success classes are distinct from each other.
     */
    public function testErrorAndSuccessClassAreDistinct(): void
    {
        $wrapper = new ExposedFieldWrapper();

        $this->assertNotSame($wrapper->exposeGetErrorClass(), $wrapper->exposeGetSuccessClass());
    }
}
