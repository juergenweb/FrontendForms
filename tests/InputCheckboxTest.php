<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputCheckbox;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputCheckbox.
 */
final class InputCheckboxTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_POST = [];
        $_GET = [];
        unset($_SERVER['REQUEST_METHOD']);
    }

    // --- construction ---

    /**
     * 1) The element's "type" attribute is "checkbox".
     */
    public function testConstructorSetsCheckboxType(): void
    {
        $checkbox = new InputCheckbox('mycheckbox');

        $this->assertSame('checkbox', $checkbox->getAttribute('type'));
    }

    // --- setChecked() ---

    /**
     * 2) On a fresh, unsubmitted form (the configured method's request
     * array is empty), the checkbox is checked by default.
     */
    public function testSetCheckedIsCheckedByDefaultOnFreshForm(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setChecked();

        $this->assertSame('checked', $checkbox->getAttribute('checked'));
    }

    /**
     * 3) After a POST submission that includes this checkbox's name, it is
     * checked.
     */
    public function testSetCheckedIsCheckedWhenPresentInPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['mycheckbox'] = '1';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setChecked();

        $this->assertSame('checked', $checkbox->getAttribute('checked'));
    }

    /**
     * 4) After a POST submission that does NOT include this checkbox's name
     * (the normal browser behaviour for an unchecked checkbox), it is not
     * checked.
     */
    public function testSetCheckedIsNotCheckedWhenAbsentFromPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['some_other_field'] = 'x';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setChecked();

        $this->assertNull($checkbox->getAttribute('checked'));
    }

    /**
     * 5) REGRESSION TEST for the GET/POST bugfix: after a GET submission
     * that includes this checkbox's name, it is checked. Before the fix,
     * setChecked() always looked at $_POST directly (never $_GET), so a
     * GET-submitted, actually-checked checkbox would incorrectly render as
     * unchecked.
     */
    public function testSetCheckedIsCheckedWhenPresentInGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['mycheckbox'] = '1';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setChecked();

        $this->assertSame('checked', $checkbox->getAttribute('checked'));
    }

    /**
     * 6) After a GET submission that does NOT include this checkbox's name,
     * it is not checked.
     */
    public function testSetCheckedIsNotCheckedWhenAbsentFromGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['some_other_field'] = 'x';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setChecked();

        $this->assertNull($checkbox->getAttribute('checked'));
    }

    // --- ___renderInputCheckbox() ---

    /**
     * 7) The checkbox is rendered as checked when its "value" matches the
     * submitted POST value.
     */
    public function testRenderChecksBoxWhenValueMatchesPostValue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['mycheckbox'] = 'yes';

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setAttribute('value', 'yes');

        $out = $checkbox->renderInputCheckbox();

        $this->assertStringContainsString('checked', $out);
    }

    /**
     * 8) The checkbox is rendered as checked when its "value" matches a
     * previously configured default value (and the form was not submitted).
     */
    public function testRenderChecksBoxWhenValueMatchesDefaultValue(): void
    {
        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setAttribute('value', 'yes');
        $checkbox->setDefaultValue('yes');

        $out = $checkbox->renderInputCheckbox();

        $this->assertStringContainsString('checked', $out);
    }

    /**
     * 9) With no matching post or default value, the checkbox is rendered
     * unchecked.
     */
    public function testRenderDoesNotCheckBoxWithoutMatch(): void
    {
        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setAttribute('value', 'yes');

        $out = $checkbox->renderInputCheckbox();

        $this->assertStringNotContainsString('checked', $out);
    }

    /**
     * 10) REGRESSION TEST for the fixed bug: after the form has been
     * submitted with this checkbox deliberately left unchecked, the
     * configured default value must NOT be re-applied on re-render (e.g.
     * after a validation failure on some other field). An unchecked
     * checkbox sends no POST entry at all, which is indistinguishable
     * from "never submitted" by looking at this field's own post value
     * alone - isSubmitted() is what makes the difference.
     */
    public function testRenderDoesNotFallBackToDefaultWhenSubmittedButUnchecked(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['someotherfield'] = 'x'; // the form WAS submitted, just not this checkbox

        $checkbox = new InputCheckbox('mycheckbox');
        $checkbox->setAttribute('value', 'yes');
        $checkbox->setDefaultValue('yes');

        $out = $checkbox->renderInputCheckbox();

        $this->assertStringNotContainsString('checked', $out);
    }
}
