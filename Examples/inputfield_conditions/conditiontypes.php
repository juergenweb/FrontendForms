<?php
    declare(strict_types=1);

    namespace ProcessWire;
    /*
     * This file contains some examples of showing/hiding/enabling/disabling input fields depending on values of another field
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: conditiontypes.php
     * Created: 08.07.2024
     */

    /*
     * You can copy this code to a template to show the forms
     * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
     * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
     */

    /*
    * Example 1: showif type
    * Example 2: hideif type
    * Example 3: enableif type
    * Example 4: disableif type
    */

    echo '<h2>Some examples of using the 4 conditions (showIf, hideIf, enableIf, disableIf) on input fields</h2>';
    echo '<p>This examples are only for demonstration purpose and does not make sense in real life situations ;-).</p>';

    echo '<h3>Example 1 (showIf): Show firstname field only if "Miss" is selected at the gender field</h3>';
    echo '<p>This example shows how the "showIf" condition works.</p>';

    $form = new \FrontendForms\Form('form1');
    $form->setMaxAttempts(0);// disable attempts

    $gender = new \FrontendForms\InputRadioMultiple('gender');
    $gender->setLabel('Gender');
    $gender->setDefaultValue('Mister');
    $gender->addOption('Mister', 'Mister');
    $gender->addOption('Miss', 'Miss');
    $gender->addOption('Diverse', 'Diverse');
    $form->add($gender);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Miss' // this is the value that should be selected to show the firstname field
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

    echo '<h3>Example 2 (hideIf): Hide firstname field if "Diverse" is selected at the gender field</h3>';
    echo '<p>This example shows how the "hideIf" condition works.</p>';

    $form = new \FrontendForms\Form('form2');
    $form->setMaxAttempts(0);// disable attempts

    $gender = new \FrontendForms\InputRadioMultiple('gender');
    $gender->setLabel('Gender');
    $gender->setDefaultValue('Mister');
    $gender->addOption('Mister', 'Mister');
    $gender->addOption('Miss', 'Miss');
    $gender->addOption('Diverse', 'Diverse');
    $form->add($gender);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->hideIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Diverse' // this is the value that should be selected to hide the firstname field
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

    echo '<h3>Example 3 (enableIf): Enable firstname field if "Diverse" is selected at the gender field</h3>';
    echo '<p>This example shows how the "enableIf" condition works.</p>';

    $form = new \FrontendForms\Form('form3');
    $form->setMaxAttempts(0);// disable attempts

    $gender = new \FrontendForms\InputRadioMultiple('gender');
    $gender->setLabel('Gender');
    $gender->setDefaultValue('Mister');
    $gender->addOption('Mister', 'Mister');
    $gender->addOption('Miss', 'Miss');
    $gender->addOption('Diverse', 'Diverse');
    $form->add($gender);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->enableIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Diverse' // this is the value that should be selected to enable the firstname field
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

    echo '<h3>Example 4 (disableIf): Disable firstname field if "Miss" is selected at the gender field</h3>';
    echo '<p>This example shows how the "disableIf" condition works.</p>';

    $form = new \FrontendForms\Form('form3');
    $form->setMaxAttempts(0);// disable attempts

    $gender = new \FrontendForms\InputRadioMultiple('gender');
    $gender->setLabel('Gender');
    $gender->setDefaultValue('Mister');
    $gender->addOption('Mister', 'Mister');
    $gender->addOption('Miss', 'Miss');
    $gender->addOption('Diverse', 'Diverse');
    $form->add($gender);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->disableIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Miss' // this is the value that should be selected to disable the firstname field
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
