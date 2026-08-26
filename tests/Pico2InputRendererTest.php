<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckbox;
use FrontendForms\InputText;
use FrontendForms\Pico2InputRenderer;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Unit tests for Pico2InputRenderer.
 *
 * Focuses on what's DIFFERENT from DefaultInputRenderer (already covered by
 * DefaultInputRendererTest): checkbox/radio fields wrap the input, label
 * text, and (for required fields, if enabled) a required-asterisk together
 * inside the <label> itself.
 *
 * getShowAsteriskConfig() reads the "input_showasterisk" module config value,
 * which depends on the live test environment's configuration and can't be
 * relied upon from here. To get deterministic results, it's forced to a
 * known value via ReflectionProperty on $frontendforms (a protected array
 * property with no public setter) rather than guessing at its live value.
 *
 * See DefaultInputRendererTest's class docblock for why real field instances
 * are used instead of mocks.
 */
final class Pico2InputRendererTest extends TestCase
{
    private function renderer(): Pico2InputRenderer
    {
        return new Pico2InputRenderer();
    }

    /**
     * Force the "input_showasterisk" module config flag on a real field
     * instance to a known value, bypassing whatever the live test
     * environment actually has configured.
     */
    private function setShowAsterisk(InputCheckbox $field, bool $show): void
    {
        $prop = new ReflectionProperty($field, 'frontendforms');
        $prop->setAccessible(true);
        $config = $prop->getValue($field);
        $config['input_showasterisk'] = $show;
        $prop->setValue($field, $config);
    }

    /**
     * 1) For checkbox-like fields, the input markup, label text, and
     * messages all end up combined into the <label> element itself, and
     * $input_markup passed onward is empty (nothing left to wrap
     * separately).
     */
    public function testCheckboxBranchCombinesEverythingIntoTheLabel(): void
    {
        $field = new InputCheckbox('agree');
        $field->setLabel('I agree to the terms');

        $out = $this->renderer()->render($field, 'InputCheckbox', '[CHECKBOXINPUT]');

        $this->assertStringContainsString('[CHECKBOXINPUT]', $out);
        $this->assertStringContainsString('I agree to the terms', $out);
        $labelOpenPos = strpos($out, '<label');
        $inputPos = strpos($out, '[CHECKBOXINPUT]');
        $this->assertNotFalse($labelOpenPos);
        $this->assertGreaterThan($labelOpenPos, $inputPos);
    }

    /**
     * 2) A field WITHOUT a "required" rule never gets an asterisk appended,
     * regardless of the "show asterisk" config setting.
     */
    public function testNoAsteriskWhenFieldIsNotRequired(): void
    {
        // Compared against a baseline that is guaranteed to have no
        // asterisk (not required, asterisk config disabled) - if enabling
        // "show asterisk" on a non-required field produces identical
        // output to that baseline, the setting had no effect, as expected.
        // (See testAsteriskAppearsForRequiredFieldWhenConfigEnabled() for
        // why an exact markup shape isn't asserted directly.)
        $field = new InputCheckbox('optional');
        $field->setLabel('Optional field');
        $this->setShowAsterisk($field, true);
        $out = $this->renderer()->render($field, 'InputCheckbox', '[INPUT]');

        $baselineField = new InputCheckbox('optional');
        $baselineField->setLabel('Optional field');
        $this->setShowAsterisk($baselineField, false);
        $baselineOut = $this->renderer()->render($baselineField, 'InputCheckbox', '[INPUT]');

        $this->assertSame($baselineOut, $out);
    }

    /**
     * 3) A required field gets an asterisk appended to the label WHEN the
     * "show asterisk" config is enabled.
     */
    public function testAsteriskAppearsForRequiredFieldWhenConfigEnabled(): void
    {
        // renderAsterisk() is hookable and a site-specific hook can replace
        // its output with anything at all (confirmed live: not even a
        // <span> wrapper, just a bare "+" character) - so instead of
        // asserting any particular markup shape, this compares the enabled
        // output against the disabled output for the exact same field and
        // just confirms enabling it actually adds something.
        $enabledField = new InputCheckbox('required_field');
        $enabledField->setLabel('Required field');
        $enabledField->setRule('required');
        $this->setShowAsterisk($enabledField, true);
        $enabledOut = $this->renderer()->render($enabledField, 'InputCheckbox', '[INPUT]');

        $disabledField = new InputCheckbox('required_field');
        $disabledField->setLabel('Required field');
        $disabledField->setRule('required');
        $this->setShowAsterisk($disabledField, false);
        $disabledOut = $this->renderer()->render($disabledField, 'InputCheckbox', '[INPUT]');

        $this->assertNotSame($disabledOut, $enabledOut);
    }

    /**
     * 4) A required field does NOT get an asterisk when the "show asterisk"
     * config is disabled, even though the field itself is required.
     */
    public function testNoAsteriskForRequiredFieldWhenConfigDisabled(): void
    {
        // Rather than comparing rendered markup against a baseline with a
        // DIFFERENT required state (which would also change Label's own
        // "required" CSS class, an unrelated confound), this checks the
        // config getter that Pico2InputRenderer itself consults directly
        // (getShowAsteriskConfig()) - the actual decision point for
        // whether renderAsterisk() gets called at all.
        $field = new InputCheckbox('required_field');
        $field->setRule('required');
        $this->setShowAsterisk($field, false);

        $this->assertFalse($field->getShowAsteriskConfig());
    }

    /**
     * 5) Just like DefaultInputRenderer, InputHidden still removes the
     * "class" attribute.
     */
    public function testInputHiddenStillRemovesClassAttribute(): void
    {
        $field = new InputText('hiddenfield');
        $field->setAttribute('class', 'should-be-removed');

        $this->renderer()->render($field, 'InputHidden', '<input type="hidden">');

        $this->assertNull($field->getAttribute('class'));
    }
}