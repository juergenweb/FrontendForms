<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Label;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for Label.
 *
 * Whether the asterisk actually appears for a required field depends on the
 * "input_showasterisk" module config, which - like getShowAsteriskConfig()
 * tested for Pico2InputRenderer earlier in this session - can't be relied
 * upon from the live test environment. Here it's forced to a known value by
 * setting the $enableAsterisk property directly via ReflectionProperty
 * (there is no public setter for it), which fully determines the outcome
 * since Label::___render() only checks that property now (the redundant
 * second config check was removed as part of this session's fix).
 */
final class LabelTest extends TestCase
{
    private function setEnableAsterisk(Label $label, bool $enable): void
    {
        $prop = new ReflectionProperty($label, 'enableAsterisk');
        $prop->setAccessible(true);
        $prop->setValue($label, $enable ? 1 : 0);
    }

    // --- construction ---

    /**
     * 1) The element's tag is "label".
     */
    public function testConstructorSetsLabelTag(): void
    {
        $label = new Label('mylabel');

        $this->assertSame('label', $label->getTag());
    }

    // --- getRequired() / setRequired() ---

    /**
     * 2) A freshly created label is not required.
     */
    public function testIsNotRequiredByDefault(): void
    {
        $this->assertFalse((new Label('mylabel'))->getRequired());
    }

    /**
     * 3) setRequired() marks the label as required.
     */
    public function testSetRequiredMarksLabelRequired(): void
    {
        $label = new Label('mylabel');
        $label->setRequired();

        $this->assertTrue($label->getRequired());
    }

    // --- disableAsterisk() ---

    /**
     * 4) disableAsterisk() forces the internal asterisk flag off,
     * regardless of what it was before.
     */
    public function testDisableAsteriskForcesFlagOff(): void
    {
        $label = new Label('mylabel');
        $this->setEnableAsterisk($label, true);

        $label->disableAsterisk();

        $prop = new ReflectionProperty($label, 'enableAsterisk');
        $prop->setAccessible(true);
        $this->assertSame(0, $prop->getValue($label));
    }

    // --- ___render() ---

    /**
     * 5) With no text set, rendering returns an empty string.
     */
    public function testRenderReturnsEmptyStringWithNoText(): void
    {
        $label = new Label('mylabel');

        $this->assertSame('', $label->render());
    }

    /**
     * 6) A non-required label with text renders just the plain text, no
     * asterisk and no "required" CSS class.
     */
    public function testRenderNonRequiredLabelHasNoAsterisk(): void
    {
        $label = new Label('mylabel');
        $label->setText('Your name');

        $out = $label->render();

        $this->assertStringContainsString('Your name', $out);
        $this->assertStringNotContainsString('<span', $out);
    }

    /**
     * 7) A required label, with the asterisk flag enabled, includes the
     * asterisk markup after the label text.
     */
    public function testRenderRequiredLabelWithAsteriskEnabledIncludesAsterisk(): void
    {
        $label = new Label('mylabel');
        $label->setText('Your name');
        $label->setRequired();
        $this->setEnableAsterisk($label, true);

        $out = $label->render();

        // renderAsterisk() is hookable (___renderAsterisk()) and its
        // markup/class/character can be customized by a site-specific
        // hook, so this only checks that SOME <span class="...">...</span>
        // marker was appended right after the label text - not the exact
        // default markup, which would be environment-dependent.
        $this->assertStringContainsString('Your name', $out);
        $this->assertMatchesRegularExpression(
            '/Your name<span class="[^"]+">.+<\/span>/',
            $out
        );
    }

    /**
     * 8) A required label, but with disableAsterisk() called, does NOT
     * include the asterisk markup - even if the asterisk flag would
     * otherwise be enabled.
     */
    public function testRenderRequiredLabelWithAsteriskDisabledOmitsAsterisk(): void
    {
        $label = new Label('mylabel');
        $label->setText('Your name');
        $label->setRequired();
        $this->setEnableAsterisk($label, true);
        $label->disableAsterisk();

        $out = $label->render();

        $this->assertStringContainsString('Your name', $out);
        $this->assertStringNotContainsString('<span', $out);
    }

    /**
     * 9) A required label with the asterisk flag disabled (but
     * disableAsterisk() not explicitly called) also omits the asterisk.
     */
    public function testRenderRequiredLabelWithAsteriskFlagOffOmitsAsterisk(): void
    {
        $label = new Label('mylabel');
        $label->setText('Your name');
        $label->setRequired();
        $this->setEnableAsterisk($label, false);

        $out = $label->render();

        $this->assertStringNotContainsString('<span', $out);
    }

    // --- getCustomTag() ---

    /**
     * 10) REGRESSION TEST: the constructor's own default-tag initialization
     * ("label") must NOT be recorded as a "custom" tag. Before the fix,
     * the constructor called $this->setTag('label') - which, through
     * TraitTags's overridden setTag(), always records the tag into
     * $customTag - meaning getCustomTag() incorrectly returned 'label'
     * immediately after construction, even though no one ever explicitly
     * customized it. This caused Form::changeElementTag() to always treat
     * every fresh Label as already having a "custom" tag, so the
     * "input_global_label_tag" module config could never actually be
     * applied (see Form.php's changeElementTag(): it only applies the
     * global tag when getCustomTag() is falsy).
     */
    public function testGetCustomTagIsNullByDefault(): void
    {
        $label = new Label('mylabel');

        $this->assertNull($label->getCustomTag());
        $this->assertSame('label', $label->getTag());
    }

    /**
     * 11) After the constructor's own default is bypassed, an explicit,
     * deliberate call to setTag() by calling code is still correctly
     * recorded as a custom tag.
     */
    public function testGetCustomTagReflectsExplicitSetTagCall(): void
    {
        $label = new Label('mylabel');
        $label->setTag('h2');

        $this->assertSame('h2', $label->getCustomTag());
        $this->assertSame('h2', $label->getTag());
    }
}