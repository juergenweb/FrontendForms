<?php
    declare(strict_types=1);

    namespace ProcessWire;
    /*
     * This file contains some examples of showing/hiding/enabling/disabling input fields depending on values of an multivalue field
     * Multivalue fields are fields which can contain more than 1 value like checkbox multiple or select multiple
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: multivaluefieldconditions.php
     * Created: 08.07.2024
     */

    /*
     * You can copy this code to a template to show the forms
     * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
     * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
     */

    /*
    * Example 1: Checkbox multiple with AND Logic
    * Example 2: Checkbox multiple with OR Logic
    * Example 3: Checkbox multiple with NO Logic (only single value)
    * Example 4: Select multiple with AND Logic
    * Example 5: Select multiple with OR Logic
    * Example 6: Select multiple with NO Logic (only single value)
    */

    echo '<h1>Some examples of using conditions on multi-value fields (checkbox multiple, select multiple)</h1>';
    echo '<p>This examples are only for demonstration purpose ;-).</p>';

    echo '<h2>Checkboxes multiple</h2>';
    echo '<p>Checkboxes multiple can have one or more values selected.</p>';

    echo '<h3>Example 1 (checkbox multiple) with AND logic: Show firstname field only if "2" AND "3" are selected in the checkbox multi field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for multiple values of a multivalue checkbox field.</p>';
    echo '<p>In this case the firstname field will be only visible if value "2" and "3" will be selected at the checkboxes.</p>';

    $form = new \FrontendForms\Form('form1');
    $form->setMaxAttempts(0);// disable attempts

    $checkboxmultivertical = new \FrontendForms\InputCheckboxMultiple('multicheckboxvertical');
    $checkboxmultivertical->setLabel('Multiple checkboxes vertical');
    $checkboxmultivertical->alignVertical();
    $checkboxmultivertical->addOption('Checkbox 1', '1');
    $checkboxmultivertical->addOption('Checkbox 2', '2');
    $checkboxmultivertical->addOption('Checkbox 3', '3');
    $form->add($checkboxmultivertical);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    // AND conditon has to written with multiple values separated with "|"
    $firstname->showIf([
        'name' => 'multicheckboxvertical', // name of the checkbox field
        'operator' => 'is', // this is the operator
        'value' => '2|3' // please note: multiple values with AND logic have to be separated with "|"
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


    echo '<h3>Example 2 (checkbox multiple) with OR logic: Show firstname field only if "2" OR "3" are selected in the checkbox multi field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for a single value of a multivalue checkbox field.</p>';
    echo '<p>In this case the firstname field will be only visible if value "2" or "3" will be selected at the checkboxes.</p>';

    $form = new \FrontendForms\Form('form2');
    $form->setMaxAttempts(0);// disable attempts

    $checkboxmultivertical = new \FrontendForms\InputCheckboxMultiple('multicheckboxvertical');
    $checkboxmultivertical->setLabel('Multiple checkboxes vertical');
    $checkboxmultivertical->alignVertical();
    $checkboxmultivertical->addOption('Checkbox 1', '1');
    $checkboxmultivertical->addOption('Checkbox 2', '2');
    $checkboxmultivertical->addOption('Checkbox 3', '3');
    $form->add($checkboxmultivertical);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the multi conditions as an array
    // OR conditions must be written as 2 separate conditions for multiple checkboxes
    $firstname->showIf([[
        'name' => 'multicheckboxvertical', // name of the checkbox field
        'operator' => 'is', // this is the operator
        'value' => '2' // the value
    ],[
        'name' => 'multicheckboxvertical', // name of the checkbox field
        'operator' => 'is', // this is the operator
        'value' => '3' // the valeu
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


    echo '<h3>Example 3 (checkbox multiple) with NO logic: Show firstname field only if "2" is selected in the checkbox multi field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for a single value with only 1 condition of a multivalue checkbox field.</p>';
    echo '<p>In this case the firstname field will be only visible if value "2" will be selected at the checkboxes.</p>';

    $form = new \FrontendForms\Form('form3');
    $form->setMaxAttempts(0);// disable attempts

    $checkboxmultivertical = new \FrontendForms\InputCheckboxMultiple('multicheckboxvertical');
    $checkboxmultivertical->setLabel('Multiple checkboxes vertical');
    $checkboxmultivertical->alignVertical();
    $checkboxmultivertical->addOption('Checkbox 1', '1');
    $checkboxmultivertical->addOption('Checkbox 2', '2');
    $checkboxmultivertical->addOption('Checkbox 3', '3');
    $form->add($checkboxmultivertical);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'multicheckboxvertical', // name of the checkbox field
        'operator' => 'is', // this is the operator
        'value' => '2' // the value
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

    echo '<h2>Select multiple</h2>';
    echo '<p>Select multiple can also have 1 or more values selected.</p>';
    echo '<p><b>Please note: Select multiple contains "[]" at the end of the name attribute for sending multiple values as an array, you also have to add it to the name property of the condition array!</b></p>';

    echo '<h3>Example 4 (select multiple) with AND logic: Show firstname field only if "CSS2", "Less" and "Sass" are selected in the select multiple field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for multiple values of a select multiple field.</p>';
    echo '<p>In this case the firstname field will be only visible if value "CSS2", "Less" and "Sass" will be selected.</p>';

    $form = new \FrontendForms\Form('form4');
    $form->setMaxAttempts(0);// disable attempts

    $css = new \FrontendForms\SelectMultiple('css');
    $css->setLabel('I have knowledge in');
    $css->addEmptyOption();
    $css->addOption('CSS 1', 'CSS 1');
    $css->addOption('CSS 2', 'CSS 2');
    $css->addOption('CSS 3', 'CSS 3');
    $css->addOption('Less', 'Less');
    $css->addOption('Sass', 'Sass');
    $form->add($css);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the single condition as an array
    $firstname->showIf([
        'name' => 'css[]', // name of the select field (do not forget the brackets []!)
        'operator' => 'is', // this is the operator
        'value' => 'CSS 2|Less|Sass' // please note: multiple values with AND logic have to be separated with "|"
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


    echo '<h3>Example 5 (select multiple) with OR logic: Show firstname field only if "CSS 2" OR "Less" is selected in the select multiple field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for a single value of a select multiple field.</p>';
    echo '<p>For OR conditions you have to write a condition for each value.</p>';
    echo '<p>In this case the firstname field will be only visible if value "CSS2" OR "Less" will be selected.</p>';

    $form = new \FrontendForms\Form('form5');
    $form->setMaxAttempts(0);// disable attempts

    $css = new \FrontendForms\SelectMultiple('css');
    $css->setLabel('I have knowledge in');
    $css->addEmptyOption();
    $css->addOption('CSS 1', 'CSS 1');
    $css->addOption('CSS 2', 'CSS 2');
    $css->addOption('CSS 3', 'CSS 3');
    $css->addOption('Less', 'Less');
    $css->addOption('Sass', 'Sass');
    $form->add($css);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the OR conditions as array of conditions inside an array separated by a comma  => [[condition 1], [condition2]]
    $firstname->showIf([[
            'name' => 'css[]', // name of the select field
            'operator' => 'is', // this is the operator
            'value' => 'CSS 2' // select only 1 value
        ],
            [
                'name' => 'css[]', // name of the select field
                'operator' => 'is', // this is the operator
                'value' => 'Less' // select only 1 value
            ]]
    );
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


    echo '<h3>Example 6 (select multiple) with NO logic: Show firstname field only if "CSS 2" is selected in the select multiple field</h3>';
    echo '<p>This example shows how the "showIf" condition works by checking for a specific single value of a select multiple field.</p>';
    echo '<p>In this case the firstname field will be only visible if value "CSS2" will be selected.</p>';

    $form = new \FrontendForms\Form('form6');
    $form->setMaxAttempts(0);// disable attempts

    $css = new \FrontendForms\SelectMultiple('css');
    $css->setLabel('I have knowledge in');
    $css->addEmptyOption();
    $css->addOption('CSS 1', 'CSS 1');
    $css->addOption('CSS 2', 'CSS 2');
    $css->addOption('CSS 3', 'CSS 3');
    $css->addOption('Less', 'Less');
    $css->addOption('Sass', 'Sass');
    $form->add($css);

    $firstname = new \FrontendForms\InputText('firstname');
    $firstname->setLabel('Firstname');
    // add the OR conditions as array of conditions inside an array separated by a comma  => [[condition 1], [condition2]]
    $firstname->showIf([
            'name' => 'css[]', // name of the select field
            'operator' => 'is', // this is the operator
            'value' => 'CSS 2' // select only 1 value
        ]
    );
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
