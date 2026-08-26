<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\SelectMultiple;
use FrontendForms\Tag;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * A minimal concrete Tag subclass for testing. Tag is declared abstract
 * only to prevent direct instantiation - it has no actual abstract methods,
 * so an empty subclass is enough to exercise all of its own logic in
 * isolation, without pulling in Element/Inputfields' extra state (labels,
 * wrappers, sanitizers, ...).
 *
 * Public wrapper methods expose the protected methods under test - PHPUnit
 * test classes aren't subclasses of Tag, so they can't call protected
 * methods directly.
 */
final class ConcreteTag extends Tag
{
    public function exposeAttributesToString(bool $selfClosing = true): string
    {
        return $this->attributesToString($selfClosing);
    }

    public function exposeIsAssoc(array $array): bool
    {
        return $this->isAssoc($array);
    }

    public function exposeSetCSSClass(string $className): self
    {
        return $this->setCSSClass($className);
    }

    public function exposeRenderSelfclosingTag(string $tag): string
    {
        return $this->renderSelfclosingTag($tag);
    }

    public function exposeRenderNonSelfclosingTag(string $tag, bool $showNoContent = false, bool $showAttributeValue = false): string
    {
        return $this->renderNonSelfclosingTag($tag, $showNoContent, $showAttributeValue);
    }

    public function exposeSanitizeAttributeName(string $name): string
    {
        return $this->sanitizeAttributeName($name);
    }
}

/**
 * Unit tests for Tag.
 *
 * Uses the real ProcessWire bootstrap (see tests/bootstrap.php) via a real,
 * minimal concrete subclass (ConcreteTag, defined above) rather than a mock -
 * consistent with the lessons learned earlier in this session about
 * ProcessWire core object mocking being unreliable (see
 * CaptchaQuestionRepositoryTest, DefaultInputRendererTest).
 *
 * All expected values for the more intricate attribute-parsing logic were
 * confirmed by running the actual algorithm standalone before writing the
 * assertions.
 */
final class TagTest extends TestCase
{
    private function tag(): ConcreteTag
    {
        return new ConcreteTag();
    }

    // --- basic attribute get/set/remove ---

    /**
     * 1) A single-value attribute can be set and read back.
     */
    public function testSetAndGetSingleValueAttribute(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('id', 'my-id');

        $this->assertSame('my-id', $tag->getAttribute('id'));
    }

    /**
     * 2) Reading an attribute that was never set returns null.
     */
    public function testGetUnknownAttributeReturnsNull(): void
    {
        $this->assertNull($this->tag()->getAttribute('href'));
    }

    /**
     * 3) Attribute names are normalized (trimmed, lower-cased), so lookups
     * work regardless of the case/whitespace used when setting them.
     */
    public function testAttributeNameIsCaseInsensitiveAndTrimmed(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('  ID  ', 'my-id');

        $this->assertSame('my-id', $tag->getAttribute('id'));
        $this->assertSame('my-id', $tag->getAttribute('ID'));
    }

    /**
     * 4) hasAttribute() correctly reports presence/absence.
     */
    public function testHasAttribute(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('id', 'my-id');

        $this->assertTrue($tag->hasAttribute('id'));
        $this->assertFalse($tag->hasAttribute('href'));
    }

    /**
     * 5) removeAttribute() removes the whole attribute.
     */
    public function testRemoveAttribute(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('id', 'my-id');
        $tag->removeAttribute('id');

        $this->assertNull($tag->getAttribute('id'));
        $this->assertFalse($tag->hasAttribute('id'));
    }

    // --- multi-value attributes (class, rel, style, aria-describedby) ---

    /**
     * 6) Setting a multi-value attribute (e.g. "class") twice ADDS to the
     * existing values rather than overwriting them.
     */
    public function testMultiValueAttributeAccumulatesAcrossCalls(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo');
        $tag->setAttribute('class', 'bar');

        $this->assertSame(['foo', 'bar'], array_values($tag->getAttribute('class')));
    }

    /**
     * 7) A single call with a space-separated string is split into
     * individual values.
     */
    public function testMultiValueAttributeSplitsSpaceSeparatedString(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo bar');

        $this->assertSame(['foo', 'bar'], array_values($tag->getAttribute('class')));
    }

    /**
     * 8) Duplicate values are not added twice.
     */
    public function testMultiValueAttributeDoesNotDuplicateValues(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo');
        $tag->setAttribute('class', 'foo');

        $this->assertSame(['foo'], array_values($tag->getAttribute('class')));
    }

    // --- boolean attributes ---

    /**
     * 9) Setting a known boolean attribute (e.g. "required") with no value
     * stores it using its own name as the value.
     */
    public function testBooleanAttributeIsSetWithItsOwnNameAsValue(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('required');

        $this->assertSame('required', $tag->getAttribute('required'));
    }

    /**
     * 10) An UIkit-prefixed attribute ("uk-..." or "data-uk-...") is also
     * treated as a boolean attribute, even though it's not in the fixed
     * BOOLEANATTR list.
     */
    public function testUikitPrefixedAttributeIsTreatedAsBoolean(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('data-uk-form-custom');
        $tag->setAttribute('uk-grid');

        $this->assertSame('data-uk-form-custom', $tag->getAttribute('data-uk-form-custom'));
        $this->assertSame('uk-grid', $tag->getAttribute('uk-grid'));
    }

    /**
     * 11) An arbitrary, unrecognized attribute name with no value is simply
     * ignored (not a boolean attribute, nothing to store).
     */
    public function testUnknownAttributeWithNoValueIsIgnored(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('foo');

        $this->assertNull($tag->getAttribute('foo'));
    }

    // --- removeAttributeValue() - regression tests for the trim-consistency fix ---

    /**
     * 12) Removing one value from a multi-value attribute leaves the others
     * intact.
     */
    public function testRemoveAttributeValueRemovesOnlyThatValue(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo bar baz');
        $tag->removeAttributeValue('class', 'bar');

        $this->assertSame(['foo', 'baz'], array_values($tag->getAttribute('class')));
    }

    /**
     * 13) Removing the last remaining value of a multi-value attribute
     * removes the whole attribute, not just an empty array.
     */
    public function testRemoveAttributeValueRemovesWholeAttributeWhenLastValueRemoved(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo');
        $tag->removeAttributeValue('class', 'foo');

        $this->assertNull($tag->getAttribute('class'));
    }

    /**
     * 14) REGRESSION TEST for the trim-consistency bug: a value passed with
     * surrounding whitespace is still correctly matched and removed (before
     * the fix, the existence check used the untrimmed value while the
     * removal used the trimmed one, which could silently fail to remove
     * anything for a whitespace-padded value).
     */
    public function testRemoveAttributeValueWorksWithSurroundingWhitespace(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo bar');
        $tag->removeAttributeValue('class', '  bar  ');

        $this->assertSame(['foo'], array_values($tag->getAttribute('class')));
    }

    /**
     * 15) Removing a value that isn't present is a harmless no-op.
     */
    public function testRemoveAttributeValueForAbsentValueIsNoOp(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo');
        $tag->removeAttributeValue('class', 'does-not-exist');

        $this->assertSame(['foo'], array_values($tag->getAttribute('class')));
    }

    // --- strpos() regression test (semicolon-separated style parsing) ---

    /**
     * 16) REGRESSION TEST: a semicolon-separated "style"-like value is
     * parsed into an associative array of property => value pairs, even
     * when the FIRST character is a semicolon (position 0). Before the fix,
     * this used "if (strpos($value, ';'))", and strpos() returning 0 (a
     * valid, falsy position) meant a leading semicolon was treated as "no
     * semicolon found", so the value was never parsed into an assoc array
     * at all. Only SelectMultiple/InputCheckboxMultiple (Tag::MULTIVALCLASSES)
     * take this code path, hence the real field class here instead of
     * ConcreteTag.
     */
    public function testSemicolonSeparatedStyleIsParsedEvenWithLeadingSemicolon(): void
    {
        $field = new SelectMultiple('myselect');
        $field->setAttribute('style', ';color:red;font-weight:bold');

        $this->assertSame(
            ['color' => 'red', 'font-weight' => 'bold'],
            $field->getAttribute('style')
        );
    }

    /**
     * 17) The same parsing also works for a semicolon NOT at the start,
     * confirming the fix didn't just move the bug elsewhere.
     */
    public function testSemicolonSeparatedStyleIsParsedWithoutLeadingSemicolon(): void
    {
        $field = new SelectMultiple('myselect');
        $field->setAttribute('style', 'color:red;font-weight:bold');

        $this->assertSame(
            ['color' => 'red', 'font-weight' => 'bold'],
            $field->getAttribute('style')
        );
    }

    // --- content ---

    /**
     * 18) Content can be set and read back.
     */
    public function testSetAndGetContent(): void
    {
        $tag = $this->tag();
        $tag->setContent('Hello world');

        $this->assertSame('Hello world', $tag->getContent());
    }

    /**
     * 19) Setting content to null is a no-op - it does NOT clear
     * previously set content (setContent() explicitly guards against null).
     */
    public function testSetContentWithNullDoesNotClearExistingContent(): void
    {
        $tag = $this->tag();
        $tag->setContent('Hello world');
        $tag->setContent(null);

        $this->assertSame('Hello world', $tag->getContent());
    }

    // --- tag name ---

    /**
     * 20) The tag name can be set and is normalized (trimmed, lower-cased).
     */
    public function testSetAndGetTag(): void
    {
        $tag = $this->tag();
        $tag->setTag('  DIV  ');

        $this->assertSame('div', $tag->getTag());
    }

    // --- prepend / append ---

    /**
     * 21) prepend()/append() markup surrounds the rendered self-closing tag.
     */
    public function testPrependAndAppendSurroundSelfClosingTag(): void
    {
        $tag = $this->tag();
        $tag->prepend('<div class="wrap">')->append('</div>');

        $out = $tag->exposeRenderSelfclosingTag('input');

        $this->assertSame('<div class="wrap"><input></div>', $out);
    }

    /**
     * 22) removePrepend()/removeAppend() clear the previously set markup.
     */
    public function testRemovePrependAndRemoveAppend(): void
    {
        $tag = $this->tag();
        $tag->prepend('<div>')->append('</div>');
        $tag->removePrepend();
        $tag->removeAppend();

        $this->assertSame('<input>', $tag->exposeRenderSelfclosingTag('input'));
    }

    // --- attributesToString() ---

    /**
     * 23) A boolean attribute is rendered as its bare name, without
     * ="value".
     */
    public function testAttributesToStringRendersBooleanAttributeBare(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('required');

        $this->assertSame(' required', $tag->exposeAttributesToString());
    }

    /**
     * 24) A regular attribute is rendered as name="value".
     */
    public function testAttributesToStringRendersRegularAttributeWithValue(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('id', 'my-id');

        $this->assertSame(' id="my-id"', $tag->exposeAttributesToString());
    }

    /**
     * 25) A multi-value attribute (e.g. "class") is rendered with its
     * values joined by a single space.
     */
    public function testAttributesToStringJoinsMultiValueAttributeWithSpaces(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('class', 'foo bar');

        $this->assertSame(' class="foo bar"', $tag->exposeAttributesToString());
    }

    /**
     * 25b) REGRESSION TEST for the fixed bug: a regular (non-boolean)
     * attribute whose VALUE happens to coincide with a boolean attribute
     * NAME (e.g. a custom data attribute set to "required") must still be
     * rendered as name="value" - not collapsed into the bare boolean form.
     * The check must be based on the attribute's name, not its value.
     */
    public function testAttributesToStringDoesNotTreatMatchingValueAsBoolean(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('data-status', 'required');

        $this->assertSame(' data-status="required"', $tag->exposeAttributesToString());
    }

    /**
     * 25c) REGRESSION TEST for the fixed bug: type="hidden" must render
     * with its full name="value" form, not collapse into the bare word
     * "hidden" just because "hidden" also happens to be a boolean
     * attribute name.
     */
    public function testAttributesToStringRendersTypeHiddenWithValue(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('type', 'hidden');

        $this->assertSame(' type="hidden"', $tag->exposeAttributesToString());
    }

    // --- isAssoc() ---

    /**
     * 26) isAssoc() correctly distinguishes associative from plain
     * (numerically indexed) arrays.
     */
    public function testIsAssocDistinguishesArrayTypes(): void
    {
        $tag = $this->tag();

        $this->assertTrue($tag->exposeIsAssoc(['color' => 'red']));
        $this->assertFalse($tag->exposeIsAssoc(['red', 'blue']));
        $this->assertFalse($tag->exposeIsAssoc([]));
    }

    // --- renderNonSelfclosingTag() ---

    /**
     * 27) With showNoContent = false (the default) and no content set, an
     * empty string is returned - the tag isn't rendered at all.
     */
    public function testRenderNonSelfclosingTagWithNoContentAndShowNoContentFalse(): void
    {
        $tag = $this->tag();

        $this->assertSame('', $tag->exposeRenderNonSelfclosingTag('div'));
    }

    /**
     * 28) With showNoContent = true, the tag is rendered even without
     * content, as an empty element.
     */
    public function testRenderNonSelfclosingTagWithNoContentAndShowNoContentTrue(): void
    {
        $tag = $this->tag();

        $this->assertSame('<div></div>', $tag->exposeRenderNonSelfclosingTag('div', true));
    }

    /**
     * 29) With content set, the tag is rendered with that content, wrapped
     * by the opening/closing tag.
     */
    public function testRenderNonSelfclosingTagWithContent(): void
    {
        $tag = $this->tag();
        $tag->setContent('Hello');

        $this->assertSame('<div>Hello</div>', $tag->exposeRenderNonSelfclosingTag('div'));
    }

    // --- flattenMixedArray() / flattenArray() ---

    /**
     * 30) flattenMixedArray() joins array values with a comma, leaving
     * scalar values untouched.
     */
    public function testFlattenMixedArrayJoinsArrayValuesWithComma(): void
    {
        $tag = $this->tag();

        $this->assertSame(
            ['a' => 'scalar', 'b' => '1,2,3'],
            $tag->flattenMixedArray(['a' => 'scalar', 'b' => [1, 2, 3]])
        );
    }

    /**
     * 31) flattenArray() recursively flattens a nested array into a single
     * flat, numerically indexed list of all leaf values.
     */
    public function testFlattenArrayFlattensNestedArrays(): void
    {
        $tag = $this->tag();

        $this->assertSame(
            ['a', 'b', 'c'],
            $tag->flattenArray(['a', ['b', ['c']]])
        );
    }

    // --- attributesToString() HTML escaping ---

    /**
     * 32) REGRESSION TEST for the XSS fix: an attribute value containing
     * HTML-significant characters (quotes, angle brackets) must be escaped
     * so it cannot break out of the surrounding double-quoted attribute and
     * inject markup - e.g. a form re-displaying a submitted value after a
     * failed validation. Before the fix, attributesToString() concatenated
     * the raw value directly into the attribute string with no escaping at
     * all, confirmed standalone before writing this assertion:
     *   $name . '="' . $value . '"'   // no htmlspecialchars() anywhere
     * A double quote in the value would have terminated the attribute
     * early, and everything after it (e.g. an onmouseover handler or a
     * <script> tag) would have been rendered as real markup.
     */
    public function testAttributesToStringEscapesHtmlSpecialCharacters(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('value', '"><script>alert(1)</script>');

        $out = $tag->exposeAttributesToString();

        $this->assertStringNotContainsString('<script>', $out);
        $this->assertStringContainsString(
            'value="&quot;&gt;&lt;script&gt;alert(1)&lt;/script&gt;"',
            $out
        );
    }

    /**
     * 33) An ampersand in an attribute value is escaped to "&amp;" so it
     * isn't misinterpreted as the start of an HTML entity.
     */
    public function testAttributesToStringEscapesAmpersand(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('value', 'Tom & Jerry');

        $this->assertSame(' value="Tom &amp; Jerry"', $tag->exposeAttributesToString());
    }

    /**
     * 34) A plain value with no special characters renders exactly as
     * before - the escaping fix doesn't visibly change ordinary values.
     */
    public function testAttributesToStringLeavesPlainValuesUnchanged(): void
    {
        $tag = $this->tag();
        $tag->setAttribute('value', 'Max Mustermann');

        $this->assertSame(' value="Max Mustermann"', $tag->exposeAttributesToString());
    }

    // --- getCSSClass() ---

    private function setClasses(object $tag, object $classes): void
    {
        $prop = new ReflectionProperty($tag, 'classes');
        $prop->setAccessible(true);
        $prop->setValue($tag, $classes);
    }

    private function setFrontendforms(object $tag, array $values): void
    {
        $prop = new ReflectionProperty($tag, 'frontendforms');
        $prop->setAccessible(true);
        $current = $prop->getValue($tag);
        foreach ($values as $key => $value) {
            $current[$key] = $value;
        }
        $prop->setValue($tag, $current);
    }

    /**
     * 35) Without any backend override configured, getCSSClass() returns
     * the framework's default class value.
     */
    public function testGetCSSClassReturnsFrameworkDefaultWithoutOverride(): void
    {
        $tag = $this->tag();
        $this->setClasses($tag, (object)['inputClass' => 'uk-input']);

        $this->assertSame('uk-input', $tag->getCSSClass('inputClass'));
    }

    /**
     * 36) REGRESSION TEST for the fixed bug: a custom class name
     * configured in the backend module settings (under the
     * "input_" + className key) is now correctly used instead of the
     * framework's default. Before the fix, the override was checked
     * and/or read from a plain object property that never actually
     * existed, so the configured override was silently ignored and the
     * framework default was always used instead.
     */
    public function testGetCSSClassUsesBackendOverrideWhenConfigured(): void
    {
        $tag = $this->tag();
        $this->setClasses($tag, (object)['inputClass' => 'uk-input']);
        $this->setFrontendforms($tag, ['input_inputClass' => 'my-custom-input-class']);

        $this->assertSame('my-custom-input-class', $tag->getCSSClass('inputClass'));
    }

    /**
     * 37) An unknown class name (not present on the classes object at
     * all) returns null, regardless of any backend configuration.
     */
    public function testGetCSSClassReturnsNullForUnknownClassName(): void
    {
        $tag = $this->tag();
        $this->setClasses($tag, (object)['inputClass' => 'uk-input']);

        $this->assertNull($tag->getCSSClass('someUnknownClass'));
    }

    // --- setCSSClass() ---

    /**
     * 38) Without any backend override, setCSSClass() adds the framework
     * default class to the "class" attribute, alongside anything already
     * present there.
     */
    public function testSetCSSClassAddsFrameworkDefaultAlongsideExistingClasses(): void
    {
        $tag = $this->tag();
        $this->setClasses($tag, (object)['inputClass' => 'uk-input']);
        $tag->setAttribute('class', 'my-own-class');

        $tag->exposeSetCSSClass('inputClass');

        $classAttr = $tag->getAttribute('class');
        $this->assertContains('my-own-class', $classAttr);
        $this->assertContains('uk-input', $classAttr);
    }

    /**
     * 39) REGRESSION TEST for the fixed bug: when a backend override class
     * is configured, setCSSClass() now REPLACES whatever was already set
     * on the "class" attribute, instead of merging the override in
     * alongside it. Before the fix, setAttribute()'s merge behavior for
     * multi-value attributes like "class" meant the override was just
     * appended next to the old class(es), rather than replacing them.
     */
    public function testSetCSSClassReplacesExistingClassesWhenOverrideConfigured(): void
    {
        $tag = $this->tag();
        $this->setClasses($tag, (object)['inputClass' => 'uk-input']);
        $this->setFrontendforms($tag, ['input_inputClass' => 'my-custom-input-class']);
        $tag->setAttribute('class', 'uk-input');

        $tag->exposeSetCSSClass('inputClass');

        $this->assertSame(['my-custom-input-class'], $tag->getAttribute('class'));
    }
}