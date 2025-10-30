<?php
declare(strict_types=1);

namespace ProcessWire;
/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: simplemultistepform.php
 * Created: 30.10.2025 
 */

/*
 * Demonstration of a very simple multi-step form 
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content =  '<h2>A simple working multi-step form</h2>';
$content .=  '<p>This form was splitted into 5 steps.</p>';

$form = new \FrontendForms\Form('simpleform');
$form->setMaxAttempts(0); // set to 0 for testing purposes
$form->setMinTime(0); // set to 0 for testing purposes
$form->setMaxTime(0); // set to 0 for testing purposes

$firstname = new \FrontendForms\InputText('firstname');
$firstname->setLabel('Firstname');
$firstname->setRule('required');
$form->add($firstname);

$lastname = new \FrontendForms\InputText('lastname');
$lastname->setLabel('Lastname');
$lastname->setRule('required');
$form->add($lastname);

$form->addStep(); // first step

$email = new \FrontendForms\InputEmail('email');
$email->setLabel('Input Email');
$email->setRule('required');
$form->add($email);

$form->addStep(); // second step

$birthday = new \FrontendForms\InputDate('date');
$birthday ->setLabel('Birthday');
$form->add($birthday );

$form->addStep(); // third step

$message = new \FrontendForms\Textarea('message');
$message->setLabel('My message');
$form->add($message);

$form->addStep(); // fourth step

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);


if($form->isValid()){


    print_r($form->getValues());
    // do what you want

}

// render the form
$content .= $form->render();

echo $content;