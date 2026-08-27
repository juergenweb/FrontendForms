<?php

namespace ProcessWire;

/**
 * Demonstration of a form inside a UiKit grid system
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content = '<h2>Test page for a form inside a grid</h2>';
$content .= '<p>In this case a UiKit grid system is used.</p>';

$form = new \FrontendForms\Form('gridform');
$form->useInputWrapper(false);
$form->setAttributes(['data-uk-grid', 'class' => 'uk-grid-small']);


$test1 = new \FrontendForms\InputText('test1');
$test1->setLabel('Input 1');
$test1->setAttribute('placeholder', '100');
$test1->getFieldWrapper()->setAttribute('class', 'uk-width-1-1');
$form->add($test1);

$test2 = new \FrontendForms\InputText('test2');
$test2->setLabel('Input 2');
$test2->setAttribute('placeholder', '50');
$test2->getFieldWrapper()->setAttribute('class', 'uk-width-1-2@s');
$form->add($test2);

$test3 = new \FrontendForms\InputText('test3');
$test3->setLabel('Input 3');
$test3->setAttribute('placeholder', '25');
$test3->getFieldWrapper()->setAttribute('class', 'uk-width-1-4@s');
$form->add($test3);

$test4 = new \FrontendForms\InputText('test4');
$test4->setLabel('Input 4');
$test4->setAttribute('placeholder', '25');
$test4->getFieldWrapper()->setAttribute('class', 'uk-width-1-4@s');
$form->add($test4);

$test5 = new \FrontendForms\InputText('test5');
$test5->setLabel('Input 5');
$test5->setAttribute('placeholder', '50');
$test5->getFieldWrapper()->setAttribute('class', 'uk-width-1-2@s');
$form->add($test5);

$test6 = new \FrontendForms\InputText('test6');
$test6->setLabel('Input 6');
$test6->setAttribute('placeholder', '50');
$test6->getFieldWrapper()->setAttribute('class', 'uk-width-1-2@s');
$form->add($test6);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$button->prepend('<div class="uk-width-1-1">');
$button->append('</div>');
$form->add($button);

if ($form->isValid()) {

    $content .= 'Submission was successful!';
    // or do what you want

}

// render the form
$content .= $form->render();

echo $content;
