<?php

declare(strict_types=1);

/*
 * Abstract class for creating text-based CAPTCHAs from a fixed character set
 * (random string, reversed string, every-second-character variants, ...)
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: AbstractCharset.php
 * Created: 16.08.2022
 */

namespace FrontendForms;

use ProcessWire\WireException;
use ProcessWire\WirePermissionException;

abstract class AbstractCharset extends AbstractTextCaptcha
{
    /**
     * @throws WireException
     * @throws WirePermissionException
     */
    public function __construct()
    {
        parent::__construct();

        // set random string as content for the captcha
        $this->setCaptchaContent($this->createRandomString());
    }


    /**
     * Get the string of the charset that will be used to create the captcha text
     *
     * Defensively decodes the value if it is still a literal JSON
     * \uXXXX-escaped string (e.g. "\u0410\u0411...") rather than actual
     * UTF-8 characters - this can happen depending on how the module
     * config round-trips through storage. Left undecoded, mb_strlen()/
     * mb_substr() in createRandomString() would operate on the literal
     * backslash/u/hex-digit text instead of the intended characters,
     * producing captchas built from a small, repetitive set of ASCII
     * characters instead of the configured charset.
     * @return string
     */
    protected function getCharset(): string
    {
        $charset = $this->frontendforms['input_captchaCharset'];
        if (preg_match('/\\\\u[0-9a-fA-F]{4}/', $charset)) {
            $decoded = json_decode('"' . str_replace('"', '\\"', $charset) . '"');
            if (is_string($decoded) && $decoded !== '') {
                $charset = $decoded;
            }
        }
        return $charset;
    }

    /**
     * Get the number of characters
     * Needs typecasting because value will be stored as string in the database
     * @return int
     */
    protected function getNumberOfCharacters(): int
    {
        return (int) $this->frontendforms['input_captchaNumberOfCharacters'];
    }

    /**
     * Create the random string for the captcha depending on the number and charset setting
     * @return string
     * @throws \RuntimeException If the configured character set is empty.
     */
    protected function createRandomString(): string
    {
        $charset = $this->getCharset();

        if ($charset === '') {
            throw new \RuntimeException(
                'The captcha character set is empty. Please configure a character set in the module settings.'
            );
        }

        // mb_strlen()/mb_substr() (not strlen() / direct string indexing)
        // are essential here: strlen() counts bytes, and $charset[$index]
        // grabs a single byte - for multi-byte UTF-8 characters (e.g.
        // Cyrillic letters, each 2 bytes), this can land in the middle of
        // a character's byte sequence, producing an invalid, corrupted
        // UTF-8 fragment instead of a complete character.
        $charsetLength = mb_strlen($charset, 'UTF-8');
        $randomString = '';
        for ($i = 0; $i < $this->getNumberOfCharacters(); $i++) {
            $index = rand(0, $charsetLength - 1);
            $randomString .= mb_substr($charset, $index, 1, 'UTF-8');
        }
        return $randomString;
    }

}
