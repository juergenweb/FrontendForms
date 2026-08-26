<?php

declare(strict_types=1);

namespace Tests;

use InvalidArgumentException;
use FrontendForms\Form;
use FrontendForms\MiscellaneousHelper;
use FrontendForms\MiscellaneousLogic;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for MiscellaneousLogic validation methods.
 *
 * Covers: exactValue, differentValue, checkHex, compareTexts,
 * cyrillicName, noLetters, noNumbers, requiredIfEqual,
 * requiredIfEmpty, requiredIfNotEmpty, and uniqueStringValueOfPWField.
 */
final class MiscellaneousLogicTest extends TestCase
{
    private MiscellaneousLogic $logic;

    /**
     * Create a MiscellaneousLogic instance shared across all tests.
     */
    protected function setUp(): void
    {
        $miscellaneousHelper = new MiscellaneousHelper();

        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('form');

        $this->logic = new MiscellaneousLogic(
            $miscellaneousHelper
        );

        $this->logic->setForm($form);
        $miscellaneousHelper->setForm($form);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: checkHex
    | Method: validateHexValue
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that the result is true for a valid 3 digit hex code.
     */
    public function testHexValidThreeDigitHexReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFF',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a valid lowercase 3 digit hex code.
     */
    public function testHexValidLowercaseThreeDigitHexReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#abc',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a valid 6 digit hex code.
     */
    public function testHexValidSixDigitHexReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#A1B2C3',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for a valid lowercase 6 digit hex code.
     */
    public function testHexValidLowercaseSixDigitHexReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#abcdef',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for mixed case hex characters.
     */
    public function testHexMixedCaseHexReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#AbCdEf',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false when hash sign is missing.
     */
    public function testHexMissingHashReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            'FFFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a 1 digit hex code.
     */
    public function testHexOneDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#F',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a 2 digit hex code.
     */
    public function testHexTwoDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a 4 digit hex code.
     */
    public function testHexFourDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a 5 digit hex code.
     */
    public function testHexFiveDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for a 7 digit hex code.
     */
    public function testHexSevenDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFFFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for an 8 digit hex code.
     */
    public function testHexEightDigitHexReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFFFFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for invalid uppercase hex characters.
     */
    public function testHexInvalidUppercaseHexCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#GGGGGG',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for invalid lowercase hex characters.
     */
    public function testHexInvalidLowercaseHexCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#gggggg',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains non-hexadecimal character.
     */
    public function testHexWithInvalidCharacterReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#12G456',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains special characters.
     */
    public function testHexWithSpecialCharactersReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#12@456',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains leading whitespace.
     */
    public function testHexLeadingWhitespaceReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            ' #FFFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains trailing whitespace.
     */
    public function testHexTrailingWhitespaceReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '#FFFFFF ',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains newline.
     */
    public function testHexWithNewlineReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            "#FFFFFF\n",
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false when value contains two hash signs.
     */
    public function testHexDoubleHashReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '##FFFFFF',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is true for an empty string.
     */
    public function testHexEmptyStringReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is true for null.
     */
    public function testHexNullReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * Verifies that the result is false for string zero.
     */
    public function testHexStringZeroReturnsTrue(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            '0',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for integer zero.
     */
    public function testHexIntegerZeroReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            0,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Should return false.
     */
    public function testHexFalseReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            false,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * Verifies that the result is false for an empty array.
     */
    public function testHexEmptyArrayReturnsFalse(): void
    {
        $result = $this->logic->validateHexValue(
            'field',
            [],
            [],
            []
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: requiredIfEqual
    | Method: validateRequiredIfEqual
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that an exception is thrown when comparison field parameter is missing.
     */
    public function testValidateRequiredIfEqualThrowsExceptionWhenFieldParameterIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEqual(
            'field',
            '',
            [],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when comparison value parameter is missing.
     */
    public function testValidateRequiredIfEqualThrowsExceptionWhenValueParameterIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEqual(
            'field',
            '',
            ['status'],
            []
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field does not exist.
     */
    public function testValidateRequiredIfEqualThrowsExceptionWhenComparisonFieldDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-field1' => 'value',
            'form-gender' => 'Mister',
        ];

        $this->logic->validateRequiredIfEqual(
            'field',
            '',
            ['status', 'active'],
            $fields
        );
    }

    /**
     * Verifies that the result is true when condition is not met.
     */
    public function testValidateRequiredIfEqualReturnsTrueWhenConditionIsNotMet(): void
    {
        $fields = [
            'form-status' => 'inactive',
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                '',
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when condition is met and value is null.
     */
    public function testValidateRequiredIfEqualReturnsFalseWhenConditionMetAndValueIsNull(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-gender' => 'Mister',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEqual(
                'field',
                null,
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when condition is met and value is empty string.
     */
    public function testValidateRequiredIfEqualThrowsExceptionWhenOnlyOneFormfieldIsPresent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-status' => 'active',
        ];

        $this->logic->validateRequiredIfEqual(
            'field',
            '',
            ['status', 'active'],
            $fields
        );
    }

    /**
     * Verifies that an exception is thrown if there is only 1 form field present
     */
    public function testValidateRequiredIfEqualReturnsFalseWhenConditionMetAndValueIsEmptyString(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-gender' => 'Mister',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEqual(
                'field',
                '',
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when condition is met and value is filled.
     */
    public function testValidateRequiredIfEqualReturnsTrueWhenConditionMetAndValueIsFilled(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'hello',
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Should use strict comparison for scalar values.
     */
    public function testValidateRequiredIfEqualUsesStrictComparison(): void
    {
        $fields = [
            'form-status' => 1,
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                '',
                ['status', '1'],
                $fields
            )
        );
    }

    /**
     * Should match array values using OR operator.
     */
    public function testValidateRequiredIfEqualMatchesArrayUsingOrOperator(): void
    {
        $fields = [
            'form-tags' => ['php', 'mysql'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript', 'or'],
                $fields
            )
        );
    }

    /**
     * Should not match array values using OR operator.
     */
    public function testValidateRequiredIfEqualDoesNotMatchArrayUsingOrOperator(): void
    {
        $fields = [
            'form-tags' => ['mysql'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                '',
                ['tags', 'php|javascript', 'or'],
                $fields
            )
        );
    }

    /**
     * Should match array values using AND operator.
     */
    public function testValidateRequiredIfEqualMatchesArrayUsingAndOperator(): void
    {
        $fields = [
            'form-tags' => ['php', 'javascript', 'mysql'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript', 'and'],
                $fields
            )
        );
    }

    /**
     * Should not match array values using AND operator when one value is missing.
     */
    public function testValidateRequiredIfEqualDoesNotMatchArrayUsingAndOperator(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                '',
                ['tags', 'php|javascript', 'and'],
                $fields
            )
        );
    }

    /**
     * Should default to OR operator when omitted.
     */
    public function testValidateRequiredIfEqualDefaultsToOrOperatorWhenOmitted(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript'],
                $fields
            )
        );
    }

    /**
     * Should default to OR operator when invalid operator is provided.
     */
    public function testValidateRequiredIfEqualDefaultsToOrOperatorWhenOperatorIsInvalid(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript', 'invalid'],
                $fields
            )
        );
    }

    /**
     * Should support uppercase AND operator.
     */
    public function testValidateRequiredIfEqualSupportsUppercaseAndOperator(): void
    {
        $fields = [
            'form-tags' => ['php', 'javascript'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript', 'AND'],
                $fields
            )
        );
    }

    /**
     * Should support uppercase OR operator.
     */
    public function testValidateRequiredIfEqualSupportsUppercaseOrOperator(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                'filled',
                ['tags', 'php|javascript', 'OR'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when value is string zero.
     */
    public function testValidateRequiredIfEqualAcceptsStringZeroAsFilledValue(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                '0',
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when value is integer zero.
     */
    public function testValidateRequiredIfEqualAcceptsIntegerZeroAsFilledValue(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-gender' => 'Mister',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEqual(
                'field',
                0,
                ['status', 'active'],
                $fields
            )
        );
    }

    /**
     * Throw exception if comparison form field is a file upload field.
     */
    /**
     * Verifies that an exception is thrown when comparison field is a file upload field.
     */
    public function testValidateRequiredIfEqualThrowsExceptionWhenComparisonFieldIsUploadField(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Field "form-upload" cannot be used as comparison field because it is a file upload field.'
        );

        $fields = [
            'form-upload' => [
                [
                    'name' => '',
                    'full_path' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => 4,
                    'size' => 0,
                ],
            ],
            'form-name' => '',
        ];

        $this->logic->validateRequiredIfEqual(
            'name',
            '',
            ['upload', 'active'],
            $fields
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: requiredIfEmpty
    | Method: validateRequiredIfEmpty
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that an exception is thrown when comparison field parameter is missing.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenParameterIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEmpty(
            'field',
            '',
            [],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field name is empty.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenComparisonFieldNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEmpty(
            'field',
            '',
            [''],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field name contains only whitespace.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenComparisonFieldNameContainsOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEmpty(
            'field',
            '',
            ['   '],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when field is used as its own comparison field.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenFieldUsesItselfAsComparisonField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-name' => '',
            'form-email' => '',
        ];

        $this->logic->validateRequiredIfEmpty(
            'form-name',
            '',
            ['name'],
            $fields
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field does not exist.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenComparisonFieldDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-name' => '',
            'form-email' => '',
        ];

        $this->logic->validateRequiredIfEmpty(
            'form-target',
            '',
            ['missing'],
            $fields
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field is an upload field.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenComparisonFieldIsUploadField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-upload' => [
                [
                    'name' => '',
                    'full_path' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => 4,
                    'size' => 0,
                ],
            ],
            'form-name' => '',
        ];

        $this->logic->validateRequiredIfEmpty(
            'form-name',
            '',
            ['upload'],
            $fields
        );
    }

    /**
     * Verifies that the result is true when comparison field contains a non-empty string.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldContainsString(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains integer zero.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldContainsIntegerZero(): void
    {
        $fields = [
            'form-status' => 0,
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains string zero.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldContainsStringZero(): void
    {
        $fields = [
            'form-status' => '0',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains a non-empty array.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldContainsNonEmptyArray(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field is null and target field is null.
     */
    public function testValidateRequiredIfEmptyReturnsFalseWhenBothFieldsAreNull(): void
    {
        $fields = [
            'form-status' => null,
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                null,
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field is null and target field is empty string.
     */
    public function testValidateRequiredIfEmptyReturnsFalseWhenComparisonFieldIsNullAndTargetFieldIsEmptyString(): void
    {
        $fields = [
            'form-status' => null,
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field is null and target field contains a value.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldIsNullAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-status' => null,
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                'John',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field is empty string and target field is empty.
     */
    public function testValidateRequiredIfEmptyReturnsFalseWhenComparisonFieldIsEmptyStringAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field is empty string and target field contains a value.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldIsEmptyStringAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                'John',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field is an empty array and target field is empty.
     */
    public function testValidateRequiredIfEmptyReturnsFalseWhenComparisonFieldIsEmptyArrayAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-tags' => [],
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field is an empty array and target field has value.
     */
    public function testValidateRequiredIfEmptyReturnsTrueWhenComparisonFieldIsEmptyArrayAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-tags' => [],
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                'John',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that it treats integer zero as a valid value when target field becomes required.
     */
    public function testValidateRequiredIfEmptyAcceptsIntegerZeroAsTargetValue(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                0,
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that it treats string zero as a valid value when target field becomes required.
     */
    public function testValidateRequiredIfEmptyAcceptsStringZeroAsTargetValue(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '0',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that it resolves comparison field when field name already contains prefix.
     */
    public function testValidateRequiredIfEmptyResolvesAlreadyPrefixedComparisonField(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['form-status'],
                $fields
            )
        );
    }

    /**
     * Verifies that it prepends detected prefix to comparison field name.
     */
    public function testValidateRequiredIfEmptyPrependsPrefixToComparisonFieldName(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that an exception is thrown when only one form field is present.
     */
    public function testValidateRequiredIfEmptyThrowsExceptionWhenOnlyOneFormFieldIsPresent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfEmpty(
            'form-name',
            '',
            ['status'],
            [
                'form-status' => '',
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: requiredIfNotEmpty
    | Method: validateRequiredIfNotEmpty
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that an exception is thrown when comparison field parameter is missing.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenParameterIsMissing(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfNotEmpty(
            'field',
            '',
            [],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field name is empty.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenComparisonFieldNameIsEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfNotEmpty(
            'field',
            '',
            [''],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field name contains only whitespace.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenComparisonFieldNameContainsOnlyWhitespace(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfNotEmpty(
            'field',
            '',
            ['   '],
            [
                'form-field' => '',
                'form-other' => '',
            ]
        );
    }

    /**
     * Verifies that an exception is thrown when field uses itself as comparison field.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenFieldUsesItselfAsComparisonField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-name' => '',
            'form-email' => '',
        ];

        $this->logic->validateRequiredIfNotEmpty(
            'form-name',
            '',
            ['name'],
            $fields
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field does not exist.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenComparisonFieldDoesNotExist(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-name' => '',
            'form-email' => '',
        ];

        $this->logic->validateRequiredIfNotEmpty(
            'form-target',
            '',
            ['missing'],
            $fields
        );
    }

    /**
     * Verifies that an exception is thrown when comparison field is an upload field.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenComparisonFieldIsUploadField(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'form-upload' => [
                [
                    'name' => '',
                    'full_path' => '',
                    'type' => '',
                    'tmp_name' => '',
                    'error' => 4,
                    'size' => 0,
                ],
            ],
            'form-name' => '',
        ];

        $this->logic->validateRequiredIfNotEmpty(
            'form-name',
            '',
            ['upload'],
            $fields
        );
    }

    /**
     * Verifies that the result is true when comparison field is null.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldIsNull(): void
    {
        $fields = [
            'form-status' => null,
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field is empty string.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldIsEmptyString(): void
    {
        $fields = [
            'form-status' => '',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field is an empty array.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldIsEmptyArray(): void
    {
        $fields = [
            'form-tags' => [],
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains a non-empty string and target field is empty.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldHasValueAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field contains a non-empty string and target field contains a value.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenComparisonFieldHasValueAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                'John',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains integer zero and target field is empty.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldContainsIntegerZeroAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-status' => 0,
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field contains integer zero and target field contains a value.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenComparisonFieldContainsIntegerZeroAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-status' => 0,
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                'John',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains string zero and target field is empty.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldContainsStringZeroAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-status' => '0',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field contains string zero and target field contains a value.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenComparisonFieldContainsStringZeroAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-status' => '0',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                'John',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is true when comparison field contains a non-empty array and target field is empty.
     */
    public function testValidateRequiredIfNotEmptyReturnsTrueWhenComparisonFieldContainsNonEmptyArrayAndTargetFieldIsEmpty(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when comparison field contains a non-empty array and target field has a value.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenComparisonFieldContainsNonEmptyArrayAndTargetFieldHasValue(): void
    {
        $fields = [
            'form-tags' => ['php'],
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                'John',
                ['tags'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when target field contains integer zero.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenTargetFieldContainsIntegerZero(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                0,
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that the result is false when target field contains string zero.
     */
    public function testValidateRequiredIfNotEmptyReturnsFalseWhenTargetFieldContainsStringZero(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertFalse(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '0',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that it resolves comparison field when field name already contains prefix.
     */
    public function testValidateRequiredIfNotEmptyResolvesAlreadyPrefixedComparisonField(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['form-status'],
                $fields
            )
        );
    }

    /**
     * Verifies that it prepends detected prefix to comparison field name.
     */
    public function testValidateRequiredIfNotEmptyPrependsPrefixToComparisonFieldName(): void
    {
        $fields = [
            'form-status' => 'active',
            'form-name' => '',
        ];

        $this->assertTrue(
            $this->logic->validateRequiredIfNotEmpty(
                'form-name',
                '',
                ['status'],
                $fields
            )
        );
    }

    /**
     * Verifies that an exception is thrown when only one form field is present.
     */
    public function testValidateRequiredIfNotEmptyThrowsExceptionWhenOnlyOneFormFieldIsPresent(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateRequiredIfNotEmpty(
            'form-name',
            '',
            ['status'],
            [
                'form-status' => '',
            ]
        );
    }


}