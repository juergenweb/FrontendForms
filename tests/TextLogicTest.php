<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use FrontendForms\Form;
use FrontendForms\TextHelper;
use FrontendForms\TextLogic;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextLogic validation methods.
 *
 * Covers: exactValue, differentValue, checkHex, compareTexts,
 * cyrillicName, noLetters, noNumbers and uniqueStringValueOfPWField.
 */
final class TextLogicTest extends TestCase
{
    private TextLogic $logic;

    /**
     * Create a TextLogic instance shared across all tests.
     */
    protected function setUp(): void
    {
        $textHelper = new TextHelper();

        $this->logic = new TextLogic(
            $textHelper
        );

        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $this->logic->setForm($form);
        $textHelper->setForm($form);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: exactValue
    | Method: validateExactValue
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that validation succeeds when the value exactly matches the configured string.
     */
    public function testExactMatchingStringReturnsTrue(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'hello',
            ['hello'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that validation fails when the value differs from the configured string.
     */
    public function testDifferentStringReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'hello',
            ['world'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that comparison is case-sensitive.
     */
    public function testDifferentCaseReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'Hello',
            ['hello'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that leading whitespace causes validation to fail.
     */
    public function testLeadingWhitespaceReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            ' hello',
            ['hello'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that trailing whitespace causes validation to fail.
     */
    public function testTrailingWhitespaceReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'hello ',
            ['hello'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that a null value matches an empty configured value throws exception.
     */
    public function testNullMatchesEmptyStringThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateExactValue(
            'field',
            null,
            [''],
            []
        );
    }

    /**
     * Verifies that an empty string does not match a non-empty configured value.
     */
    public function testEmptyStringAgainstNonEmptyStringReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            '',
            ['hello'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that strict comparison rejects an integer when the configured value is a string.
     */
    public function testIntegerDoesNotMatchStringReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            123,
            ['123'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that strict comparison matches an integer when the configured value is the same integer.
     */
    public function testIntegerDoesMatchIntegerReturnsTrue(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            123,
            [123],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that strict comparison rejects an integer when the configured value is a different integer.
     */
    public function testIntegerDoesNotMatchIntegerReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            123,
            [456],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that strict comparison rejects a string when the configured value is an integer.
     */
    public function testStringDoesNotMatchIntegerReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            '123',
            [123],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that strict comparison throws exception if param is empty string
     */
    public function testStringThrowsExceptionOnEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateExactValue(
            'field',
            '',
            [''],
            []
        );
    }

    /**
     * Verifies that null does not match a configured string value.
     */
    public function testNullDoesNotMatchStringReturnsFalse(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            null,
            ['hello'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that special characters are compared exactly.
     */
    public function testSpecialCharactersMatchReturnsTrue(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'äöü!@#',
            ['äöü!@#'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that Unicode strings are compared exactly.
     */
    public function testUnicodeStringMatchReturnsTrue(): void
    {
        $result = $this->logic->validateExactValue(
            'field',
            'こんにちは',
            ['こんにちは'],
            []
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: differentValue
    | Method: validateDifferentValue
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true when string values are different.
     */
    public function testDifferentStringsReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'value1',
            ['value2'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when string values are exactly equal.
     */
    public function testEqualStringsReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'value',
            ['value'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when values differ only by case.
     */
    public function testCaseSensitiveDifferenceReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'Value',
            ['value'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when special character strings are equal.
     */
    public function testSpecialCharactersMatchReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'äöü!@#',
            ['äöü!@#'],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when special character strings differ.
     */
    public function testSpecialCharactersDifferReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'äöü!@#',
            ['äöü!@$'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when integer values are equal.
     */
    public function testEqualIntegersReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            123,
            [123],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when integer values are different.
     */
    public function testDifferentIntegersReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            123,
            [456],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when integer and string representations differ by type.
     */
    public function testIntegerAndStringWithSameValueReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            123,
            ['123'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when float values are equal.
     */
    public function testEqualFloatsReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            12.34,
            [12.34],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when float values are different.
     */
    public function testDifferentFloatsReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            12.34,
            [56.78],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when boolean true values are equal.
     */
    public function testEqualTrueBooleansReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            true,
            [true],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when boolean false values are equal.
     */
    public function testEqualFalseBooleansReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            false,
            [false],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when boolean values differ.
     */
    public function testDifferentBooleansReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            true,
            [false],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when boolean and integer representations differ by type.
     */
    public function testBooleanAndIntegerReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            true,
            [1],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that an exception is thrown when both values are null.
     */
    public function testBothNullThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateNoneExactValue(
            'field',
            null,
            [null],
            []
        );
    }

    /**
     * Verifies that the result is true when one value is null and the other is not.
     */
    public function testNullAndStringReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            null,
            ['value'],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when arrays are identical.
     */
    public function testIdenticalArraysReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            ['a', 'b'],
            [['a', 'b']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when arrays differ.
     */
    public function testDifferentArraysReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            ['a', 'b'],
            [['a', 'c']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that an exception is thrown when empty strings are equal.
     */
    public function testEmptyStringsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logic->validateNoneExactValue(
            'field',
            '',
            [''],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when empty string and null differ.
     */
    public function testEmptyStringAndNullThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateNoneExactValue(
            'field',
            '',
            [null],
            []
        );
    }

    /**
     * Verifies that the result is false when values contain whitespace and are identical.
     */
    public function testWhitespaceStringsMatchReturnsFalse(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            ' value ',
            [' value '],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when values differ by whitespace.
     */
    public function testWhitespaceDifferenceReturnsTrue(): void
    {
        $result = $this->logic->validateNoneExactValue(
            'field',
            'value',
            [' value '],
            []
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: compareTexts
    | Method: validateTextComparison
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true when the value exactly matches
     * one entry in the comparison array.
     */
    public function testTextComparisonExactMatchReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Hello',
            [['Hello', 'World']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when the value matches
     * case-insensitively.
     */
    public function testTextComparisonCaseInsensitiveMatchReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'hello',
            [['Hello', 'World']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when the matching value
     * exists later in the comparison array.
     */
    public function testTextComparisonMatchInArrayReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'orange',
            [['Apple', 'Banana', 'Orange', 'Pear']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when no comparison value matches.
     */
    public function testTextComparisonNoMatchReturnsFalse(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Orange',
            [['Apple', 'Banana']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Should throw an exception for an empty comparison array.
     */
    public function testTextComparisonEmptyArrayThrowException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->logic->validateTextComparison(
            'field',
            'Anything',
            [[]],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when both value and comparison
     * entry are empty strings.
     */
    public function testTextComparisonEmptyStringMatchReturnsTrue(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateTextComparison(
            'field',
            '',
            [['']],
            []
        );
    }

    /**
     * Verifies that the result is false when an empty string
     * is not contained in the comparison array.
     */
    public function testTextComparisonEmptyStringNoMatchReturnsFalse(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            '',
            [['test']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true for numeric values
     * when the string representation matches.
     */
    public function testTextComparisonNumericValueReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            123,
            [['123']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false for numeric values
     * when no matching string exists.
     */
    public function testTextComparisonNumericValueReturnsFalse(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            123,
            [['456']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when the first
     * comparison value matches.
     */
    public function testTextComparisonFirstEntryMatchReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Apple',
            [['Apple', 'Banana', 'Orange']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when the last
     * comparison value matches.
     */
    public function testTextComparisonLastEntryMatchReturnsTrue(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Orange',
            [['Apple', 'Banana', 'Orange']],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false for a value
     * containing additional whitespace.
     */
    public function testTextComparisonWhitespaceDifferenceReturnsFalse(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Hello ',
            [['Hello']],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a completely
     * different value.
     */
    public function testTextComparisonDifferentValueReturnsFalse(): void
    {
        $result = $this->logic->validateTextComparison(
            'field',
            'Foo',
            [['Bar']],
            []
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: cyrllicname
    | Method: validateCyrillicName
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true for a non-string value (array input).
     */
    public function testCyrillicNameNonStringReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            [],
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for an empty string.
     */
    public function testCyrillicNameEmptyStringReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a simple valid Cyrillic name.
     */
    public function testCyrillicNameSimpleValidNameReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a valid Cyrillic full name with space.
     */
    public function testCyrillicNameValidFullNameWithSpaceReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван петров',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a valid hyphenated Cyrillic name.
     */
    public function testCyrillicNameHyphenatedNameReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'анна-мария',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a complex valid Cyrillic full name.
     */
    public function testCyrillicNameComplexValidNameReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'анна мария-петрова',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false for a name containing Latin characters.
     */
    public function testCyrillicNameLatinCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'ivan',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a name containing numbers.
     */
    public function testCyrillicNameWithNumbersReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a name containing special characters.
     */
    public function testCyrillicNameWithSpecialCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван@петров',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for leading hyphen.
     */
    public function testCyrillicNameLeadingHyphenReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            '-иван',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for trailing hyphen.
     */
    public function testCyrillicNameTrailingHyphenReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван-',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for multiple consecutive hyphens.
     */
    public function testCyrillicNameDoubleHyphenReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'иван--петров',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true for whitespace-only string.
     */
    public function testCyrillicNameWhitespaceOnlyReturnsFalse(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            '   ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for name with leading/trailing spaces (trimmed internally).
     */
    public function testCyrillicNameWithWhitespaceAroundReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            '  иван петров  ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for name containing the Cyrillic character "ё".
     */
    public function testCyrillicNameWithYoCharacterReturnsTrue(): void
    {
        $result = $this->logic->validateCyrillicName(
            'field',
            'савёлов',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: noLetters
    | Method: validateNoLetters
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true when a string contains no letters.
     */
    public function testNoLettersReturnsTrueForNumbersOnly(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            '123456',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when a string contains only special characters.
     */
    public function testNoLettersReturnsTrueForSpecialCharacters(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            '!@#$%^&*()',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for an empty string.
     */
    public function testNoLettersReturnsTrueForEmptyString(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for whitespace-only string.
     */
    public function testNoLettersReturnsTrueForWhitespaceOnly(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            '   ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when string contains ASCII letters.
     */
    public function testNoLettersReturnsFalseForAsciiLetters(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'hello123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains uppercase ASCII letters.
     */
    public function testNoLettersReturnsFalseForUppercaseLetters(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'HELLO',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains German umlauts.
     */
    public function testNoLettersReturnsFalseForUmlauts(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'schön',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains mixed letters and numbers.
     */
    public function testNoLettersReturnsFalseForMixedContent(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'abc123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains Cyrillic letters.
     */
    public function testNoLettersReturnsFalseForCyrillicLetters(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'иван',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains accented letters.
     */
    public function testNoLettersReturnsFalseForAccentedLetters(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            'café',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when input is non-string (array).
     */
    public function testNoLettersReturnsTrueForArrayInput(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            [],
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when input is integer.
     */
    public function testNoLettersReturnsTrueForIntegerInput(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            12345,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when input is null.
     */
    public function testNoLettersReturnsTrueForNullInput(): void
    {
        $result = $this->logic->validateNoLetters(
            'field',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: noNumbers
    | Method: validateNoNumbers
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true when a string contains no numbers.
     */
    public function testNoNumbersReturnsTrueForAlphabeticString(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            'hello',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for an empty string.
     */
    public function testNoNumbersReturnsTrueForEmptyString(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for whitespace-only string.
     */
    public function testNoNumbersReturnsTrueForWhitespaceOnly(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            '   ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when string contains digits only.
     */
    public function testNoNumbersReturnsFalseForDigitsOnly(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            '123456',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains mixed letters and numbers.
     */
    public function testNoNumbersReturnsFalseForMixedContent(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            'abc123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string contains numbers in between text.
     */
    public function testNoNumbersReturnsFalseForNumbersInText(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            'test1test',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string starts with numbers.
     */
    public function testNoNumbersReturnsFalseForLeadingNumbers(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            '123abc',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when string ends with numbers.
     */
    public function testNoNumbersReturnsFalseForTrailingNumbers(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            'abc123',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true when input is null.
     */
    public function testNoNumbersReturnsTrueForNullInput(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when input is an array.
     */
    public function testNoNumbersReturnsTrueForArrayInput(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            [],
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true when input is an integer (non-string).
     */
    public function testNoNumbersReturnsTrueForIntegerInput(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            12345,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false for decimal numbers inside strings.
     */
    public function testNoNumbersReturnsFalseForDecimalNumbers(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            'price9.99',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for string containing Unicode digits.
     */
    public function testNoNumbersReturnsFalseForUnicodeDigits(): void
    {
        $result = $this->logic->validateNoNumbers(
            'field',
            '123４５６', // includes fullwidth digits
            [],
            []
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: uniqueStringValueOfPWField
    | Method: validateUniqueStringValueOfPWField
    | PW templates: (basic-page, home, sitemap, sitemap
    | Field title in basic-page is "About"
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that an exception is thrown when no field name parameter is provided.
     */
    public function testValidateUniqueStringValueOfPWFieldThrowsExceptionWhenParamIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateUniqueStringValueOfPWField(
            'title',
            'About',
            [],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when field name parameter is empty.
     */
    public function testValidateUniqueStringValueOfPWFieldThrowsExceptionWhenFieldNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateUniqueStringValueOfPWField(
            'title',
            'About',
            [''],
            []
        );
    }

    /**
     * Verifies that the result is false when title already exists.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsFalseWhenValueAlreadyExists(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when title does not exist.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsTrueWhenValueIsUnique(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'CompletelyUniqueTitle123',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when title exists in basic-page template.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsFalseWhenValueExistsInSpecifiedTemplate(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title', 'basic-page'],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when title does not exist in specified template.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsTrueWhenValueDoesNotExistInSpecifiedTemplate(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'Home',
                ['title', 'sitemap'],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when title exists in one of multiple templates.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsFalseWhenValueExistsInMultipleTemplateSelector(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title', ['basic-page', 'home']],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when title does not exist in any specified template.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsTrueWhenValueDoesNotExistInMultipleTemplateSelector(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'CompletelyUniqueTitle123',
                ['title', ['basic-page', 'home']],
                []
            )
        );
    }

    /**
     * Verifies that the result is true when value exists only in templates outside the selector.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsTrueWhenValueExistsOutsideTemplateSelector(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title', 'home'],
                []
            )
        );
    }

    /**
     * Verifies that it trims and sanitize value before lookup.
     */
    public function testValidateUniqueStringValueOfPWFieldFindsExistingValueAfterTrimming(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                '  About  ',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that the result is true for unique value containing whitespace.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsTrueForUniqueTrimmedValue(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                '  CompletelyUniqueTitle123  ',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when matching title is found including hidden pages.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsFalseForExistingHiddenPage(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that the result is false when matching title is found including unpublished pages.
     */
    public function testValidateUniqueStringValueOfPWFieldReturnsFalseForExistingUnpublishedPage(): void
    {
        $this->assertFalse(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About',
                ['title'],
                []
            )
        );
    }

    /**
     * Verifies that it treats selector special characters safely.
     */
    public function testValidateUniqueStringValueOfPWFieldSanitizesSelectorCharacters(): void
    {
        $this->assertTrue(
            $this->logic->validateUniqueStringValueOfPWField(
                'title',
                'About, template=home',
                ['title'],
                []
            )
        );
    }

}