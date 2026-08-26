<?php

declare(strict_types=1);

namespace FrontendForms;

use InvalidArgumentException;

/**
 * Helper class for text processing and word detection.
 */
class TextHelper extends BaseHelper
{
    private static array $patterns = [];

    /**
     * Create a new TextHelper instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Search a text for any words found in a word-list file.
     *
     * Regex patterns are built from the file once and cached statically
     * (keyed by resolved file path) to avoid redundant processing.
     *
     * @param string $text      Input text to search.
     * @param string $filePath  Absolute path to the word-list file (one word per line).
     * @param int    $chunkSize Number of words per regex alternation chunk (default 100).
     *
     * @return string[] Unique matched words found in the text.
     *
     * @throws InvalidArgumentException If the file is unreadable, too large, or $chunkSize < 1.
     */
    final public function findWords(
        string $text,
        string $filePath,
        int $chunkSize = 100
    ): array {
        if ($text === '') {
            return [];
        }

        if ($chunkSize < 1) {
            throw new InvalidArgumentException(
                'Chunk size must be greater than 0.'
            );
        }

        $realPath = realpath($filePath);

        if (
            $realPath === false
            || !is_file($realPath)
            || !is_readable($realPath)
        ) {
            throw new InvalidArgumentException(
                "File could not be read: $filePath"
            );
        }

        if (filesize($realPath) > 5_000_000) {
            throw new InvalidArgumentException(
                'Word list too large.'
            );
        }

        if (mb_strlen($text) > 1_000_000) {
            throw new InvalidArgumentException(
                'Text too large.'
            );
        }

        if (count(self::$patterns) > 100) {
            unset(self::$patterns[array_key_first(self::$patterns)]);
        }

        if (!isset(self::$patterns[$realPath])) {
            $words = file(
                $realPath,
                FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES
            );

            if ($words === false) {
                throw new InvalidArgumentException(
                    'Unable to read word list.'
                );
            }

            $words = array_filter(
                array_map('trim', $words),
                static fn (string $word): bool => $word !== ''
            );

            $words = array_keys(array_flip($words));

            $escaped = array_map(
                static fn (string $word): string => preg_quote($word, '/'),
                $words
            );

            usort(
                $escaped,
                static fn (string $a, string $b): int => strlen($b) <=> strlen($a)
            );

            $chunks = array_chunk($escaped, $chunkSize);

            self::$patterns[$realPath] = array_map(
                static fn (array $chunk): string =>
                    '/(?<![\p{L}\p{N}_])(?:' .
                    implode('|', $chunk) .
                    ')(?![\p{L}\p{N}_])/ui',
                $chunks
            );
        }

        $matchesFound = [];

        foreach (self::$patterns[$realPath] as $pattern) {
            $result = preg_match_all($pattern, $text, $matches);

            if ($result === false) {
                $this->wire('log')->save(
                    'customvalidation',
                    'Regex failed: ' . preg_last_error_msg()
                );
                continue;
            }

            if (!empty($matches[0])) {
                array_push($matchesFound, ...$matches[0]);

                if (count($matchesFound) >= 5000) {
                    break;
                }
            }
        }

        return array_keys(array_flip($matchesFound));
    }
}