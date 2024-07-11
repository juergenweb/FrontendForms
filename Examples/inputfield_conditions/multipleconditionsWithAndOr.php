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
     * Example 1: Combine 2 conditions with OR logic
     * Example 2: Combine 2 conditions with AND logic
     */

    echo '<h2>Examples of combining values of different fields with AND and OR logic</h2>';
    echo '<p>This examples are only for demonstration purpose and often does not make sense in real life situations ;-).</p>';

    echo '<h3>Example 1: Show firstname field only if value of select input is "Less" OR value of number input is "3"</h3>';

    $form = new \FrontendForms\Form('form1');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addEmptyOption();
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $inputnumber = new \FrontendForms\InputNumber('number');
    $inputnumber->setLabel('Input Number');
    $form->add($inputnumber);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add multiple conditions inside an array and comma separated [[condition 1],[condition 2]]
    // Please note: by default the OR logic is selected, so you do not have to define it
    $firstname->showIf([[
        'name' => 'select', // name of the select field
        'operator' => 'is', // this is the operator
        'value' => 'Less' // the value that should be selected to show the firstname field
    ],
        ['name' => 'number', // name of the number field
            'operator' => 'is', // this is the operator
            'value' => '3' // the value that should be selected to show the firstname field
        ]]);
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


    echo '<h3>Example 2: Show firstname field only if value of select input is "Less" AND value of number input is "3"</h3>';
    echo '<p>This is the same as the first example, but with the AND logic.</p>';

    $form = new \FrontendForms\Form('form2');
    $form->setMaxAttempts(0);// disable attempts

    $inputselect = new \FrontendForms\Select('select');
    $inputselect->setLabel('Input Select');
    $inputselect->addEmptyOption();
    $inputselect->addOption('CSS 1', 'CSS 1');
    $inputselect->addOption('CSS 2', 'CSS 2');
    $inputselect->addOption('Less', 'Less');
    $inputselect->addOption('Sass', 'Sass');
    $form->add($inputselect);

    $inputnumber = new \FrontendForms\InputNumber('number');
    $inputnumber->setLabel('Input Number');
    $form->add($inputnumber);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add multiple conditions inside an array and comma separated [[condition 1],[condition 2]]
    // Please note: by default the OR logic is selected, so you do not have to define it
    $firstname->showIf([[
        'name' => 'select', // name of the select field
        'operator' => 'is', // this is the operator
        'value' => 'Less' // the value that should be selected to show the firstname field
    ],
        ['name' => 'number', // name of the number field
            'operator' => 'is', // this is the operator
            'value' => '3' // the value that should be selected to show the firstname field
        ]], 'and'); // please note, you have to define the "AND" logic in this case
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
