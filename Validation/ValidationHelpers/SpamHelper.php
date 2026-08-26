<?php

declare(strict_types=1);

namespace FrontendForms;

/**
 * Helper class for spam detection and scoring.
 */
class SpamHelper extends BaseHelper
{
    private TextHelper $textHelper;

    /**
     * Create a new SpamHelper instance.
     *
     * @param TextHelper $textHelper Helper dependency for word-list matching.
     */
    public function __construct(TextHelper $textHelper)
    {
        parent::__construct();

        $this->textHelper = $textHelper;
    }

    /**
     * Calculate a spam score (0–100) for the given text based on heuristic checks.
     *
     * Higher values indicate a higher probability of spam.
     * Individual checks can be disabled by name via $excludes.
     *
     * @param string     $text      Input text to analyze.
     * @param array|null $spamWords Optional list of custom spam words to match against.
     * @param array|null $excludes  Optional list of check names to skip.
     *
     * @return int Spam score between 0 and 100.
     */
    public function calculateContentScore(
        string $text,
        ?array $spamWords,
        ?array $excludes
    ): int {
        $score = 0;

        if (empty($text)) {
            return 0;
        }

        $ex = $excludes ? array_flip($excludes) : [];

        $textLower = strtolower($text);
        $textLen = strlen($text);

        // 1. STOPWORDS (high impact)
        if (!isset($ex['stopwords'])) {
            $stopwordPath =
                $this->config->paths->siteModules
                . 'FrontendForms/data/stopwords.txt';

            if ($this->wire('files')->exists($stopwordPath)) {
                $count = count(
                    $this->textHelper->findWords($text, $stopwordPath)
                );

                if ($count >= 5) {
                    return 100;
                }

                if (($score += $count * 20) >= 100) {
                    return 100;
                }
            }
        }

        // 2. CUSTOM WORDS
        if (!isset($ex['customstopwords']) && !empty($spamWords)) {
            foreach ($spamWords as $word) {
                if ($word && str_contains($textLower, strtolower($word))) {
                    if (($score += 20) >= 100) {
                        return 100;
                    }
                }
            }
        }

        // 3. LINKS
        if (!isset($ex['links'])) {
            $httpCount =
                substr_count($textLower, 'http://')
                + substr_count($textLower, 'https://');

            if ($httpCount > 2) {
                if (($score += 20) >= 100) {
                    return 100;
                }
            }
        }

        // 4. CAPITAL LETTERS (ASCII only; Unicode uppercase is a known limitation)
        if (!isset($ex['capitalletters']) && $textLen > 0) {
            $letters = 0;
            $upper = 0;

            for ($i = 0; $i < $textLen; $i++) {
                $ord = ord($text[$i]);

                if (
                    ($ord >= 65 && $ord <= 90)
                    || ($ord >= 97 && $ord <= 122)
                ) {
                    $letters++;

                    if ($ord >= 65 && $ord <= 90) {
                        $upper++;
                    }
                }
            }

            if ($letters > 0 && ($upper / $letters) > 0.5) {
                if (($score += 15) >= 100) {
                    return 100;
                }
            }
        }

        // 5. REPEATED CHAR PATTERNS
        if (!isset($ex['repeatedchars'])) {
            if (
                str_contains($text, '!!')
                || str_contains($text, '??')
                || str_contains($text, '$$')
                || str_contains($text, '##')
            ) {
                if (($score += 10) >= 100) {
                    return 100;
                }
            }
        }

        // 6. EXCLAMATIONS
        if (!isset($ex['exclamations'])) {
            if (substr_count($text, '!') > 5) {
                if (($score += 10) >= 100) {
                    return 100;
                }
            }
        }

        // 7. LENGTH CHECK
        if (!isset($ex['length']) && $score > 0 && $textLen < 50) {
            $score += 10;
        }

        return min($score, 100);
    }
}