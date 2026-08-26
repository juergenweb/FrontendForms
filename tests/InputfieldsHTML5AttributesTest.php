<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputEmail;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the addHTML5{validator}() methods in Inputfields.php -
 * the client-side HTML5 attributes (pattern, minlength, maxlength, ...)
 * that setRule() applies automatically for each matching validation rule.
 *
 * A code-quality issue was found and fixed in exactly half of the
 * pattern-setting addHTML5*() methods: they called setAttribute() with a
 * trailing space in the attribute name literal itself (e.g. "pattern "
 * instead of "pattern"). This turned out to be functionally harmless -
 * Tag::setAttribute() runs every key through sanitizeAttributeName()
 * (trim + lowercase) before storing it, so "pattern " and "pattern" were
 * always stored under the same, correct key either way - but the tests
 * below still guard directly against this class of typo, since relying
 * on that internal normalization to silently paper over an incorrect
 * literal is fragile, not a design to depend on.
 */
final class InputfieldsHTML5AttributesTest extends TestCase
{
    // --- pattern-attribute validators ---

    /**
     * 1) "firstAndLastname" sets a "pattern" attribute.
     */
    public function testFirstAndLastnameSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('firstAndLastname');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 2) "ascii" sets a "pattern" attribute.
     */
    public function testAsciiSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('ascii');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 3) "slug" sets a "pattern" attribute.
     */
    public function testSlugSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('slug');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 4) "url" sets a "pattern" attribute (on a plain InputText - the
     * dedicated InputUrl field type is deliberately skipped by
     * addHTML5url() since it already uses the browser's native url type).
     */
    public function testUrlSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('url');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 5) "email" sets a "pattern" attribute (on a plain InputText - the
     * dedicated InputEmail field type is deliberately skipped, see
     * testEmailSkipsPatternOnDedicatedEmailField()).
     */
    public function testEmailSetsPatternAttributeOnPlainTextField(): void
    {
        $field = new InputText('name');
        $field->setRule('email');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 6) "email" does NOT set a "pattern" attribute on the dedicated
     * InputEmail field type, which already uses the browser's native
     * email input type for validation.
     */
    public function testEmailSkipsPatternOnDedicatedEmailField(): void
    {
        $field = new InputEmail('email');
        $field->setRule('email');

        $this->assertNull($field->getAttribute('pattern'));
    }

    /**
     * 7) "numeric" sets a "pattern" attribute.
     */
    public function testNumericSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('numeric');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 8) "ip" sets a "pattern" attribute.
     */
    public function testIpSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('ip');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 9) "ipv4" sets a "pattern" attribute.
     */
    public function testIpv4SetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('ipv4');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 10) "ipv6" sets a "pattern" attribute.
     */
    public function testIpv6SetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('ipv6');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 11) "usernameSyntax" sets a "pattern" attribute.
     */
    public function testUsernameSyntaxSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('usernameSyntax');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 12) "regex" sets a "pattern" attribute, derived from the given raw
     * PHP-style regex.
     */
    public function testRegexSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('regex', '/^[a-z]+$/i');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 13) "exactValue" sets a "pattern" attribute.
     */
    public function testExactValueSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('exactValue', 'expected');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 14) "dateFormat" sets a "pattern" attribute for a recognized format.
     */
    public function testDateFormatSetsPatternAttribute(): void
    {
        $field = new InputText('name');
        $field->setRule('dateFormat', 'dd.mm.yyyy');

        $this->assertNotNull($field->getAttribute('pattern'));
    }

    /**
     * 15) "lengthBetween" sets both "minlength" AND "maxlength" attributes
     * (this rule sets two attributes in one call, both of which had the
     * trailing-space typo).
     */
    public function testLengthBetweenSetsMinlengthAndMaxlengthAttributes(): void
    {
        $field = new InputText('name');
        $field->setRule('lengthBetween', 5, 10);

        $this->assertNotNull($field->getAttribute('minlength'));
        $this->assertNotNull($field->getAttribute('maxlength'));
    }

    // --- spot-check: the underlying regex is not just non-empty, but correct ---

    /**
     * 16) The "pattern" attribute set by "numeric" actually matches a
     * plausible numeric value and rejects a non-numeric one, confirming
     * the regex content itself (not just its presence) is correct - a
     * non-empty-but-wrong pattern would pass tests 1-14 above without
     * this additional check.
     */
    public function testNumericPatternAttributeMatchesExpectedValues(): void
    {
        $field = new InputText('name');
        $field->setRule('numeric');

        $pattern = '/^' . $field->getAttribute('pattern') . '$/';

        $this->assertSame(1, preg_match($pattern, '12345'));
        $this->assertSame(0, preg_match($pattern, 'abc'));
    }

    // --- addAriaAttributes() ---

    /**
     * 17) REGRESSION TEST: when a field has both a description AND notes
     * text, aria-describedby references BOTH of their ids, not just the
     * last one set. Previously, setting aria-describedby a second time
     * (for notes) silently overwrote the first value (for the
     * description) instead of combining them, so a screen reader user
     * was never told about the description at all.
     *
     * Note: "aria-describedby" is listed in Tag::MULTIVALUEATTR, so
     * getAttribute() returns an array of the individual id strings here,
     * not a single space-separated string.
     */
    public function testAriaDescribedByCombinesDescriptionAndNotesIds(): void
    {
        $field = new InputText('email');
        $field->setDescription('Enter your email address');
        $field->setNotes('We will never share it');

        $field->render();

        $describedBy = $field->getAttribute('aria-describedby');
        $this->assertIsArray($describedBy);
        $this->assertContains($field->getID() . '-desc', $describedBy);
        $this->assertContains($field->getID() . '-notes', $describedBy);
    }

    /**
     * 18) With only a description (no notes), aria-describedby points
     * solely at the description id.
     */
    public function testAriaDescribedByPointsOnlyAtDescriptionWhenNoNotes(): void
    {
        $field = new InputText('email');
        $field->setDescription('Enter your email address');

        $field->render();

        $this->assertSame([$field->getID() . '-desc'], $field->getAttribute('aria-describedby'));
    }

    /**
     * 19) With only notes (no description), aria-describedby points
     * solely at the notes id.
     */
    public function testAriaDescribedByPointsOnlyAtNotesWhenNoDescription(): void
    {
        $field = new InputText('email');
        $field->setNotes('We will never share it');

        $field->render();

        $this->assertSame([$field->getID() . '-notes'], $field->getAttribute('aria-describedby'));
    }

    /**
     * 20) With neither a description nor notes, no aria-describedby
     * attribute is set at all.
     */
    public function testAriaDescribedByNotSetWithoutDescriptionOrNotes(): void
    {
        $field = new InputText('email');

        $field->render();

        $this->assertNull($field->getAttribute('aria-describedby'));
    }

    /**
     * 21) Disabling ARIA attributes via useAriaAttributes(false) suppresses
     * aria-describedby entirely, even when both a description and notes
     * are present. Previously, a second, unguarded copy of this logic
     * directly inside ___render() bypassed this setting, meaning
     * aria-describedby was still added regardless of it.
     */
    public function testAriaDescribedByNotSetWhenAriaAttributesDisabled(): void
    {
        $field = new InputText('email');
        $field->setDescription('Enter your email address');
        $field->setNotes('We will never share it');
        $field->useAriaAttributes(false);

        $field->render();

        $this->assertNull($field->getAttribute('aria-describedby'));
    }
}
