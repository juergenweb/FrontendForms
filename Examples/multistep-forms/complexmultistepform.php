<?php
declare(strict_types=1);

namespace ProcessWire;
/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: complexmultistepform.php
 * Created: 25.10.2025 
 */

/*
 * Demonstration of a complex multi-step form 
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content =  '<h2>A complex working multi-step form</h2>';
$content .=  '<p>This form was splitted into 3 steps.</p>';

$form = new \FrontendForms\Form('multistepform');
$form->setMaxAttempts(0); // set to 0 for testing purposes
$form->setMinTime(0); // set to 0 for testing purposes
$form->setMaxTime(0); // set to 0 for testing purposes
$form->useAjax(true); // use Ajax form validation (=true) or not (=false)
//$form->showStepOf(false); // can be used to hide "Show step 1 of 4" text at the top
//$form->showStepsProgressbar(false); // can be used to hide the progress bar

// create the first fieldset
$userdata = new \FrontendForms\FieldsetOpen();
$userdata->setLegend('User data');
$form->add($userdata);

$gender = new \FrontendForms\InputRadioMultiple('gender');
$gender->setLabel('Gender')->setAttribute('class', 'myextralabelclass');
$gender->setDefaultValue('Male'); // this will be selected on page load
$gender->addOption('Male', 'Male')->setAttribute('class', 'male');
$gender->addOption('Female', 'Female')->setAttribute('class', 'female');
$gender->addOption('Diverse', 'Diverse')->setAttribute('class', 'diverse');
$gender->getFieldWrapper()->setAttribute('class', 'uk-width-1-1')->removeAttributeValue('class', 'uk-margin'); // add uikit grid
$form->add($gender);

$firstname = new \FrontendForms\InputText('firstname');
$firstname->setLabel('Firstname');
$firstname->setRule('required')->setCustomMessage('Please enter your first name.'); // customize the error message
$firstname->getFieldWrapper()->prepend('<div class="uk-child-width-1-2" data-uk-grid>')->removeAttributeValue('class', 'uk-margin');
$form->add($firstname);

$lastname = new \FrontendForms\InputText('lastname');
$lastname->setLabel('Lastname');
$lastname->setRule('required');
$lastname->getFieldWrapper()->append('</div>')->removeAttributeValue('class', 'uk-margin');
$form->add($lastname);

// close the fieldset
$userdataClose = new \FrontendForms\FieldsetClose();
$form->add($userdataClose);

// add an extra markup inside the form - in this case a headline
$headline = new \FrontendForms\Markup();
$headline->setMarkup('<h3>Tell us about yourself</h3>');
$form->add($headline);

$interests = new \FrontendForms\InputCheckboxMultiple('interest');
$interests->setLabel('I am interested in');
$interests->addOption('Music', 'Music');
$interests->addOption('Web-design', 'Web-design');
$interests->addOption('Sports', 'Sports');
$interests->addOption('Photography', 'Photography');
$firstname->setRule('required');
$interests->alignVertical();
$form->add($interests);

// STEP 1
$form->addStep(); // this is the first step in this form

$subject = new \FrontendForms\InputText('subject');
$subject->setLabel('Subject');
$subject->setRule('required')->setCustomFieldName('The subject');
$form->add($subject);

$message = new \FrontendForms\Textarea('message');
$message->setLabel('Message');
$message->setRule('required')->setCustomFieldName('The message');
$form->add($message);

$callback = new \FrontendForms\InputCheckbox("callback");
$callback->setAttribute("role", "switch");
$callback->setAttribute('value', 'yes');
$callback->setLabel("I want a callback");
$form->add($callback);

$inputPhone = new \FrontendForms\InputTel('phone');
$inputPhone->setLabel('My phone number');
$inputPhone->showIf([
    'name' => 'callback', // name of the checkbox field
    'operator' => 'is', // this is the operator
    'value' => 'yes' // the value
]);
$form->add($inputPhone);

// STEP 2 = last step
$form->addStep(); // second and last step

// add another additional markup
$uploadheadline = new \FrontendForms\Markup();
$uploadheadline->setMarkup('<h3>Upload some files if you want</h3>');
$form->add($uploadheadline);

// IMPORTANT!! File upload fields must always be after the last step!!! Otherwise they wont work
$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->setLabel('Add some files');
$file1->setMultiple(true);
$file1->setRule('allowedFileSize', '40000');
$file1->setRule('allowedFileExt', ['pdf', 'docx','txt']);
$form->add($file1);

// add privacy checkbox as switch
$privacy = new \FrontendForms\InputCheckbox("privacy");
$privacy->setAttribute("role", "switch");
$privacy->setLabel("I accept the privacy policy");
//$privacy->setRule("required")->setCustomMessage("You have to accept our privacy policy");
$form->add($privacy);

// IMPORTANT!! Submit button must always be the last element in the form
$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Save');
$form->add($button);

if ($form->isValid()) {

    print_r($form->getValues());
}


$content .= $form->render();
echo $content;


