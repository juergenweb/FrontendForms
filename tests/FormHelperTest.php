<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use Exception;
use FrontendForms\FormHelper;
use PHPUnit\Framework\TestCase;
use ProcessWire\Field;

/**
 * Unit tests for FormHelper.
 *
 * All expected outputs were confirmed by running the actual algorithms
 * standalone with the exact same inputs before writing the assertions.
 */
final class FormHelperTest extends TestCase
{
    // --- checkForPath() ---

    /**
     * 1) A filename that includes a directory path is recognized as having
     * a path.
     */
    public function testCheckForPathReturnsTrueForRealPath(): void
    {
        $this->assertTrue(FormHelper::checkForPath('templates/mail.html'));
    }

    /**
     * 2) A bare filename with no directory component is not recognized as
     * having a path.
     */
    public function testCheckForPathReturnsFalseForBareFilename(): void
    {
        $this->assertFalse(FormHelper::checkForPath('mail.html'));
    }

    // --- sanitizeValueToInt() ---

    /**
     * 3) A non-empty string sanitizes to 1, an empty string to 0.
     */
    public function testSanitizeValueToIntWithStrings(): void
    {
        $this->assertSame(1, FormHelper::sanitizeValueToInt('anything'));
        $this->assertSame(0, FormHelper::sanitizeValueToInt(''));
    }

    /**
     * 4) An integer >= 1 sanitizes to 1, anything less (including 0 and
     * negative numbers) sanitizes to 0.
     */
    public function testSanitizeValueToIntWithIntegers(): void
    {
        $this->assertSame(1, FormHelper::sanitizeValueToInt(5));
        $this->assertSame(1, FormHelper::sanitizeValueToInt(1));
        $this->assertSame(0, FormHelper::sanitizeValueToInt(0));
        $this->assertSame(0, FormHelper::sanitizeValueToInt(-3));
    }

    /**
     * 5) A boolean falls through to a plain (int) cast: true -> 1,
     * false -> 0.
     */
    public function testSanitizeValueToIntWithBooleans(): void
    {
        $this->assertSame(1, FormHelper::sanitizeValueToInt(true));
        $this->assertSame(0, FormHelper::sanitizeValueToInt(false));
    }

    // --- simplifyMultiFileArray() ---

    /**
     * 6) The nested $_FILES-style structure (one array per attribute, each
     * indexed by file position) is converted into one array per file.
     */
    public function testSimplifyMultiFileArrayRestructuresCorrectly(): void
    {
        $files = [
            'name' => ['a.txt', 'b.txt'],
            'type' => ['text/plain', 'text/plain'],
            'error' => [0, 0],
        ];

        $result = FormHelper::simplifyMultiFileArray($files);

        $this->assertSame([
            0 => ['name' => 'a.txt', 'type' => 'text/plain', 'error' => 0],
            1 => ['name' => 'b.txt', 'type' => 'text/plain', 'error' => 0],
        ], $result);
    }

    /**
     * 7) An empty array input produces an empty array output.
     */
    public function testSimplifyMultiFileArrayWithEmptyInput(): void
    {
        $this->assertSame([], FormHelper::simplifyMultiFileArray([]));
    }

    // --- putRequiredOnTop() ---

    /**
     * 8) A "required" rule is moved to the first position among multiple
     * rules.
     */
    public function testPutRequiredOnTopMovesRequiredFirst(): void
    {
        $rules = ['email' => 'a', 'required' => 'b'];

        $result = FormHelper::putRequiredOnTop($rules);

        $this->assertSame(['required' => 'b', 'email' => 'a'], $result);
    }

    /**
     * 9) When both "required" and "fileRequired" are present,
     * "fileRequired" takes priority for the first position.
     */
    public function testPutRequiredOnTopPrefersFileRequired(): void
    {
        $rules = ['required' => 'b', 'fileRequired' => 'c', 'other' => 'd'];

        $result = FormHelper::putRequiredOnTop($rules);

        $this->assertSame(['fileRequired' => 'c', 'required' => 'b', 'other' => 'd'], $result);
    }

    /**
     * 10) A single-rule array is left unchanged (nothing to reorder).
     */
    public function testPutRequiredOnTopLeavesSingleRuleUnchanged(): void
    {
        $rules = ['required' => 'b'];

        $this->assertSame(['required' => 'b'], FormHelper::putRequiredOnTop($rules));
    }

    /**
     * 11) Rules without a "required" key at all are left unchanged.
     */
    public function testPutRequiredOnTopLeavesRulesWithoutRequiredUnchanged(): void
    {
        $rules = ['email' => 'a', 'minLength' => 'b'];

        $this->assertSame($rules, FormHelper::putRequiredOnTop($rules));
    }

    // --- repositionArrayElement() ---

    /**
     * 12) An array element is moved from its original position to the
     * given new position.
     */
    public function testRepositionArrayElementMovesElement(): void
    {
        $array = ['a', 'b', 'c', 'd'];

        FormHelper::repositionArrayElement($array, 2, 0);

        $this->assertSame(['c', 'a', 'b', 'd'], $array);
    }

    /**
     * 13) A non-existent key throws an Exception.
     */
    public function testRepositionArrayElementThrowsForMissingKey(): void
    {
        $array = ['a', 'b', 'c'];

        $this->expectException(Exception::class);

        FormHelper::repositionArrayElement($array, 'not-a-key', 0);
    }

    // --- encryptDecrypt() ---

    /**
     * 14) A string encrypted and then decrypted round-trips back to the
     * original value.
     */
    public function testEncryptDecryptRoundTrips(): void
    {
        $original = 'a secret value 123';

        $encrypted = FormHelper::encryptDecrypt($original, 'encrypt');
        $decrypted = FormHelper::encryptDecrypt($encrypted, 'decrypt');

        $this->assertNotSame($original, $encrypted);
        $this->assertSame($original, $decrypted);
    }

    /**
     * 15) An unrecognized method name returns the input string unchanged.
     */
    public function testEncryptDecryptWithUnknownMethodReturnsInputUnchanged(): void
    {
        $this->assertSame('unchanged', FormHelper::encryptDecrypt('unchanged', 'not-a-real-method'));
    }

    // --- generateSlug() ---

    /**
     * 16) Spaces, punctuation and other non-alphanumeric characters are
     * replaced with a single hyphen each.
     */
    public function testGenerateSlugReplacesNonAlphanumericCharacters(): void
    {
        $this->assertSame('Hello-World-123', FormHelper::generateSlug('Hello World! 123'));
    }

    /**
     * 17) Accented/non-ASCII letters are also replaced (the allowed
     * character set is strictly A-Za-z0-9-).
     */
    public function testGenerateSlugReplacesNonAsciiCharacters(): void
    {
        $this->assertSame('h-llo-w-rld', FormHelper::generateSlug('héllo@wörld'));
    }

    /**
     * 18) A string that's already a valid slug is left unchanged.
     */
    public function testGenerateSlugLeavesValidSlugUnchanged(): void
    {
        $this->assertSame('already-a-slug123', FormHelper::generateSlug('already-a-slug123'));
    }

    // --- createQueryCode() ---

    /**
     * 19) The generated code is a non-empty, slug-safe string (only
     * A-Za-z0-9- characters, matching what generateSlug() would produce).
     */
    public function testCreateQueryCodeProducesSlugSafeString(): void
    {
        $code = FormHelper::createQueryCode();

        $this->assertNotSame('', $code);
        $this->assertMatchesRegularExpression('/^[A-Za-z\d-]+$/', $code);
    }

    /**
     * 20) A non-positive length falls back to a length of 10 characters
     * worth of random data (checked indirectly via a non-empty result,
     * since the exact output length after slug conversion isn't fixed to
     * the input character count).
     */
    public function testCreateQueryCodeWithNonPositiveLengthStillProducesOutput(): void
    {
        $code = FormHelper::createQueryCode(0);

        $this->assertNotSame('', $code);
    }

    /**
     * 21) Two consecutively generated codes are different from each other
     * (confirms genuine randomness, not a fixed/cached value).
     */
    public function testCreateQueryCodeProducesDifferentValuesEachCall(): void
    {
        $this->assertNotSame(FormHelper::createQueryCode(), FormHelper::createQueryCode());
    }

    // --- getSeoMaestro() ---

    /**
     * 22) getSeoMaestro() returns either null (SeoMaestro not installed) or
     * a Field instance (SeoMaestro installed with a configured field) -
     * whichever the live test environment actually has, since this can't
     * be controlled from a unit test.
     */
    public function testGetSeoMaestroReturnsNullOrField(): void
    {
        $result = FormHelper::getSeoMaestro();

        $this->assertTrue($result === null || $result instanceof Field);
    }
}
