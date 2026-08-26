<?php

declare(strict_types=1);

namespace Tests;

use Exception;
use InvalidArgumentException;
use FrontendForms\DateLogic;
use FrontendForms\Form;
use PHPUnit\Framework\TestCase;
use FrontendForms\DateHelper;
use FrontendForms\FieldNameResolverHelper;
use stdClass;

/**
 * Unit tests for DateLogic validation methods.
 *
 * Covers: isWeek, isMonth, isDateBeforeField, isDateAfterField,
 * validateDateInsideOfDaysRange, validateDateOutsideOfDaysRange.
 */
final class DateLogicTest extends TestCase
{
    private DateLogic $logic;

    /**
     * Create a fully wired DateLogic instance shared across all tests.
     */
    protected function setUp(): void
    {
        $dateHelper = new DateHelper();
        $fieldHelper = new FieldNameResolverHelper();

        $form = $this->createMock(Form::class);
        $form->method('getID')->willReturn('test');

        $this->logic = new DateLogic($dateHelper, $fieldHelper);
        $this->logic->setForm($form);
        $dateHelper->setForm($form);
        $fieldHelper->setForm($form);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: dateBeforeField
    | Method: isDateBeforeField
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a date before the referenced field returns true.
     */
    public function testDateBeforeFieldReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => '2025-01-01',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-01',
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that a date after the referenced field returns false.
     */
    public function testDateAfterFieldReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate'   => '2025-01-31',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-31',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 3) Verifies that equal dates return false.
     */
    public function testEqualDatesReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-15',
            'test-endDate'   => '2025-01-15',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-15',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 4) Verifies that an empty string is treated as a valid value.
     */
    public function testEmptyStringReturnsTrueIsDateBeforeField(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => '',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '',
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 6) Verifies that null is treated as empty value.
     */
    public function testNullReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => null,
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            null,
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 7) Verifies that whitespace-only input is treated as empty value.
     */
    public function testWhitespaceReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => '   ',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '   ',
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 8) Verifies that an invalid current date returns false.
     */
    public function testInvalidCurrentDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => 'invalid-date',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            'invalid-date',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 9) Verifies that an invalid referenced date returns false.
     */
    public function testInvalidReferencedDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => 'invalid-date',
            'test-endDate'   => '2025-01-15',
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-01',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 10) Verifies that a missing parameter throws an exception.
     */
    public function testMissingParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-01',
            [],
            []
        );
    }

    /**
     * 11) Verifies that a non-string field parameter throws an exception.
     */
    public function testInvalidFieldParameterTypeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-01',
            [[1]],
            []
        );
    }

    /**
     * 12) Verifies that an unknown reference field throws an exception.
     */
    public function testUnknownReferenceFieldThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => '2025-01-15',
        ];

        $this->logic->isDateBeforeField(
            'endDate',
            '2025-01-01',
            ['unknownField'],
            $fields
        );
    }

    /**
     * 13) Verifies that boolean true is rejected.
     */
    public function testBooleanTrueReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => true,
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            true,
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 14) Verifies that boolean false is treated as an empty value.
     */
    public function testBooleanFalseReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => false,
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            false,
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 15) Verifies that integer input is rejected.
     */
    public function testIntegerReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => 20250101,
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            20250101,
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 17) Verifies that float input is rejected.
     */
    public function testFloatReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => 2025.01,
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            2025.01,
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 18) Verifies that arrays are rejected.
     */
    public function testArrayReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => ['2025-01-01'],
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            ['2025-01-01'],
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 19) Verifies that objects are rejected.
     */
    public function testObjectReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-31',
            'test-endDate'   => new stdClass(),
        ];

        $result = $this->logic->isDateBeforeField(
            'endDate',
            new stdClass(),
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: dateAfterField
    | Method: isDateAfterField
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a valid date after the reference field returns true.
     */
    public function testDateAfterFieldReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate'   => '2025-01-10',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            '2025-01-10',
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that a date before the reference field returns false.
     */
    public function testDateBeforeFieldReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => '2025-01-01',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            '2025-01-01',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 3) Verifies that equal dates return false.
     */
    public function testEqualDatesReturnFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => '2025-01-10',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            '2025-01-10',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 4) Verifies that empty values are treated as valid (true).
     */
    public function testEmptyValueReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => '',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            '',
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 5) Verifies that null values are treated as valid (true).
     */
    public function testNullValueReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => null,
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            null,
            ['startDate'],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 6) Verifies that arrays are rejected.
     */
    public function testArrayReturnsFalseDateAfter(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => ['2025-01-01'],
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            ['2025-01-01'],
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 7) Verifies that objects are rejected.
     */
    public function testObjectReturnsFalseDateAfter(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => new stdClass(),
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            new stdClass(),
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 8) Verifies that integer values are rejected.
     */
    public function testIntegerReturnsFalseDateAfter(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => 123,
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            123,
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 9) Verifies that invalid date strings return false.
     */
    public function testInvalidDateStringReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-10',
            'test-endDate'   => 'not-a-date',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            'not-a-date',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 10) Verifies that invalid reference field date returns false.
     */
    public function testInvalidReferenceDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => 'invalid-date',
            'test-endDate'   => '2025-01-10',
        ];

        $result = $this->logic->isDateAfterField(
            'endDate',
            '2025-01-10',
            ['startDate'],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 11) Verifies that a missing parameter throws an exception.
     */
    public function testMissingParameterThrowsExceptionDateAfter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate'   => '2025-01-10',
        ];

        $this->logic->isDateAfterField(
            'endDate',
            '2025-01-10',
            [],
            $fields
        );
    }

    /**
     * 12) Verifies that a non-string parameter throws an exception.
     */
    public function testNonStringParameterThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate'   => '2025-01-10',
        ];

        $this->logic->isDateAfterField(
            'endDate',
            '2025-01-10',
            [123],
            $fields
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: isWeek
    | Method: isWeek
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a valid ISO week returns true.
     */
    public function testValidWeekReturnsTrue(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W10',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that week 1 is accepted.
     */
    public function testWeekOneReturnsTrue(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W01',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 3) Verifies that week 53 is accepted for a year that contains week 53.
     */
    public function testValidWeekFiftyThreeReturnsTrue(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2020-W53',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 4) Verifies that week 53 is rejected for a year that has only 52 ISO weeks.
     */
    public function testInvalidWeekFiftyThreeReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2021-W53',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 5) Verifies that week 00 is rejected.
     */
    public function testWeekZeroReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W00',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 6) Verifies that week numbers greater than 53 are rejected.
     */
    public function testWeekGreaterThanFiftyThreeReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W54',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 11) Verifies that an invalid format without 'W' is rejected.
     */
    public function testMissingWeekPrefixReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-10',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 12) Verifies that a malformed week string is rejected.
     */
    public function testMalformedWeekReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            'abcd-W10',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 13) Verifies that single-digit week numbers are rejected.
     */
    public function testSingleDigitWeekReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W1',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 14) Verifies that additional characters cause validation to fail.
     */
    public function testWeekWithTrailingCharactersReturnsFalse(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '2025-W10abc',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 15) Verifies that an empty string is treated as an empty value.
     */
    public function testEmptyStringReturnsTrue(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 16) Verifies that null is treated as an empty value.
     */
    public function testNullReturnsTrueIsWeek(): void
    {
        $result = $this->logic->isWeek(
            'week',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 17) Verifies that whitespace-only input is treated as empty.
     */
    public function testWhitespaceReturnsTrueIsWeek(): void
    {
        $result = $this->logic->isWeek(
            'week',
            '   ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 18) Verifies that boolean true is rejected.
     */
    public function testBooleanTrueReturnsFalseIsWeek(): void
    {
        $result = $this->logic->isWeek(
            'week',
            true,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 19) Verifies that boolean false is treated as an empty value.
     */
    public function testBooleanFalseReturnsTrueIsWeek(): void
    {
        $result = $this->logic->isWeek(
            'week',
            false,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 20) Verifies that arrays are treated as invalid input.
     */
    public function testArrayReturnsFalseIsWeek(): void
    {
        $result = $this->logic->isWeek(
            'week',
            ['2025-W10'],
            [],
            []
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: isMonth
    | Method: isMonth
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a valid month returns true.
     */
    public function testValidMonthReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-06',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that January is accepted.
     */
    public function testJanuaryReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-01',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 3) Verifies that December is accepted.
     */
    public function testDecemberReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-12',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 4) Verifies that month 00 is rejected.
     */
    public function testMonthZeroReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-00',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 5) Verifies that month 13 is rejected.
     */
    public function testMonthThirteenReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-13',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 6) Verifies that a single-digit month is rejected.
     */
    public function testSingleDigitMonthReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-6',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 11) Verifies that a missing month separator is rejected.
     */
    public function testMissingSeparatorReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '202506',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 12) Verifies that an invalid separator is rejected.
     */
    public function testWrongSeparatorReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025/06',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 13) Verifies that additional trailing characters are rejected.
     */
    public function testTrailingCharactersReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '2025-06abc',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 14) Verifies that alphabetic input is rejected.
     */
    public function testAlphabeticInputReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            'abcd-ef',
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 15) Verifies that an empty string is treated as an empty value.
     */
    public function testMonthEmptyStringReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 16) Verifies that null is treated as an empty value.
     */
    public function testMonthNullReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            null,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 17) Verifies that whitespace-only input is treated as an empty value.
     */
    public function testMonthWhitespaceReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            '   ',
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 18) Verifies that boolean true is rejected.
     */
    public function testMonthBooleanTrueReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            true,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 19) Verifies that boolean false is treated as an empty value.
     */
    public function testMonthBooleanFalseReturnsTrue(): void
    {
        $result = $this->logic->isMonth(
            'month',
            false,
            [],
            []
        );

        $this->assertTrue($result);
    }

    /**
     * 20) Verifies that integer input is rejected.
     */
    public function testIntegerReturnsFalseIsMonth(): void
    {
        $result = $this->logic->isMonth(
            'month',
            202506,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 21) Verifies that float input is rejected.
     */
    public function testFloatReturnsFalseIsMonth(): void
    {
        $result = $this->logic->isMonth(
            'month',
            2025.06,
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 22) Verifies that arrays are rejected.
     */
    public function testMonthArrayReturnsFalse(): void
    {
        $result = $this->logic->isMonth(
            'month',
            ['2025-06'],
            [],
            []
        );

        $this->assertFalse($result);
    }

    /**
     * 23) Verifies that objects are rejected.
     */
    public function testObjectReturnsFalseIsMonth(): void
    {
        $result = $this->logic->isMonth(
            'month',
            new stdClass(),
            [],
            []
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | DATE COMPARISON VALIDATION
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies that isDateBeforeField throws exception when no params are provided.
     */
    public function testDateBeforeFieldWithoutParamsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->isDateBeforeField(
            'date',
            '2025-01-01',
            [],
            []
        );
    }

    /**
     * Verifies that isDateAfterField throws exception when no params are provided.
     */
    public function testDateAfterFieldWithoutParamsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->isDateAfterField(
            'date',
            '2025-01-01',
            [],
            []
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: dateInsideOfDaysRange
    | Method: validateDateInsideOfDaysRange
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a date inside the configured future range is accepted.
     * @throws Exception
     */
    public function testDateInsideFutureRangeReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-01-15',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-01-15',
            ['startDate', 30],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 1a) Verifies that a date inside the configured past range is accepted.
     * @throws Exception
     */
    public function testDateInsidePastRangeReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-20',
            'test-endDate' => '2025-01-15',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-01-15',
            ['startDate', -10],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that a date outside the configured future range is rejected.
     * @throws Exception
     */
    public function testDateInsideFutureRangeReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-02-15',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-15',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 2a) Verifies that a date outside the configured past range is rejected.
     * @throws Exception
     */
    public function testDateInsidePastRangeReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-30',
            'test-endDate' => '2025-01-15',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-01-15',
            ['startDate', -10],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 3) Verifies that an invalid reference date returns false.
     * @throws Exception
     */
    public function testInsideInvalidReferenceDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => 'invalid-date',
            'test-endDate' => '2025-02-01',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 4) Verifies that missing parameters throw an exception.
     * @throws Exception
     */
    public function testInsideMissingParametersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            [],
            []
        );
    }

    /**
     * 5) Verifies that a reference field that does not exist throws an exception.
     * @throws Exception
     */
    public function testInsideMissingReferenceFieldThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['notexist', 10],
            []
        );
    }

    /**
     * 6) Verifies that a negative day range throws an exception.
     * @throws Exception
     */
    public function testInsideNegativeDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', -1],
            [
                'startDate' => '2025-01-01',
            ]
        );
    }

    /**
     * 7) Verifies that a non-numeric day range throws an exception.
     * @throws Exception
     */
    public function testInsideInvalidDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', 'abc'],
            [
                'startDate' => '2025-01-01',
            ]
        );
    }

    /**
     * 8) Verifies that an empty parameter for reference date field throws an exception.
     * @throws Exception
     */
    public function testInsideEmptyParameterTypeForRefFieldInvalidDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['', 10],
            []
        );
    }

    /**
     * 9) Verifies that a date matching exactly the end of the date range returns true.
     * @throws Exception
     */
    public function testInsideExactlyMatchDateRangeReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-01-30',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            '2025-01-01',
            ['startDate', 30],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 10) Verifies that an invalid date value returns false.
     * @throws Exception
     */
    public function testInsideValueIsNotValidDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-02-30',
            'test-endDate' => '2025-02-01',
        ];

        $result = $this->logic->validateDateInsideOfDaysRange(
            'endDate',
            'abc',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | Rule: dateOutsideOfDaysRange
    | Method: validateDateOutsideOfDaysRange
    |--------------------------------------------------------------------------
    */

    /**
     * 1) Verifies that a date outside the configured future range is accepted.
     * @throws Exception
     */
    public function testDateOutsideFutureRangeReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-02-15',
        ];

        $result = $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-15',
            ['startDate', 30],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 2) Verifies that a date inside the configured future range is rejected.
     * @throws Exception
     */
    public function testDateOutsideFutureRangeReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-01-15',
        ];

        $result = $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-01-15',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 3) Verifies that an invalid reference date returns false.
     * @throws Exception
     */
    public function testInvalidReferenceDateReturnsFalseDateOutside(): void
    {
        $fields = [
            'test-startDate' => 'invalid-date',
            'test-endDate' => '2025-02-01',
        ];

        $result = $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }

    /**
     * 4) Verifies that missing parameters throw an exception.
     * @throws Exception
     */
    public function testMissingParametersThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            [],
            []
        );
    }

    /**
     * 5) Verifies that a reference field that does not exist throws an exception.
     * @throws Exception
     */
    public function testMissingReferenceFieldThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['notexist', 10],
            []
        );
    }

    /**
     * 6) Verifies that a negative day range throws an exception.
     * @throws Exception
     */
    public function testNegativeDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', -1],
            [
                'startDate' => '2025-01-01',
            ]
        );
    }

    /**
     * 7) Verifies that a non-numeric day range throws an exception.
     * @throws Exception
     */
    public function testInvalidDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', 'abc'],
            [
                'startDate' => '2025-01-01',
            ]
        );
    }

    /**
     * 8) Verifies that an empty parameter for reference date field throws an exception.
     * @throws Exception
     */
    public function testEmptyParameterTypeForRefFieldInvalidDayRangeThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['', 10],
            []
        );
    }

    /**
     * 9) Verifies that a date matching exactly the end of the date range returns true.
     * @throws Exception
     */
    public function testExactlyMatchDateRangeReturnsTrue(): void
    {
        $fields = [
            'test-startDate' => '2025-01-01',
            'test-endDate' => '2025-01-30',
        ];

        $result = $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            '2025-02-01',
            ['startDate', 30],
            $fields
        );

        $this->assertTrue($result);
    }

    /**
     * 10) Verifies that an invalid date value returns false.
     * @throws Exception
     */
    public function testValueIsNotValidDateReturnsFalse(): void
    {
        $fields = [
            'test-startDate' => '2025-02-30',
            'test-endDate' => '2025-02-01',
        ];

        $result = $this->logic->validateDateOutsideOfDaysRange(
            'endDate',
            'abc',
            ['startDate', 30],
            $fields
        );

        $this->assertFalse($result);
    }
}
