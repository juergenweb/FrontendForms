<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\DateHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for DateHelper.
 */
final class DateHelperTest extends TestCase
{
    // --- compareDates() ---

    /**
     * 1) With the default $before=true, returns true when date2 is
     * earlier than date1.
     */
    public function testCompareDatesReturnsTrueWhenSecondDateIsBefore(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->compareDates('2025-06-15', '2025-06-10'));
    }

    /**
     * 2) With the default $before=true, returns false when date2 is later
     * than date1.
     */
    public function testCompareDatesReturnsFalseWhenSecondDateIsAfter(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->compareDates('2025-06-15', '2025-06-20'));
    }

    /**
     * 3) With $before=false, returns true when date2 is later than date1.
     */
    public function testCompareDatesWithBeforeFalseChecksAfter(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->compareDates('2025-06-15', '2025-06-20', false));
    }

    /**
     * 4) Equal dates never satisfy a strict before/after comparison.
     */
    public function testCompareDatesReturnsFalseForEqualDates(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->compareDates('2025-06-15', '2025-06-15'));
        $this->assertFalse($helper->compareDates('2025-06-15', '2025-06-15', false));
    }

    /**
     * 5) A null first date returns false rather than throwing.
     */
    public function testCompareDatesReturnsFalseForNullFirstDate(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->compareDates(null, '2025-06-15'));
    }

    // --- checkDateRange() ---

    /**
     * 7) A date within a future range (positive days) is correctly
     * detected as inside.
     */
    public function testCheckDateRangeWithinFutureRange(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->checkDateRange('2025-06-01', '2025-06-05', 10));
    }

    /**
     * 8) A date outside a future range (positive days) is correctly
     * detected as not within.
     */
    public function testCheckDateRangeOutsideFutureRange(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->checkDateRange('2025-06-01', '2025-06-20', 10));
    }

    /**
     * 9) A negative day range correctly checks a window in the past,
     * regardless of the min/max direction.
     */
    public function testCheckDateRangeWithinPastRange(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->checkDateRange('2025-06-15', '2025-06-10', -10));
    }

    /**
     * 10) With $within=false, a date OUTSIDE the range is correctly
     * detected as satisfying the "outside" condition.
     */
    public function testCheckDateRangeOutsideConditionTrueWhenActuallyOutside(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->checkDateRange('2025-06-01', '2025-06-20', 10, false));
    }

    /**
     * 11) With $within=false, a date INSIDE the range does not satisfy the
     * "outside" condition.
     */
    public function testCheckDateRangeOutsideConditionFalseWhenActuallyInside(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->checkDateRange('2025-06-01', '2025-06-05', 10, false));
    }

    /**
     * 12) The range boundary itself counts as "within" (inclusive check).
     */
    public function testCheckDateRangeBoundaryIsInclusive(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->checkDateRange('2025-06-01', '2025-06-11', 10));
    }

    /**
     * 13) A null base date returns false rather than throwing.
     */
    public function testCheckDateRangeReturnsFalseForNullBaseDate(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->checkDateRange(null, '2025-06-05', 10));
    }

    /**
     * 14) An unparsable target date returns false rather than throwing.
     */
    public function testCheckDateRangeReturnsFalseForUnparsableTargetDate(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->checkDateRange('2025-06-01', 'not-a-date', 10));
    }

    // --- validateDate() ---

    /**
     * 15) A genuine, correctly formatted calendar date is valid.
     */
    public function testValidateDateAcceptsRealDate(): void
    {
        $helper = new DateHelper();

        $this->assertTrue($helper->validateDate('2025-06-15'));
    }

    /**
     * 16) A null value is rejected.
     */
    public function testValidateDateRejectsNull(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->validateDate(null));
    }

    /**
     * 17) A non-existent calendar date (e.g. February 30th) is rejected,
     * even though DateTimeImmutable would otherwise silently roll it over
     * to a nearby real date (confirmed standalone: 2025-02-30 becomes
     * 2025-03-02) - the round-trip format comparison catches this.
     */
    public function testValidateDateRejectsAutoCorrectedDate(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->validateDate('2025-02-30'));
    }

    /**
     * 18) A malformed date string is rejected.
     */
    public function testValidateDateRejectsMalformedString(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->validateDate('not-a-date'));
    }

    /**
     * 19) A date in the wrong format (e.g. DD-MM-YYYY) is rejected.
     */
    public function testValidateDateRejectsWrongFormat(): void
    {
        $helper = new DateHelper();

        $this->assertFalse($helper->validateDate('15-06-2025'));
    }
}