<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SpamHelper;
use FrontendForms\TextHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for SpamHelper.
 *
 * All expected scores were computed by running the exact same scoring
 * algorithm standalone (with the file-dependent "stopwords" check
 * excluded, since that file's presence/content isn't controllable from a
 * unit test) before writing these assertions.
 */
final class SpamHelperTest extends TestCase
{
    private function makeHelper(): SpamHelper
    {
        return new SpamHelper(new TextHelper());
    }

    /**
     * 1) Empty text always scores 0.
     */
    public function testCalculateContentScoreReturnsZeroForEmptyText(): void
    {
        $helper = $this->makeHelper();

        $this->assertSame(0, $helper->calculateContentScore('', null, ['stopwords']));
    }

    /**
     * 2) A long, unremarkable message scores 0.
     */
    public function testCalculateContentScoreReturnsZeroForNormalText(): void
    {
        $helper = $this->makeHelper();

        $text = 'Hello this is a totally normal message with enough length to avoid the length penalty ok';

        $this->assertSame(0, $helper->calculateContentScore($text, null, ['stopwords']));
    }

    /**
     * 3) A custom spam word match, combined with heavy capitalization and
     * short text, accumulates the expected combined score (20 + 15 + 10 =
     * 45).
     */
    public function testCalculateContentScoreCombinesMultipleFactors(): void
    {
        $helper = $this->makeHelper();

        $score = $helper->calculateContentScore('BUY VIAGRA NOW', ['viagra'], ['stopwords']);

        $this->assertSame(45, $score);
    }

    /**
     * 4) More than 2 links triggers the links penalty (20 points), with
     * text long enough to avoid the length penalty.
     */
    public function testCalculateContentScoreDetectsExcessiveLinks(): void
    {
        $helper = $this->makeHelper();

        $text = 'check http://a.com http://b.com http://c.com out this is long enough text to skip length';

        $this->assertSame(20, $helper->calculateContentScore($text, null, ['stopwords']));
    }

    /**
     * 5) A repeated character pattern ("!!") combined with short text
     * scores 20 (10 + 10 length penalty).
     */
    public function testCalculateContentScoreDetectsRepeatedCharsWithShortText(): void
    {
        $helper = $this->makeHelper();

        $this->assertSame(20, $helper->calculateContentScore('wow!!', null, ['stopwords']));
    }

    /**
     * 6) Excessive exclamation marks (>5) combined with the repeated-char
     * pattern and short text scores 30 (10 + 10 + 10).
     */
    public function testCalculateContentScoreDetectsExcessiveExclamations(): void
    {
        $helper = $this->makeHelper();

        $this->assertSame(30, $helper->calculateContentScore('test!!!!!!', null, ['stopwords']));
    }

    /**
     * 7) A named check can be individually excluded, even when it would
     * otherwise trigger.
     */
    public function testCalculateContentScoreRespectsExcludedCheck(): void
    {
        $helper = $this->makeHelper();

        $score = $helper->calculateContentScore('wow!!', null, ['stopwords', 'repeatedchars', 'length']);

        $this->assertSame(0, $score);
    }

    /**
     * 8) The score never exceeds 100, even when many factors combine.
     */
    public function testCalculateContentScoreCapsAtOneHundred(): void
    {
        $helper = $this->makeHelper();

        $text = 'BUY VIAGRA CIALIS NOW!!!! http://a.com http://b.com http://c.com';

        $score = $helper->calculateContentScore($text, ['viagra', 'cialis'], ['stopwords']);

        $this->assertLessThanOrEqual(100, $score);
        $this->assertGreaterThan(0, $score);
    }
}
