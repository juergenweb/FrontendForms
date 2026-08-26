<?php

declare(strict_types=1);

namespace FrontendForms;

use DateInterval;
use DateTimeImmutable;
use Exception;
use ProcessWire\WireDatetime;
use ProcessWire\WireException;

/**
 * Helper class containing reusable date-related utility methods.
 *
 * Provides functionality for:
 * - comparing dates
 * - validating date ranges
 * - converting date strings into timestamps
 */
class DateHelper extends BaseHelper
{
    /**
     * Cached ProcessWire datetime service instance.
     */
    private ?WireDatetime $datetime = null;

    /**
     * Compare two date strings by UNIX timestamp and report whether
     * $date2 lies before (or after) $date1.
     *
     * @param string|null $date1  Reference date.
     * @param string      $date2  Date to compare.
     * @param bool        $before If true, checks if $date2 is before $date1.
     *
     * @return bool True if comparison condition is met, otherwise false.
     *
     * @throws WireException
     */
    public function compareDates(?string $date1, string $date2, bool $before = true): bool
    {
        $date1 = self::normalizeScalar($date1);
        $date2 = self::normalizeScalar($date2);

        if ($date1 === null || $date2 === null) {
            return false;
        }

        $this->datetime ??= $this->wire('datetime');

        $timestamp1 = $this->datetime->strtotime($date1);
        $timestamp2 = $this->datetime->strtotime($date2);

        if ($timestamp1 === false || $timestamp2 === false) {
            return false;
        }

        return $before
            ? $timestamp2 < $timestamp1
            : $timestamp2 > $timestamp1;
    }

    /**
     * Check whether a date falls inside (or outside) a day-range window
     * measured from a base date.
     *
     * @param string|null $date_1 Base/reference date (Y-m-d format), already
     *                             resolved by the caller.
     * @param string $value Date to evaluate (Y-m-d format).
     * @param int $days Range offset in days.
     * @param bool $within If true, checks inclusion in range;
     *                            otherwise checks exclusion.
     *
     * @return bool True if condition is met, otherwise false.
     *
     * @throws WireException
     * @throws Exception
     */
    public function checkDateRange(?string $date_1, string $value, int $days, bool $within = true): bool
    {
        $date_1 = self::normalizeScalar($date_1);

        if ($date_1 === null) {
            return false;
        }

        $start = DateTimeImmutable::createFromFormat('Y-m-d', $date_1);
        $target = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        if (!$start || !$target) {
            return false;
        }

        $end = $days >= 0
            ? $start->add(new DateInterval('P' . $days . 'D'))
            : $start->sub(new DateInterval('P' . abs($days) . 'D'));

        if ($within) {
            return $target >= min($start, $end)
                && $target <= max($start, $end);
        }

        return $target < min($start, $end)
            || $target > max($start, $end);
    }

    /**
     * Validate that a string is a real calendar date in Y-m-d format.
     *
     * @param string|null $value Date string to validate.
     *
     * @return bool True if $value is a valid Y-m-d date.
     */
    public function validateDate(?string $value): bool
    {
        // check if value is present, otherwise return false
        if ($value === null) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }
}