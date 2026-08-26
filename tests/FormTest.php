<?php

declare(strict_types=1);

namespace FrontendForms\Tests\Unit;

use FrontendForms\Form;
use FrontendForms\InputText;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for Form's wrapper-propagation behaviour.
 *
 * Every field, being Tag-descended, is pre-populated with a concrete
 * boolean useInputWrapper/useFieldWrapper value from the backend module
 * config at construction time (see Tag::__construct(), which assigns the
 * property directly, bypassing the tracked setter). A separate
 * "explicitly set" flag (only set by a genuine, direct
 * useInputWrapper()/useFieldWrapper() call on the field, never by the
 * constructor or by Form's own internal propagation) lets Form
 * distinguish "this field has its own real per-field choice" from "this
 * field is just carrying the backend default" - so the form-level
 * setting can safely win in the latter case, while the former is always
 * respected regardless of call order.
 */
final class FormTest extends TestCase
{
    /**
     * 1) REGRESSION TEST: calling useInputWrapper() on the Form AFTER a
     * field was already added updates that field too.
     */
    public function testUseInputWrapperUpdatesAlreadyAddedField(): void
    {
        $form = new Form('myform');
        $field = new InputText('firstname');
        $form->add($field);

        $form->useInputWrapper(true);
        $this->assertTrue($field->getUsageOfInputWrapper());

        $form->useInputWrapper(false);
        $this->assertFalse($field->getUsageOfInputWrapper());
    }

    /**
     * 2) The same update behaviour applies to useFieldWrapper().
     */
    public function testUseFieldWrapperUpdatesAlreadyAddedField(): void
    {
        $form = new Form('myform');
        $field = new InputText('firstname');
        $form->add($field);

        $form->useFieldWrapper(true);
        $this->assertTrue($field->getUsageOfFieldWrapper());

        $form->useFieldWrapper(false);
        $this->assertFalse($field->getUsageOfFieldWrapper());
    }

    /**
     * 3) REGRESSION TEST: a form-level useInputWrapper() call made BEFORE
     * add() is correctly picked up when the field is added - add() used
     * to check is_null() on the field's own wrapper value to decide
     * whether to apply the form-level setting, but that value is never
     * actually null in practice, so a call made before add() was
     * previously silently ignored.
     */
    public function testUseInputWrapperCalledBeforeAddIsApplied(): void
    {
        $form = new Form('myform');
        $form->useInputWrapper(false);

        $field = new InputText('firstname');
        $form->add($field);

        $this->assertFalse($field->getUsageOfInputWrapper());
    }

    /**
     * 4) Calling useInputWrapper() on the Form updates ALL currently
     * added fields at once, not just one.
     */
    public function testUseInputWrapperUpdatesAllAddedFields(): void
    {
        $form = new Form('myform');
        $field1 = new InputText('firstname');
        $field2 = new InputText('lastname');
        $form->add($field1);
        $form->add($field2);

        $form->useInputWrapper(false);

        $this->assertFalse($field1->getUsageOfInputWrapper());
        $this->assertFalse($field2->getUsageOfInputWrapper());
    }

    /**
     * 5) A field's own explicit useInputWrapper() call, made BEFORE it is
     * added to the form, is respected and not overridden by add() -
     * even when the form's own setting differs.
     */
    public function testExplicitFieldSettingBeforeAddIsRespected(): void
    {
        $form = new Form('myform');
        $form->useInputWrapper(true);

        $field = new InputText('firstname');
        $field->useInputWrapper(false);
        $form->add($field);

        $this->assertFalse($field->getUsageOfInputWrapper());
    }

    /**
     * 6) A field's own explicit useInputWrapper() call, made AFTER it was
     * added to the form, is respected and not overridden by a later
     * form-level useInputWrapper() call.
     */
    public function testExplicitFieldSettingAfterAddIsRespected(): void
    {
        $form = new Form('myform');
        $field = new InputText('firstname');
        $form->add($field);

        $field->useInputWrapper(false);
        $form->useInputWrapper(true);

        $this->assertFalse($field->getUsageOfInputWrapper());
    }

    /**
     * 7) The same explicit-setting protection applies to useFieldWrapper().
     */
    public function testExplicitFieldWrapperSettingIsRespected(): void
    {
        $form = new Form('myform');
        $field = new InputText('firstname');
        $field->useFieldWrapper(false);
        $form->add($field);

        $form->useFieldWrapper(true);

        $this->assertFalse($field->getUsageOfFieldWrapper());
    }

    /**
     * 8) A field WITHOUT its own explicit setting still correctly follows
     * the form-level value, alongside another field that DOES have its
     * own explicit setting - confirming both behaviours coexist
     * correctly within the same form.
     */
    public function testMixedExplicitAndInheritedFieldsBehaveIndependently(): void
    {
        $form = new Form('myform');

        $inheritingField = new InputText('firstname');
        $explicitField = new InputText('lastname');
        $explicitField->useInputWrapper(false);

        $form->add($inheritingField);
        $form->add($explicitField);

        $form->useInputWrapper(true);

        $this->assertTrue($inheritingField->getUsageOfInputWrapper());
        $this->assertFalse($explicitField->getUsageOfInputWrapper());
    }
}
