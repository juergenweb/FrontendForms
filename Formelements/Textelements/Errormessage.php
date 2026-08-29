<?php

declare(strict_types=1);

namespace FrontendForms;

/*
 * Class for creating an error message under an input element
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: Errormessage.php
 * Created: 03.07.2022
 * Optimized via Claude AI 05.05.26
 */

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

class Errormessage extends TextElements
{
    use TraitTags;

    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct(?string $id = null)
    {
        parent::__construct($id);
        $this->setCSSClass('error_messageClass');
    }

    /**
     * Find all array-like substrings in a larger string, where the whole
     * "[...]" portion is wrapped in an outer pair of single quotes (e.g.
     * "...contains '['16:9', '4:3']' as a placeholder..."). Returns the
     * inner array-like strings (without the outer quotes, e.g.
     * "['16:9', '4:3']"), ready to be passed to
     * arrayLikeStringToCommaSeparated().
     * @param string $text
     * @return string[]
     */
    protected function findArrayLikeStrings(string $text): array
    {
        preg_match_all('/\[[^\]]*\]/', $text, $matches);
        return $matches[0];
    }

    /**
     * Replace every array-like substring in a text (e.g. "['16:9','4:3']"
     * or "[4,3]") with its plain, comma-separated form (e.g. "16:9, 4:3" or
     * "4, 3"), leaving the rest of the text untouched.
     * @param string $text
     * @return string
     */
    public static function replaceArrayLikeStringsInText(string $text): string
    {
        return preg_replace_callback(
            '/\[[^\]]*\]/',
            static fn (array $matches): string => self::arrayLikeStringToCommaSeparated($matches[0]),
            $text
        );
    }

    public function render(): string
    {
        $markup = parent::render();
        // convert string arrays to comma separated list.
        $stringArrays = $this->findArrayLikeStrings($markup);

        if(count($stringArrays) > 0) {
            $markup = FormHelper::replaceArrayLikeStringsInText($markup);
        }

        return $markup;
    }

}
