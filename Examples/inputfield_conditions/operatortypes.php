<?php
    declare(strict_types=1);

    namespace ProcessWire;
    /*
     * This file contains some examples of showing/hiding/enabling/disabling input fields depending on values of another field
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: operatortypes.php
     * Created: 08.07.2024
     */

    /*
     * You can copy this code to a template to show the forms
     * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
     * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
     */

    /*
     * Example 1: greaterthan
     * Example 2: lessthan
     * Example 3: contains
     * Example 4: doesnotcontain
     * Example 5: beginswith
     * Example 6: doesnotbeginwith
     * Example 7: endswith
     * Example 8: doesnotendwith
     * Example 9: isempty
     * Example 10: isnotempty
     * Example 11: is
     * Example 12: isnot
     */

    echo '<h1>Operator tests</h1>';
    echo '<h2>Some examples of using the various operator types (is, isnot, greaterthan, contains,...) on input fields</h2>';
    echo '<p>This examples are only for demonstration purpose and often does not make sense in real life situations ;-).</p>';

    echo '<h3>Example 1 (greaterthan): Show firstname field only if value in the number field is greater than 5</h3>';
    echo '<p>This example shows how the "greaterthan" operator works.</p>';

    $form = new \FrontendForms\Form('form1');
    $form->setMaxAttempts(0);// disable attempts

    $inputnumber = new \FrontendForms\InputNumber('number');
    $inputnumber->setLabel('Input Number');
    $form->add($inputnumber);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'number', // name of the number field
        'operator' => 'greaterthan', // this is the operator
        'value' => 5 // the value entered must be greater than 5 to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 2 (lessthan): Show firstname field only if value in the number field is lower than 5</h3>';
    echo '<p>This example shows how the "lessthan" operator works.</p>';

    $form = new \FrontendForms\Form('form2');
    $form->setMaxAttempts(0);// disable attempts

    $inputnumber = new \FrontendForms\InputNumber('number');
    $inputnumber->setLabel('Input Number');
    $form->add($inputnumber);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'number', // name of the number field
        'operator' => 'lessthan', // this is the operator
        'value' => 5 // the value entered must be lower than 5 to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 3 (contains): Show firstname field only if value contains "CSS"</h3>';
    echo '<p>This example shows how the "contains" operator works.</p>';

    $form = new \FrontendForms\Form('form3');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'contains', // this is the operator
        'value' => 'CSS' // the value must contain "CSS" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 4 (doesnotcontain): Show firstname field only if value does not contain "CSS"</h3>';
    echo '<p>This example shows how the "doesnotcontain" operator works.</p>';

    $form = new \FrontendForms\Form('form4');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'doesnotcontain', // this is the operator
        'value' => 'CSS' // the value must not contain CSS to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 5 (beginswith): Show firstname field only if value begins with "S"</h3>';
    echo '<p>This example shows how the "beginswith" operator works.</p>';


    $form = new \FrontendForms\Form('form5');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'beginswith', // this is the operator
        'value' => 'S' // the value must begin with "S" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 6 (doesnotbeginwith): Show firstname field only if value does not begin with "C"</h3>';
    echo '<p>This example shows how the "doesnotbeginwith" operator works.</p>';

    $form = new \FrontendForms\Form('form6');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'doesnotbeginwith', // this is the operator
        'value' => 'C' // the value must not begin with "C" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();


    echo '<h3>Example 7 (endswith): Show firstname field only if value ends with "S"</h3>';
    echo '<p>This example shows how the "endswith" operator works.</p>';

    $form = new \FrontendForms\Form('form7');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'endswith', // this is the operator
        'value' => 'S' // the value must end with "S" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();


    echo '<h3>Example 8 (doesnotendwith): Show firstname field only if value does not end with "S"</h3>';
    echo '<p>This example shows how the "doesnotendwith" operator works.</p>';

    $form = new \FrontendForms\Form('form8');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'doesnotendwith', // this is the operator
        'value' => 'S' // the value must end with "S" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();


    echo '<h3>Example 9 (isempty): Show firstname field only if value is empty</h3>';
    echo '<p>This example shows how the "isempty" operator works.</p>';

    $form = new \FrontendForms\Form('form9');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addEmptyOption();
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'isempty', // this is the operator
        'value' => '' // the value must be empty to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

    echo '<h3>Example 10 (isnotempty): Show firstname field only if field is not empty</h3>';
    echo '<p>This example shows how the "isnotempty" operator works.</p>';

    $form = new \FrontendForms\Form('form10');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addEmptyOption();
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'isnotempty', // this is the operator
        'value' => '' // the value must not be empty to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();



    echo '<h3>Example 12 (isnot): Show firstname field only if select value is not "Less"</h3>';
    echo '<p>This example shows how the "isnot" operator works.</p>';

    $form = new \FrontendForms\Form('form12');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addEmptyOption();
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'select', // name of the select field
        'operator' => 'isnot', // this is the operator
        'value' => 'Less' // the value must not be "Less" to show the firstname field
    ]);
    // please note: every validation rule, you have set will be only validated if the field is visible and not disabled
    // if the field is not visible and/or disabled, none of the validators will checked after form submission
    $firstname->setRule('required');
    $form->add($firstname);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();

