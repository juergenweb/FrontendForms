<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\MailPlaceholderRegistry;
use PHPUnit\Framework\TestCase;

use function ProcessWire\wire;

/**
 * Unit tests for MailPlaceholderRegistry.
 *
 * Mostly a pure data container with no ProcessWire dependency - covers
 * name normalization, the two different array-flattening code paths
 * (simple list vs. multi-dimensional $_FILES-style array), and remove(),
 * which was found to be silently broken in the original Form.php
 * implementation this class replaced (unset() was called on a copy
 * returned by a getter, so nothing was ever actually removed) - see
 * testRemoveActuallyRemoves().
 *
 * The allSanitized() tests below are the one exception: they do depend
 * on ProcessWire (via wire('modules')->isInstalled('MarkupHTMLPurifier'))
 * to determine which of allSanitized()'s two code paths - HTML Purifier
 * or the htmlspecialchars() fallback - is active on the test site, and
 * skip themselves accordingly rather than assuming either one.
 */
final class MailPlaceholderRegistryTest extends TestCase
{
    /**
     * 1) A value set for a name can be read back under that same name.
     */
    public function testSetAndGetRoundTrip(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('title', 'Contact form');

        $this->assertSame('Contact form', $registry->get('title'));
    }

    /**
     * 2) Placeholder names are normalized to upper case and trimmed, so
     * lookups work regardless of the case/whitespace used when setting them.
     */
    public function testNameIsNormalizedToUpperCaseAndTrimmed(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('  body  ', 'Hello world');

        $this->assertSame('Hello world', $registry->get('BODY'));
        $this->assertSame('Hello world', $registry->get('body'));
    }

    /**
     * 3) String values are trimmed before being stored.
     */
    public function testValueIsTrimmed(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('name', '  John Doe  ');

        $this->assertSame('John Doe', $registry->get('name'));
    }

    /**
     * 4) Getting a placeholder that was never set returns an empty string,
     * not null and not a missing-key error.
     */
    public function testGetUnknownPlaceholderReturnsEmptyString(): void
    {
        $registry = new MailPlaceholderRegistry();

        $this->assertSame('', $registry->get('unknown'));
    }

    /**
     * 5) Setting a placeholder to null is a no-op - it must not create an
     * (empty) entry.
     */
    public function testSetWithNullValueIsNoOp(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('optional', null);

        $this->assertSame('', $registry->get('optional'));
        $this->assertSame([], $registry->all());
    }

    /**
     * 6) A simple (one-dimensional) array value is joined with ", " (comma
     * + space).
     */
    public function testSetWithFlatArrayJoinsWithCommaAndSpace(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('tags', ['red', 'green', 'blue']);

        $this->assertSame('red, green, blue', $registry->get('tags'));
    }

    /**
     * 7) A multi-dimensional array in $_FILES shape (each entry has a
     * "name" key, among others) is reduced to just the file names, joined
     * with "," (comma, no space) - a different separator than the flat-array
     * case in testSetWithFlatArrayJoinsWithCommaAndSpace().
     */
    public function testSetWithFilesStyleArrayJoinsFileNamesWithComma(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('attachments', [
            ['name' => 'invoice.pdf', 'tmp_name' => '/tmp/php1'],
            ['name' => 'photo.jpg', 'tmp_name' => '/tmp/php2'],
        ]);

        $this->assertSame('invoice.pdf,photo.jpg', $registry->get('attachments'));
    }

    /**
     * 8) Empty file names inside a multi-dimensional $_FILES-style array are
     * filtered out (array_filter), so an empty upload slot doesn't leave a
     * stray comma in the resulting string.
     */
    public function testSetWithFilesStyleArrayFiltersEmptyFileNames(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->set('attachments', [
            ['name' => 'invoice.pdf'],
            ['name' => ''],
        ]);

        $this->assertSame('invoice.pdf', $registry->get('attachments'));
    }

    /**
     * 9) remove() actually removes a previously set placeholder - a
     * regression guard for the bug found in the original Form.php
     * implementation, where removePlaceholder() called unset() on the
     * return value of a getter (a copy of the array), so the placeholder
     * was never actually deleted.
     */
    public function testRemoveActuallyRemoves(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('title', 'Contact form');

        $registry->remove('title');

        $this->assertSame('', $registry->get('title'));
        $this->assertArrayNotHasKey('TITLE', $registry->all());
    }

    /**
     * 10) Removing a placeholder that was never set is a harmless no-op.
     */
    public function testRemoveUnknownPlaceholderIsNoOp(): void
    {
        $registry = new MailPlaceholderRegistry();

        $registry->remove('does-not-exist');

        $this->assertSame([], $registry->all());
    }

    /**
     * 11) all() returns every placeholder that was set, keyed by its
     * normalized (upper-case) name.
     */
    public function testAllReturnsEveryPlaceholderByNormalizedName(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('title', 'Contact form');
        $registry->set('body', 'Hello world');

        $this->assertSame(
            ['TITLE' => 'Contact form', 'BODY' => 'Hello world'],
            $registry->all()
        );
    }

    /**
     * 12) Setting a placeholder under a name that was already used
     * overwrites the previous value rather than adding a second entry.
     */
    public function testSetOverwritesPreviousValueForSameName(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('title', 'First value');
        $registry->set('TITLE', 'Second value');

        $this->assertSame('Second value', $registry->get('title'));
        $this->assertCount(1, $registry->all());
    }

    /**
     * 13) REGRESSION TEST for the fixed bug: get() now trims the given
     * name before looking it up, matching set()/remove()'s own trimming -
     * previously, a name with surrounding whitespace would never match a
     * value stored under its trimmed form.
     */
    public function testGetTrimsNameBeforeLookup(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set(' title ', 'My Title');

        $this->assertSame('My Title', $registry->get(' title '));
        $this->assertSame('My Title', $registry->get('title'));
    }

    // --- allSanitized() ---
    //
    // allSanitized() replaced the earlier allEscaped() (plain
    // htmlspecialchars()-only escaping): it now runs values through
    // ProcessWire's MarkupHTMLPurifier module if installed, falling back
    // to htmlspecialchars() only if that module is unavailable. This
    // matters because a placeholder legitimately containing HTML - e.g.
    // "verificationlink", a ready-made <a href="...">...</a> link the
    // module itself builds for account-activation emails - was silently
    // broken by the original, escape-everything implementation: the
    // link rendered as visible "&lt;a href=...&gt;" text instead of an
    // actual clickable link. See testAllSanitizedPreservesSafeLinks().

    /**
     * 14) REGRESSION TEST for the verification-link bug: a placeholder
     * value containing a well-formed, safe <a href="...">...</a> link
     * (as the module itself builds for account-activation/verification
     * emails) survives allSanitized() intact, so it still renders as an
     * actual clickable link rather than visible, escaped tag text.
     *
     * Skipped if MarkupHTMLPurifier is not installed on the test site,
     * since only that path preserves safe HTML - see
     * testAllSanitizedFallsBackToEscapingWithoutPurifier() for the
     * other, HTML-purifier-unavailable case.
     */
    public function testAllSanitizedPreservesSafeLinks(): void
    {
        if (!wire('modules')->isInstalled('MarkupHTMLPurifier')) {
            $this->markTestSkipped('MarkupHTMLPurifier is not installed on this test site.');
        }

        $registry = new MailPlaceholderRegistry();
        $registry->set('verificationlink', '<a href="https://example.com/activate?code=abc123">Activate my account</a>');

        $this->assertSame(
            '<a href="https://example.com/activate?code=abc123">Activate my account</a>',
            $registry->allSanitized()['VERIFICATIONLINK']
        );
    }

    /**
     * 15) SECURITY TEST: a <script> tag in a placeholder value is always
     * neutralized by allSanitized() - true regardless of whether
     * MarkupHTMLPurifier is installed (it strips the tag entirely) or
     * not (the htmlspecialchars() fallback escapes it into inert text).
     * Either way, the literal string "<script>" must never appear in
     * the sanitized output.
     */
    public function testAllSanitizedNeutralizesScriptTags(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('commentvalue', '<script>alert(1)</script>');

        $this->assertStringNotContainsString('<script>', $registry->allSanitized()['COMMENTVALUE']);
    }

    /**
     * 16) SECURITY TEST: a dangerous event-handler attribute (onclick)
     * on an otherwise-safe link is always stripped by allSanitized(),
     * whether via MarkupHTMLPurifier's attribute filtering or the
     * htmlspecialchars() fallback (which neutralizes the whole tag).
     */
    public function testAllSanitizedStripsEventHandlerAttributes(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('link', '<a href="https://example.com" onclick="alert(1)">Click me</a>');

        $this->assertStringNotContainsString('onclick', $registry->allSanitized()['LINK']);
    }

    /**
     * 17) The "BODY" placeholder is always excluded from sanitization,
     * regardless of whether MarkupHTMLPurifier is installed - it holds
     * the already-assembled HTML body content itself (the template
     * structure), not a single user-controlled value.
     */
    public function testAllSanitizedLeavesBodyPlaceholderUntouched(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('body', '<div>Already-assembled template content &amp; markup</div>');

        $this->assertSame(
            '<div>Already-assembled template content &amp; markup</div>',
            $registry->allSanitized()['BODY']
        );
    }

    /**
     * 18) A plain value with no special characters is unaffected by
     * sanitization, regardless of which underlying mechanism is used.
     */
    public function testAllSanitizedLeavesPlainValuesUnchanged(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('username', 'Max Mustermann');

        $this->assertSame('Max Mustermann', $registry->allSanitized()['USERNAME']);
    }

    /**
     * 19) allSanitized() does not modify the original, unsanitized values
     * returned by all()/get() - the sanitized array is a separate copy,
     * not an in-place mutation.
     */
    public function testAllSanitizedDoesNotAffectRawGetterMethods(): void
    {
        $registry = new MailPlaceholderRegistry();
        $registry->set('commentvalue', '<b>bold</b>');

        $registry->allSanitized();

        $this->assertSame('<b>bold</b>', $registry->get('commentvalue'));
        $this->assertSame('<b>bold</b>', $registry->all()['COMMENTVALUE']);
    }

    /**
     * 20) FALLBACK TEST: when MarkupHTMLPurifier is not installed,
     * allSanitized() still neutralizes dangerous markup via
     * htmlspecialchars() rather than passing it through unsanitized -
     * "fail safe, not fail open". This is the flip side of
     * testAllSanitizedPreservesSafeLinks(): in this fallback mode, even
     * a safe, well-formed link is escaped into visible tag text rather
     * than rendering as a clickable link (the same limitation the
     * original, htmlspecialchars()-only implementation always had).
     *
     * Skipped if MarkupHTMLPurifier IS installed on the test site, since
     * this test specifically covers its absence.
     */
    public function testAllSanitizedFallsBackToEscapingWithoutPurifier(): void
    {
        if (wire('modules')->isInstalled('MarkupHTMLPurifier')) {
            $this->markTestSkipped('MarkupHTMLPurifier is installed on this test site.');
        }

        $registry = new MailPlaceholderRegistry();
        $registry->set('verificationlink', '<a href="https://example.com">Activate</a>');

        $this->assertSame(
            '&lt;a href=&quot;https://example.com&quot;&gt;Activate&lt;/a&gt;',
            $registry->allSanitized()['VERIFICATIONLINK']
        );
    }
}
