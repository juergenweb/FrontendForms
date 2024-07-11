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
    * Example 1: show/hide a text element
    */

    echo '<h2>Some examples of using conditions to show/hide elements which are not of type inputfield</h2>';

    echo '<h3>Example 1 (showIf): Show text, alert and link element only if a certain value is selected at the gender field</h3>';
    echo '<p>This example demonstrates, that you can show/hide other elements of the form which are not an inputfield too.</p>';
    $form = new \FrontendForms\Form('form1');
    $form->setMaxAttempts(0);// disable attempts

    $gender = new \FrontendForms\InputRadioMultiple('gender');
    $gender->setLabel('Gender');
    $gender->setDefaultValue('Mister');
    $gender->addOption('Mister', 'Mister');
    $gender->addOption('Miss', 'Miss');
    $gender->addOption('Diverse', 'Diverse');
    $form->add($gender);

    // adding some text to the form via the TextElements class
    $text = new \FrontendForms\TextElements('text');
    $text->setContent('This is my text');
    // add the single condition as an array
    $text->showIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Miss' // this is the value that should be selected to show the text
    ]);
    $form->add($text);

    // adding an alert to the form via the Alert class
    $alert = new \FrontendForms\Alert('alert');
    $alert->setText('This is my alert text');
    // add the single condition as an array
    $alert->showIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Diverse' // this is the value that should be selected to show the alert
    ]);
    $form->add($alert);

    // adding a link to the form via the Link class
    $link = new \FrontendForms\Link('mylink');
    $link->setLinkText('This is my link text');
    $link->setAttribute('href','http://www.google.com');
    // add the single condition as an array
    $link->showIf([
        'name' => 'gender', // name of the gender field
        'operator' => 'is', // this is the operator
        'value' => 'Mister' // this is the value that should be selected to show the alert
    ]);
    $form->add($link);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {
        // do whatever you want
    }

    echo $form->render();




