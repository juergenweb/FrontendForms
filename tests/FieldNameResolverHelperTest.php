<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\FieldNameResolverHelper;
use FrontendForms\Form;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FieldNameResolverHelper.
 */
final class FieldNameResolverHelperTest extends TestCase
{
    private function makeHelper(): FieldNameResolverHelper
    {
        $helper = new FieldNameResolverHelper();
        $helper->setForm(new Form('myform'));

        return $helper;
    }

    // --- resolve() ---

    /**
     * 1) An unprefixed field name is correctly resolved to its prefixed
     * form, when that prefixed key exists in the field set.
     */
    public function testResolveAddsPrefixToUnprefixedName(): void
    {
        $helper = $this->makeHelper();

        $result = $helper->resolve(['myform-email' => 'test@example.com'], 'email');

        $this->assertSame('myform-email', $result);
    }

    /**
     * 2) An already-prefixed field name is accepted as-is (not
     * double-prefixed).
     */
    public function testResolveAcceptsAlreadyPrefixedName(): void
    {
        $helper = $this->makeHelper();

        $result = $helper->resolve(['myform-email' => 'test@example.com'], 'myform-email');

        $this->assertSame('myform-email', $result);
    }

    /**
     * 3) Surrounding whitespace in the input field name is trimmed before
     * resolution.
     */
    public function testResolveTrimsWhitespace(): void
    {
        $helper = $this->makeHelper();

        $result = $helper->resolve(['myform-email' => 'test@example.com'], '  email  ');

        $this->assertSame('myform-email', $result);
    }

    /**
     * 4) An empty field name throws.
     */
    public function testResolveThrowsForEmptyFieldName(): void
    {
        $helper = $this->makeHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->resolve(['myform-email' => 'test@example.com'], '   ');
    }

    /**
     * 5) A field name that doesn't exist in the field set (even after
     * prefixing) throws, naming the resolved field in the message.
     */
    public function testResolveThrowsForNonExistentField(): void
    {
        $helper = $this->makeHelper();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/myform-nonexistent/');

        $helper->resolve(['myform-email' => 'test@example.com'], 'nonexistent');
    }

    // --- getFieldValue() ---

    /**
     * 6) An existing field's value is returned.
     */
    public function testGetFieldValueReturnsValue(): void
    {
        $helper = $this->makeHelper();

        $value = $helper->getFieldValue(['myform-email' => 'test@example.com'], 'myform-email');

        $this->assertSame('test@example.com', $value);
    }

    /**
     * 7) A non-existent field throws.
     */
    public function testGetFieldValueThrowsForNonExistentField(): void
    {
        $helper = $this->makeHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->getFieldValue(['myform-email' => 'test@example.com'], 'myform-missing');
    }
}
