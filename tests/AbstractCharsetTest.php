<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\AbstractCharset;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A minimal concrete AbstractCharset subclass for testing. AbstractCharset
 * itself is already fully concrete via its parent AbstractTextCaptcha
 * (which implements both createCaptchaImage() and createCaptchaInputField()),
 * so no method overrides are needed here to make the class instantiable -
 * only public wrappers for the protected methods under test.
 */
final class ConcreteCharset extends AbstractCharset
{
    public function exposeGetCharset(): string
    {
        return $this->getCharset();
    }

    public function exposeGetNumberOfCharacters(): int
    {
        return $this->getNumberOfCharacters();
    }

    public function exposeCreateRandomString(): string
    {
        return $this->createRandomString();
    }

    public function exposeGetCaptchaContent(): string
    {
        return $this->getCaptchaContent();
    }
}

/**
 * Unit tests for AbstractCharset.
 *
 * getCharset()/getNumberOfCharacters() read from the "frontendforms" module
 * config array, which depends on the live test environment - forced to
 * known values via ReflectionProperty for deterministic results (same
 * technique as AbstractCaptchaTest/AbstractImageCaptchaTest). Numeric
 * config values are deliberately set as STRINGS, matching how ProcessWire
 * actually stores/returns module config values - this is what caught the
 * missing (int) cast in getNumberOfCharacters() in the first place; see
 * testGetNumberOfCharactersCastsConfigToInt() below.
 */
final class AbstractCharsetTest extends TestCase
{
    private function setConfig(ConcreteCharset $captcha, array $values): void
    {
        $prop = new ReflectionProperty($captcha, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($captcha);
        foreach ($values as $key => $value) {
            $config[$key] = $value;
        }
        $prop->setValue($captcha, $config);
    }

    /**
     * Build a ConcreteCharset with a known, fixed config, bypassing the
     * constructor's own createRandomString() call (which would otherwise
     * run against whatever the live environment's config happens to be)
     * by re-forcing the config immediately afterward. The constructor's own
     * random content is not asserted against in tests that need a specific
     * charset/length - only testConstructorSetsRandomCaptchaContent() does.
     */
    private function charsetWithConfig(array $values): ConcreteCharset
    {
        $captcha = new ConcreteCharset();
        $this->setConfig($captcha, $values);
        return $captcha;
    }

    // --- construction ---

    /**
     * 1) On construction, a random captcha content string is generated and
     * stored, using whatever charset/length the live environment happens to
     * have configured (just checking it's non-empty here - the exact
     * charset/length behaviour is covered deterministically by the other
     * tests below).
     */
    public function testConstructorSetsRandomCaptchaContent(): void
    {
        $captcha = new ConcreteCharset();

        $this->assertNotSame('', $captcha->exposeGetCaptchaContent());
    }

    // --- getCharset() ---

    /**
     * 2) getCharset() returns the configured character set string as-is.
     */
    public function testGetCharsetReturnsConfiguredValue(): void
    {
        $captcha = $this->charsetWithConfig(['input_captchaCharset' => 'ABCDEF']);

        $this->assertSame('ABCDEF', $captcha->exposeGetCharset());
    }

    // --- getNumberOfCharacters() ---

    /**
     * 3) REGRESSION TEST for the missing (int) cast: module config values
     * are stored as strings in the database, so the configured number of
     * characters is set here as a STRING ("6"), not an int. Before the fix,
     * returning that string from a method declared ": int" under
     * declare(strict_types=1) would have thrown a TypeError.
     */
    public function testGetNumberOfCharactersCastsConfigToInt(): void
    {
        $captcha = $this->charsetWithConfig(['input_captchaNumberOfCharacters' => '6']);

        $this->assertSame(6, $captcha->exposeGetNumberOfCharacters());
    }

    // --- createRandomString() ---

    /**
     * 4) The generated random string has exactly the configured length.
     */
    public function testCreateRandomStringHasConfiguredLength(): void
    {
        $captcha = $this->charsetWithConfig([
            'input_captchaCharset' => 'ABCDEFGHIJ',
            'input_captchaNumberOfCharacters' => '8',
        ]);

        $this->assertSame(8, strlen($captcha->exposeCreateRandomString()));
    }

    /**
     * 5) Every character in the generated random string comes from the
     * configured charset - not from anywhere else.
     */
    public function testCreateRandomStringOnlyUsesConfiguredCharset(): void
    {
        $captcha = $this->charsetWithConfig([
            'input_captchaCharset' => 'AB',
            'input_captchaNumberOfCharacters' => '20',
        ]);

        $result = $captcha->exposeCreateRandomString();

        $this->assertSame(20, strlen($result));
        for ($i = 0; $i < strlen($result); $i++) {
            $this->assertContains($result[$i], ['A', 'B']);
        }
    }

    /**
     * 6) With zero configured characters, the generated string is empty.
     */
    public function testCreateRandomStringWithZeroCharactersIsEmpty(): void
    {
        $captcha = $this->charsetWithConfig([
            'input_captchaCharset' => 'ABCDEF',
            'input_captchaNumberOfCharacters' => '0',
        ]);

        $this->assertSame('', $captcha->exposeCreateRandomString());
    }

    /**
     * 7) Repeated calls with a single-character charset always produce that
     * one character, repeated - a simple, fully deterministic sanity check
     * that doesn't depend on rand()'s actual output.
     */
    public function testCreateRandomStringWithSingleCharacterCharsetIsFullyDeterministic(): void
    {
        $captcha = $this->charsetWithConfig([
            'input_captchaCharset' => 'X',
            'input_captchaNumberOfCharacters' => '5',
        ]);

        $this->assertSame('XXXXX', $captcha->exposeCreateRandomString());
    }

    /**
     * 8) REGRESSION TEST for the fixed bug: an empty configured character
     * set used to cause rand(0, -1) to throw an unhelpful ValueError deep
     * inside the random-string generation. Now it throws a clear,
     * descriptive RuntimeException instead, surfacing the actual
     * misconfiguration (thrown during construction, since the constructor
     * itself calls createRandomString()).
     */
    public function testCreateRandomStringThrowsClearErrorForEmptyCharset(): void
    {
        $captcha = $this->charsetWithConfig([
            'input_captchaCharset' => 'ABCDEF',
            'input_captchaNumberOfCharacters' => '5',
        ]);
        $this->setConfig($captcha, ['input_captchaCharset' => '']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/character set is empty/');

        $captcha->exposeCreateRandomString();
    }
}
