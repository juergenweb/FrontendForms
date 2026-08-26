<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProcessWire\FrontendForms;

/**
 * Unit tests for FrontendForms::isFontAwesomeFile(), used by
 * findAllFontfiles() to exclude Font Awesome's icon font files from the
 * list of selectable CAPTCHA fonts - icon fonts don't contain glyphs for
 * regular text characters, so selecting one would make CAPTCHA text
 * render as empty/missing-glyph boxes.
 *
 * This is a pure, static function with no wire()/filesystem dependencies,
 * so it's tested directly rather than through the full, directory-
 * scanning findAllFontfiles() (which would require redirecting
 * wire('config')->paths->root - the kind of risky path manipulation this
 * session has otherwise deliberately avoided).
 */
final class IsFontAwesomeFileTest extends TestCase
{
    /**
     * 1) REGRESSION TEST for the fixed bug: the actual, real-world path
     * ProcessWire's admin theme ships Font Awesome under (hyphenated,
     * "font-awesome") is correctly detected - the original check only
     * matched the non-hyphenated "fontawesome" and missed this.
     */
    public function testDetectsHyphenatedFontAwesomePath(): void
    {
        $this->assertTrue(FrontendForms::isFontAwesomeFile(
            'wire/templates-admin/styles/font-awesome-6.7.2/webfonts/fa-brands-400.ttf'
        ));
    }

    /**
     * 2) The original, non-hyphenated spelling is still detected too.
     */
    public function testDetectsNonHyphenatedFontAwesomePath(): void
    {
        $this->assertTrue(FrontendForms::isFontAwesomeFile('site/assets/fonts/FontAwesome.ttf'));
    }

    /**
     * 3) Detection is case-insensitive.
     */
    public function testDetectionIsCaseInsensitive(): void
    {
        $this->assertTrue(FrontendForms::isFontAwesomeFile('site/assets/fonts/FONT-AWESOME-Solid.ttf'));
        $this->assertTrue(FrontendForms::isFontAwesomeFile('site/assets/fonts/fontAWESOME.ttf'));
    }

    /**
     * 4) A regular, unrelated font file is correctly NOT flagged as
     * Font Awesome.
     */
    public function testDoesNotFlagRegularFontFiles(): void
    {
        $this->assertFalse(FrontendForms::isFontAwesomeFile(
            'site/modules/FrontendForms/Formelements/Captcha/fonts/OpenSans-SemiBold.ttf'
        ));
        $this->assertFalse(FrontendForms::isFontAwesomeFile('site/templates/fonts/SomeOtherFont.ttf'));
    }

    /**
     * 5) A font file whose name merely contains "awesome" or "font" on
     * their own (not as part of the "fontawesome"/"font-awesome" phrase)
     * is not falsely flagged.
     */
    public function testDoesNotFlagUnrelatedFilesContainingSimilarWords(): void
    {
        $this->assertFalse(FrontendForms::isFontAwesomeFile('site/assets/fonts/AwesomeSans.ttf'));
        $this->assertFalse(FrontendForms::isFontAwesomeFile('site/assets/fonts/MyFont.ttf'));
    }
}
