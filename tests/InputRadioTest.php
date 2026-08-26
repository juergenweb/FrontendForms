<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\InputRadio;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InputRadio.
 */
final class InputRadioTest extends TestCase
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
     * 1) The element's "type" attribute is "radio".
     */
    public function testConstructorSetsRadioType(): void
    {
        $radio = new InputRadio('myradio');

        $this->assertSame('radio', $radio->getAttribute('type'));
    }

    // --- ___renderInputRadio() ---

    /**
     * 2) The radio is rendered checked when its value matches a configured
     * default value.
     */
    public function testRenderChecksRadioWhenValueMatchesDefaultValue(): void
    {
        $radio = new InputRadio('myradio');
        $radio->setAttribute('value', 'yes');
        $radio->setDefaultValue('yes');

        $out = $radio->renderInputRadio();

        $this->assertStringContainsString('checked', $out);
    }

    /**
     * 3) The radio is rendered checked when its value matches the
     * submitted POST value.
     */
    public function testRenderChecksRadioWhenValueMatchesPostValue(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['myradio'] = 'yes';

        $radio = new InputRadio('myradio');
        $radio->setAttribute('name', 'myradio');
        $radio->setAttribute('value', 'yes');

        $out = $radio->renderInputRadio();

        $this->assertStringContainsString('checked', $out);
    }

    /**
     * 4) With no matching post or default value, the radio is not checked.
     */
    public function testRenderDoesNotCheckRadioWithoutMatch(): void
    {
        $radio = new InputRadio('myradio');
        $radio->setAttribute('value', 'yes');

        $out = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $out);
    }

    /**
     * 5) An empty value never gets marked checked, even if the (unrelated,
     * absent) POST value would otherwise loosely resemble it - the
     * "$value && ..." guard specifically prevents matching on empty values.
     */
    public function testEmptyValueIsNeverCheckedEvenWithoutSubmission(): void
    {
        $radio = new InputRadio('myradio');
        $radio->setAttribute('value', '');

        $out = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $out);
    }

    /**
     * 6) REGRESSION TEST for the strict-comparison fix: a value that would
     * loosely (but not strictly) equal one of the default values must NOT
     * be marked checked. "1e2" and "100" are loosely equal as numeric
     * strings in PHP but are different strings - confirmed standalone
     * before writing this assertion:
     *   in_array("1e2", ["100"])         => true  (loose)
     *   in_array("1e2", ["100"], true)   => false (strict)
     * Before the fix (missing "strict: true"), this radio would have been
     * incorrectly rendered as checked.
     */
    public function testStrictComparisonPreventsLooseNumericStringMatch(): void
    {
        $radio = new InputRadio('myradio');
        // setDefaultValue() also sets the "value" attribute internally, so
        // it must be called BEFORE the explicit setAttribute() below,
        // otherwise it would overwrite our intended "1e2" back to "100".
        $radio->setDefaultValue('100');
        $radio->setAttribute('value', '1e2');

        $out = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $out);
    }

    /**
     * REGRESSION TEST for the fixed bug: after the form has been
     * submitted with this radio deliberately left unselected, the
     * configured default value must NOT be re-applied on re-render (e.g.
     * after a validation failure on some other field). An unselected
     * radio sends no POST entry at all, which is indistinguishable from
     * "never submitted" by looking at this field's own post value alone -
     * isSubmitted() is what makes the difference.
     */
    public function testRenderDoesNotFallBackToDefaultWhenSubmittedButUnselected(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['someotherfield'] = 'x'; // the form WAS submitted, just not this radio

        $radio = new InputRadio('myradio');
        $radio->setDefaultValue('yes');
        $radio->setAttribute('value', 'yes');

        $out = $radio->renderInputRadio();

        $this->assertStringNotContainsString('checked', $out);
    }
}
