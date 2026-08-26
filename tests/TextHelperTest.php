<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\TextHelper;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for TextHelper.
 */
final class TextHelperTest extends TestCase
{
    private function wordListPath(): string
    {
        return __DIR__ . '/fixtures/test-wordlist.txt';
    }

    // --- findWords() ---

    /**
     * 1) Empty text returns an empty array without touching the word-list
     * file.
     */
    public function testFindWordsReturnsEmptyArrayForEmptyText(): void
    {
        $helper = new TextHelper();

        $this->assertSame([], $helper->findWords('', $this->wordListPath()));
    }

    /**
     * 2) A word from the list, present in the text, is found.
     */
    public function testFindWordsFindsMatchingWord(): void
    {
        $helper = new TextHelper();

        $result = $helper->findWords('Buy cheap viagra online now!', $this->wordListPath());

        $this->assertSame(['viagra'], $result);
    }

    /**
     * 3) The search is case-insensitive.
     */
    public function testFindWordsIsCaseInsensitive(): void
    {
        $helper = new TextHelper();

        $result = $helper->findWords('VIAGRA for sale', $this->wordListPath());

        $this->assertSame(['VIAGRA'], $result);
    }

    /**
     * 4) A word boundary is respected - a word list entry must not match
     * as a mere substring of a longer, unrelated word ("spam" must not
     * match inside "spammer", since "m" immediately follows and fails the
     * negative lookahead).
     */
    public function testFindWordsRespectsWordBoundaries(): void
    {
        $helper = new TextHelper();

        $result = $helper->findWords('He is a notorious spammer', $this->wordListPath());

        $this->assertSame([], $result);
    }

    /**
     * 5) Multiple different matching words are all found, each only once.
     */
    public function testFindWordsFindsMultipleUniqueMatches(): void
    {
        $helper = new TextHelper();

        $result = $helper->findWords('Win the lottery! Buy viagra! Another viagra ad here.', $this->wordListPath());

        sort($result);
        $this->assertSame(['lottery', 'viagra'], $result);
    }

    /**
     * 6) Text with no matches returns an empty array.
     */
    public function testFindWordsReturnsEmptyArrayForNoMatches(): void
    {
        $helper = new TextHelper();

        $result = $helper->findWords('This is a perfectly normal sentence.', $this->wordListPath());

        $this->assertSame([], $result);
    }

    /**
     * 7) A non-existent word-list file throws an InvalidArgumentException.
     */
    public function testFindWordsThrowsForNonExistentFile(): void
    {
        $helper = new TextHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->findWords('some text', __DIR__ . '/fixtures/this-file-does-not-exist.txt');
    }

    /**
     * 8) A chunk size below 1 throws an InvalidArgumentException.
     */
    public function testFindWordsThrowsForInvalidChunkSize(): void
    {
        $helper = new TextHelper();

        $this->expectException(InvalidArgumentException::class);

        $helper->findWords('some text', $this->wordListPath(), 0);
    }
}
