<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputRadio;
use FrontendForms\InputRadioMultiple;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the retainSubmittedValue fix, on both levels where it
 * matters:
 * - InputRadio::___renderInputRadio() (a single radio button)
 * - InputRadioMultiple::___renderInputRadioMultiple() (a radio group,
 *   e.g. the image CAPTCHA's answer options), which must propagate its
 *   own retainSubmittedValue setting down to each individual InputRadio
 *   option before rendering it - otherwise the individual radio's own,
 *   independent post-value check would silently re-check itself
 *   regardless of the group's setting (the actual bug that was fixed).
 *
 * isSubmitted()/getServerMethod() read $_POST / $_SERVER['REQUEST_METHOD']
 * directly (not even via wire('input')), so these are safely and directly
 * controllable in a test without any ProcessWire-side syncing concerns.
 */
final class RetainSubmittedValueTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
    }

    protected function tearDown(): void
    {
        $_POST = [];
        parent::tearDown();
    }

    // ---------- InputRadio (single radio) ----------

    /**
     * 1) Default behavior (retainSubmittedValue not touched, defaults to
     * true): a submitted value stays checked on re-render - the normal,
     * unchanged behavior for every other radio in the project.
     */
    public function testSingleRadioRetainsSubmittedValueByDefault(): void
    {
        $_POST['answer'] = 'blue';

        $radio = new InputRadio('answer');
        $radio->setAttribute('value', 'blue');
        $html = $radio->renderInputRadio();

        $this->assertStringContainsString('checked', $html);
    }

    /**
     * 2) REGRESSION TEST for the fixed bug: with retainSubmittedValue(false),
     * a submitted value must NOT stay checked - this is the exact
     * behavior the image CAPTCHA's radio options need.
     */
    public function testSingleRadioDoesNotRetainSubmittedValueWhenDisabled(): void
    {
        $_POST['answer'] = 'blue';

        $radio = new InputRadio('answer');
        $radio->setAttribute('value', 'blue');
        $radio->retainSubmittedValue(false);
        $html = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $html);
    }

    /**
     * 3) With retainSubmittedValue(false), a default value must also not
     * be pre-checked - a CAPTCHA answer must never come pre-selected,
     * submitted or not.
     */
    public function testSingleRadioDoesNotRetainDefaultValueWhenDisabled(): void
    {
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET'; // not yet submitted

        $radio = new InputRadio('answer');
        $radio->setAttribute('value', 'blue');
        $radio->setDefaultValue('blue');
        $radio->retainSubmittedValue(false);
        $html = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $html);
    }

    // ---------- InputRadioMultiple (radio group) ----------

    /**
     * 4) Default behavior for a radio group: the submitted option stays
     * checked on re-render (normal radio group behavior, unrelated to
     * the CAPTCHA use case).
     */
    public function testRadioGroupRetainsSubmittedValueByDefault(): void
    {
        $_POST['color'] = 'green';

        $group = new InputRadioMultiple('color');
        $group->addOption('Red', 'red');
        $group->addOption('Green', 'green');
        $html = $group->renderInputRadioMultiple();

        $this->assertStringContainsString('checked', $html);
    }

    /**
     * 5) REGRESSION TEST for the fixed bug: with the group's
     * retainSubmittedValue(false) - as set for the image CAPTCHA - the
     * submitted option must NOT stay checked, and critically, this
     * setting must actually reach the individual InputRadio options
     * during rendering (the part that was broken before the fix).
     */
    public function testRadioGroupDoesNotRetainSubmittedValueWhenDisabled(): void
    {
        $_POST['color'] = 'green';

        $group = new InputRadioMultiple('color');
        $group->addOption('Red', 'red');
        $group->addOption('Green', 'green');
        $group->retainSubmittedValue(false);
        $html = $group->renderInputRadioMultiple();

        $this->assertStringNotContainsString('checked', $html);
    }

    /**
     * 6) Confirms the propagation happens on every option in the group,
     * not just coincidentally on the first/matching one - submitting a
     * value that matches a LATER option in the list must also correctly
     * stay unchecked.
     */
    public function testRadioGroupPropagatesSettingToEveryOption(): void
    {
        $_POST['color'] = 'blue';

        $group = new InputRadioMultiple('color');
        $group->addOption('Red', 'red');
        $group->addOption('Green', 'green');
        $group->addOption('Blue', 'blue'); // the submitted, matching option is last
        $group->retainSubmittedValue(false);
        $html = $group->renderInputRadioMultiple();

        $this->assertStringNotContainsString('checked', $html);
    }
}
