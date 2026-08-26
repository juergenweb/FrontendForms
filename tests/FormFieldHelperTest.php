<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Button;
use FrontendForms\FormFieldHelper;
use FrontendForms\InputCheckboxMultiple;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;
use ProcessWire\WireInputData;

/**
 * Unit tests for FormFieldHelper.
 *
 * Uses a real WireInputData instance (constructed directly with an array of
 * submitted values) rather than a mock, consistent with the lessons learned
 * earlier in this session about ProcessWire core classes being unreliable
 * to mock (see CaptchaQuestionRepositoryTest/DefaultInputRendererTest) -
 * WireInputData's constructor accepts a plain array directly.
 */
final class FormFieldHelperTest extends TestCase
{

    /**
     * WireInputData's constructor takes its array argument by reference,
     * so it can't be passed a literal array expression directly at the
     * call site (new WireInputData([...])) - wrapping it in a real
     * parameter variable here satisfies that requirement.
     */
    private function inputData(array $data = []): WireInputData
    {
        return new WireInputData($data);
    }

    // --- getRealInputFields() ---

    /**
     * 1) Only genuine Inputfields elements are kept - other form elements
     * (e.g. a Button) are filtered out.
     */
    public function testGetRealInputFieldsFiltersOutNonInputElements(): void
    {
        $helper = new FormFieldHelper($this->inputData([]));
        $text = new InputText('name');
        $button = new Button('submit');

        $result = $helper->getRealInputFields([$text, $button]);

        $this->assertSame([$text], $result);
    }

    /**
     * 2) The returned array is re-indexed numerically from 0, regardless of
     * the original array's keys.
     */
    public function testGetRealInputFieldsReindexesResult(): void
    {
        $helper = new FormFieldHelper($this->inputData([]));
        $text = new InputText('name');

        $result = $helper->getRealInputFields(['some_key' => new Button('submit'), 'other_key' => $text]);

        $this->assertSame([0 => $text], $result);
    }

    /**
     * 3) An empty list of form elements yields an empty result.
     */
    public function testGetRealInputFieldsWithEmptyListReturnsEmptyArray(): void
    {
        $helper = new FormFieldHelper($this->inputData([]));

        $this->assertSame([], $helper->getRealInputFields([]));
    }

    // --- sanitizePostValue() ---

    /**
     * 4) When the field's name isn't present in the submitted input data at
     * all, sanitizePostValue() returns null and does not touch the
     * element's "value" attribute.
     */
    public function testSanitizePostValueReturnsNullWhenFieldNotSubmitted(): void
    {
        $helper = new FormFieldHelper($this->inputData([]));
        $field = new InputText('myfield');

        $result = $helper->sanitizePostValue($field);

        $this->assertNull($result);
        $this->assertNull($field->getAttribute('value'));
    }

    /**
     * 5) A regular (single-value) field's submitted value is written back
     * into its "value" attribute and returned.
     */
    public function testSanitizePostValueWritesBackRegularFieldValue(): void
    {
        $helper = new FormFieldHelper($this->inputData(['myfield' => 'Hello world']));
        $field = new InputText('myfield');

        $result = $helper->sanitizePostValue($field);

        $this->assertSame('Hello world', $result);
        $this->assertSame('Hello world', $field->getAttribute('value'));
    }

    /**
     * 6) A falsy but legitimate value ("0") is still written back, not
     * treated as "nothing submitted".
     */
    public function testSanitizePostValueWritesBackFalsyValue(): void
    {
        $helper = new FormFieldHelper($this->inputData(['myfield' => '0']));
        $field = new InputText('myfield');

        $result = $helper->sanitizePostValue($field);

        $this->assertSame('0', $result);
        $this->assertSame('0', $field->getAttribute('value'));
    }

    /**
     * 7) For a multi-value field class (e.g. InputCheckboxMultiple), each
     * submitted array value is individually sanitized.
     */
    public function testSanitizePostValueSanitizesEachValueOfMultiValueField(): void
    {
        $helper = new FormFieldHelper($this->inputData(['mygroup' => ['red', 'blue']]));
        $field = new InputCheckboxMultiple('mygroup');

        $result = $helper->sanitizePostValue($field);

        $this->assertSame(['red', 'blue'], $result);
        $this->assertSame(['red', 'blue'], $field->getAttribute('value'));
    }

    /**
     * 8) For a multi-value field class with nothing selected (submitted as
     * null rather than an empty array), sanitizePostValue() normalizes it
     * to an empty array rather than passing null through.
     */
    public function testSanitizePostValueNormalizesNullToEmptyArrayForMultiValueField(): void
    {
        $helper = new FormFieldHelper($this->inputData(['mygroup' => null]));
        $field = new InputCheckboxMultiple('mygroup');

        $result = $helper->sanitizePostValue($field);

        $this->assertSame([], $result);
    }
}