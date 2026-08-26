<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\BaseHelper;
use FrontendForms\Form;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Minimal concrete subclass, since BaseHelper is abstract.
 */
final class ConcreteBaseHelper extends BaseHelper
{
    public function exposeGetForm(): Form
    {
        return $this->getForm();
    }
}

/**
 * Unit tests for BaseHelper.
 *
 * All expected outputs for the numeric/filter_var-based methods were
 * confirmed by running the exact same checks standalone before writing
 * the assertions.
 */
final class BaseHelperTest extends TestCase
{
    // --- setForm() / getForm() ---

    /**
     * 1) REGRESSION TEST for the fixed safety check: calling getForm()
     * before setForm() throws a clear InvalidArgumentException instead of
     * an uninitialized-property fatal error.
     */
    public function testGetFormThrowsWhenCalledBeforeSetForm(): void
    {
        $helper = new ConcreteBaseHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->exposeGetForm();
    }

    /**
     * 2) After setForm(), getForm() returns the same instance.
     */
    public function testGetFormReturnsAssignedForm(): void
    {
        $helper = new ConcreteBaseHelper();
        $form = new Form('myform');

        $helper->setForm($form);

        $this->assertSame($form, $helper->exposeGetForm());
    }

    // --- normalizeScalar() ---

    /**
     * 3) A scalar value is trimmed.
     */
    public function testNormalizeScalarTrimsWhitespace(): void
    {
        $this->assertSame('test', BaseHelper::normalizeScalar(' test '));
    }

    /**
     * 4) An empty string (after trimming) normalizes to null.
     */
    public function testNormalizeScalarEmptyStringReturnsNull(): void
    {
        $this->assertNull(BaseHelper::normalizeScalar(''));
        $this->assertNull(BaseHelper::normalizeScalar('   '));
    }

    /**
     * 5) A non-scalar value (array) returns null.
     */
    public function testNormalizeScalarRejectsNonScalar(): void
    {
        $this->assertNull(BaseHelper::normalizeScalar([]));
        $this->assertNull(BaseHelper::normalizeScalar(['a']));
    }

    /**
     * 6) An integer is cast and returned as a string.
     */
    public function testNormalizeScalarAcceptsInt(): void
    {
        $this->assertSame('5', BaseHelper::normalizeScalar(5));
    }

    // --- allValuesContainLetters() ---

    /**
     * 7) A single string containing at least one letter passes.
     */
    public function testAllValuesContainLettersWithValidString(): void
    {
        $this->assertTrue(BaseHelper::allValuesContainLetters('abc123'));
    }

    /**
     * 8) A single string with no letters at all fails.
     */
    public function testAllValuesContainLettersWithNoLetters(): void
    {
        $this->assertFalse(BaseHelper::allValuesContainLetters('12345'));
    }

    /**
     * 9) An array where every string contains a letter passes.
     */
    public function testAllValuesContainLettersWithValidArray(): void
    {
        $this->assertTrue(BaseHelper::allValuesContainLetters(['abc', 'd1e']));
    }

    /**
     * 10) An array where at least one entry has no letters fails.
     */
    public function testAllValuesContainLettersWithOneInvalidEntry(): void
    {
        $this->assertFalse(BaseHelper::allValuesContainLetters(['abc', '123']));
    }

    /**
     * 11) An array containing a non-string entry fails.
     */
    public function testAllValuesContainLettersWithNonStringEntry(): void
    {
        $this->assertFalse(BaseHelper::allValuesContainLetters(['abc', 123]));
    }

    // --- normalizeInt() ---

    /**
     * 12) A genuine integer is returned unchanged.
     */
    public function testNormalizeIntWithInteger(): void
    {
        $this->assertSame(5, BaseHelper::normalizeInt(5));
        $this->assertSame(-3, BaseHelper::normalizeInt(-3));
    }

    /**
     * 13) A numeric string is converted to an integer.
     */
    public function testNormalizeIntWithNumericString(): void
    {
        $this->assertSame(5, BaseHelper::normalizeInt('5'));
        $this->assertSame(-3, BaseHelper::normalizeInt('-3'));
    }

    /**
     * 14) A non-numeric or empty string returns null.
     */
    public function testNormalizeIntWithInvalidString(): void
    {
        $this->assertNull(BaseHelper::normalizeInt('abc'));
        $this->assertNull(BaseHelper::normalizeInt(''));
        $this->assertNull(BaseHelper::normalizeInt('5.5'));
    }

    /**
     * 15) Any other type (array) returns null.
     */
    public function testNormalizeIntWithOtherTypeReturnsNull(): void
    {
        $this->assertNull(BaseHelper::normalizeInt([]));
    }

    // --- normalizeNonNegativeInt() ---

    /**
     * 16) A positive numeric value normalizes correctly.
     */
    public function testNormalizeNonNegativeIntWithPositiveValue(): void
    {
        $this->assertSame(5, BaseHelper::normalizeNonNegativeInt('5'));
        $this->assertSame(5, BaseHelper::normalizeNonNegativeInt(5));
    }

    /**
     * 17) Zero is accepted (non-negative includes zero).
     */
    public function testNormalizeNonNegativeIntWithZero(): void
    {
        $this->assertSame(0, BaseHelper::normalizeNonNegativeInt('0'));
    }

    /**
     * 18) A negative value returns null.
     */
    public function testNormalizeNonNegativeIntWithNegativeValueReturnsNull(): void
    {
        $this->assertNull(BaseHelper::normalizeNonNegativeInt('-3'));
    }

    /**
     * 19) A non-numeric or decimal value returns null.
     */
    public function testNormalizeNonNegativeIntWithInvalidValueReturnsNull(): void
    {
        $this->assertNull(BaseHelper::normalizeNonNegativeInt('abc'));
        $this->assertNull(BaseHelper::normalizeNonNegativeInt('5.5'));
    }

    // --- isPositiveInt() ---

    /**
     * 20) A positive integer (or numeric string) is recognized.
     */
    public function testIsPositiveIntWithPositiveValue(): void
    {
        $this->assertTrue(BaseHelper::isPositiveInt(5));
        $this->assertTrue(BaseHelper::isPositiveInt('5'));
        $this->assertTrue(BaseHelper::isPositiveInt('  5  '));
    }

    /**
     * 21) Zero is not a positive integer.
     */
    public function testIsPositiveIntWithZeroReturnsFalse(): void
    {
        $this->assertFalse(BaseHelper::isPositiveInt(0));
        $this->assertFalse(BaseHelper::isPositiveInt('0'));
    }

    /**
     * 22) A negative value is not positive.
     */
    public function testIsPositiveIntWithNegativeValueReturnsFalse(): void
    {
        $this->assertFalse(BaseHelper::isPositiveInt(-5));
    }

    /**
     * 23) A decimal or non-numeric value is not a positive int.
     */
    public function testIsPositiveIntWithInvalidValueReturnsFalse(): void
    {
        $this->assertFalse(BaseHelper::isPositiveInt('5.5'));
        $this->assertFalse(BaseHelper::isPositiveInt('abc'));
    }

    // --- getPositiveInt() ---

    /**
     * 24) A valid positive integer as the first array element is returned.
     */
    public function testGetPositiveIntReturnsFirstElement(): void
    {
        $this->assertSame(5, BaseHelper::getPositiveInt(['5', 'ignored'], 'myRule'));
    }

    /**
     * 25) An invalid (zero, negative, or missing) first element throws an
     * exception mentioning the given validator name.
     */
    public function testGetPositiveIntThrowsForInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('myRule');

        BaseHelper::getPositiveInt(['0'], 'myRule');
    }

    /**
     * 26) A completely missing array element (index 0 not set) also
     * throws, rather than triggering a warning.
     */
    public function testGetPositiveIntThrowsForMissingElement(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BaseHelper::getPositiveInt([], 'myRule');
    }

    // --- isUploadField() ---

    /**
     * 27) A value with the structural signature of a real upload field
     * (array with tmp_name/error keys at index 0) is recognized.
     */
    public function testIsUploadFieldWithValidStructure(): void
    {
        $value = [0 => ['tmp_name' => '/tmp/xyz', 'error' => 0]];

        $this->assertTrue(BaseHelper::isUploadField($value));
    }

    /**
     * 28) A plain string is not an upload field.
     */
    public function testIsUploadFieldWithStringReturnsFalse(): void
    {
        $this->assertFalse(BaseHelper::isUploadField('hello'));
    }

    /**
     * 29) An array missing the expected keys is not recognized as an
     * upload field.
     */
    public function testIsUploadFieldWithIncompleteStructureReturnsFalse(): void
    {
        $this->assertFalse(BaseHelper::isUploadField([0 => ['tmp_name' => '/tmp/xyz']]));
    }

    // --- assertAtLeastTwoFields() ---

    /**
     * 30) Two or more fields pass without throwing.
     */
    public function testAssertAtLeastTwoFieldsPassesWithTwoOrMore(): void
    {
        $helper = new ConcreteBaseHelper();

        $helper->assertAtLeastTwoFields(['a', 'b']);
        $helper->assertAtLeastTwoFields(['a', 'b', 'c']);

        $this->addToAssertionCount(2);
    }

    /**
     * 31) Exactly one field throws.
     */
    public function testAssertAtLeastTwoFieldsThrowsForOneField(): void
    {
        $helper = new ConcreteBaseHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->assertAtLeastTwoFields(['a']);
    }

    /**
     * 32) REGRESSION TEST for the fixed bug: zero fields must also throw
     * (before the fix, count($strings) === 1 let an empty array through
     * silently, even though zero fields is even further from "at least 2"
     * than one field is).
     */
    public function testAssertAtLeastTwoFieldsThrowsForZeroFields(): void
    {
        $helper = new ConcreteBaseHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->assertAtLeastTwoFields([]);
    }
}
