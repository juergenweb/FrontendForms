<?php
namespace ProcessWire;

/**
 * Demonstration of a complex form
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content = '<h1>Test page for various inputfields</h1>';
$content .= '<p>you have to adapt/remove the namespace from the objects depending on if you are using a namespace inside your templates or not.</p>';


$form = new \FrontendForms\Form('inputfieldtest');
$form->setMinTime(8);
$form->setMaxTime(3600);
$form->setMaxAttempts(0);
$form->setErrorMsg('Ouups! There are some errors.');
$form->setSuccessMsg('Congratulation! There are no errors.');

$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->setLabel('Multiple files upload');
$file1->setRule('allowedFileSize', '60000');
$file1->setRule('allowedFileExt', ['jpg','pdf']);


$form->add($file1);

$file2 = new \FrontendForms\InputFile('fileupload2');
$file2->setLabel('Single file upload');
$file2->setRule('allowedFileExt');
$form->add($file2);

$datalist = new \FrontendForms\Datalist('datalist');
$datalist->setLabel('Browsers');
$datalist->addOption('Edge', 'Edge');
$datalist->addOption('Firefox', 'Firefox');
$datalist->addOption('Chrome', 'Chrome');
$datalist->addOption('Opera', 'Opera');
$datalist->addOption('Safari', 'Safari');
//$datalist->setRule('required');

$form->add($datalist);

$inputCheckbox = new \FrontendForms\InputCheckbox('checkbox');
$inputCheckbox->setLabel('Single checkbox');
$inputCheckbox->setAttribute('value', 'single');
$inputCheckbox->setRule('required');
$form->add($inputCheckbox);

$inputCheckbox2 = new \FrontendForms\InputCheckbox('checkbox2');
$inputCheckbox2->setLabel('Single checkbox');
$inputCheckbox2->setAttribute('value', 'single');
$inputCheckbox2->setAttribute('checked');
$inputCheckbox2->setRule('required');
$form->add($inputCheckbox2);

$checkboxmulti = new \FrontendForms\InputCheckboxMultiple('multicheckboxhorizontal');
$checkboxmulti->setLabel('Multiple checkboxes horizontal');
$checkboxmulti->addOption('Checkbox 1', 1)->setChecked();
$checkboxmulti->addOption('Checkbox 2', 2)->setChecked();
$checkboxmulti->addOption('Checkbox 3', 3);
$checkboxmulti->setRule('required');
$form->add($checkboxmulti);

$checkboxmultivertical = new \FrontendForms\InputCheckboxMultiple('multicheckboxvertical');
$checkboxmultivertical->setLabel('Multiple checkboxes vertical');
$checkboxmultivertical->alignVertical();
$checkboxmultivertical->addOption('Checkbox 1', 1);
$checkboxmultivertical->addOption('Checkbox 2', 2)->setChecked();
$checkboxmultivertical->addOption('Checkbox 3', 3);
$checkboxmultivertical->setRule('required');
$form->add($checkboxmultivertical);

$inputcolor = new \FrontendForms\InputColor('color');
$inputcolor->setLabel('Input Color');
$inputcolor->setAttribute('value', '#990000');
$inputcolor->setRule('required');
$form->add($inputcolor);

$inputDate = new \FrontendForms\InputDate('date');
$inputDate->setLabel('Input Date');
$inputDate->setAttribute('min', '018-01-01');
$inputDate->setAttribute('max', '2018-12-31');
$inputDate->setRule('required');
$form->add($inputDate);

$inputDateTime = new \FrontendForms\InputDateTime('datetime');
$inputDateTime->setLabel('Input Datetime');
$inputDateTime->setAttribute('value', '2022-06-08T09:00');
$inputDateTime->setRule('required');
$form->add($inputDateTime);

$inputemail = new \FrontendForms\InputEmail('email');
$inputemail->setLabel('Input Email');
$inputemail->setAttribute('value', 'test@gmx.at');
$inputemail->setRule('required');
$form->add($inputemail);

$inputmonth = new \FrontendForms\InputMonth('month');
$inputmonth->setLabel('Input Month');
//$inputmonth->setAttribute('value', '2018-05');
$inputmonth->setRule('required');
$form->add($inputmonth);

$inputnumber = new \FrontendForms\InputNumber('number');
$inputnumber->setLabel('Input Number');
$inputnumber->setAttribute('value', '2');
$inputnumber->setRule('required');
$form->add($inputnumber);

$password = new \FrontendForms\InputPassword('pass');
$password->setLabel('Password');
$password->setRule('required');
$password->setRule('safePassword');
$password->showPasswordRequirements();
$password->showPasswordToggle();
$password->getFieldWrapper()->prepend('<div class="uk-child-width-1-2" data-uk-grid>')->removeAttributeValue('class', 'uk-margin');
$form->add($password);

$singleRadio = new \FrontendForms\InputRadio('single');
$singleRadio->setLabel('Single radio button');
$singleRadio->setAttribute('value', 'single');
$singleRadio->setRule('required');
$singleRadio->setNotes('This field makes no sense');
$form->add($singleRadio);

$gender = new \FrontendForms\InputRadioMultiple('gender');
$gender->setLabel('Gender')->setAttribute('class', 'myextralabelclass');
//$gender->setDefaultValue('Male');
$gender->addOption('Male', 'Male')->setAttribute('class','male');
$gender->addOption('Female', 'Female')->setAttribute('class','female');
$gender->addOption('Diverse', 'Diverse')->setAttribute('class','diverse');
$gender->setRule('required');
$form->add($gender);

$inputRange = new \FrontendForms\InputRange('range');
$inputRange->setLabel('Input Range');
$inputRange->setAttribute('min', '0');
$inputRange->setAttribute('max', '10');
$inputRange->setAttribute('value', '5');
$inputRange->setRule('required');
$form->add($inputRange);

$inputRange2 = new \FrontendForms\InputRange('range2');
$inputRange2->setLabel('Input Range 2');
$inputRange2->setDefaultValue('100');
$form->add($inputRange2);

$inputSearch = new \FrontendForms\InputSearch('search');
$inputSearch->setLabel('Input Search');
$inputSearch->setRule('required');
$form->add($inputSearch);

$inputPhone = new \FrontendForms\InputTel('phone');
$inputPhone->setLabel('Phone');
$inputPhone->setRule('required');
$inputPhone->setRule('integer');
$inputPhone->getFieldWrapper()->removeAttributeValue('class', 'uk-margin');
$form->add($inputPhone);

$inputText = new \FrontendForms\InputText('text');
$inputText->setLabel('Input Text');
$inputText->setRule('time');
//$inputText->setRule('required');
$form->add($inputText);

$inputTime = new \FrontendForms\InputTime('time');
$inputTime->setLabel('Input Time');
$inputTime->setRule('required');
$form->add($inputTime);

$inputURL = new \FrontendForms\InputUrl('url');
$inputURL->setLabel('Input URL');
$inputURL->setRule('required');
$form->add($inputURL);

$inputWeek = new \FrontendForms\InputWeek('week');
$inputWeek->setLabel('Input Week');
$inputWeek->setAttribute('value', '2017-W01');
$inputWeek->setRule('required');
$form->add($inputWeek);

$textarea = new \FrontendForms\Textarea('textarea');
$textarea->setLabel('Textarea');
$textarea->setRule('required');
$form->add($textarea);

$php = new \FrontendForms\Select('php');
$php->setLabel('My preferred PHP version is');
//$php->setDefaultValue('PHP 8');
$php->addEmptyOption();
$php->addOption('PHP 6', 'PHP 6');
$php->addOption('PHP 7', 'PHP 7');
$php->addOption('PHP 8', 'PHP 8');
$php->setRule('required');
$form->add($php);

$php2 = new \FrontendForms\Select('php2');
$php2->setLabel('My preferred PHP version is');
$php->setDefaultValue('PHP 8');
$php2->addEmptyOption();
$php2->addOption('PHP 6', 'PHP 6');
$php2->addOption('PHP 7', 'PHP 7');
$php2->addOption('PHP 8', 'PHP 8');
$php2->setRule('required');
$form->add($php2);

$css = new \FrontendForms\SelectMultiple('css');
$css->setLabel('I have knowledge in');
$css->addEmptyOption();
$css->addOption('CSS 1', 'CSS 1');
$css->addOption('CSS 2', 'CSS 2');
$css->addOption('CSS 3', 'CSS 3');
$css->addOption('Less', 'Less');
$css->addOption('Sass', 'Sass');
$css->setRule('required');
$form->add($css);

$sel2 = new \FrontendForms\SelectMultiple('sel2');
$sel2->setLabel('I have knowledge in');
$sel2->addEmptyOption();
$sel2->addOption('CSS 1', 'CSS 1');
$sel2->addOption('CSS 2', 'CSS 2');
$sel2->addOption('CSS 3', 'CSS 3');
$sel2->addOption('Less', 'Less');
$sel2->addOption('Sass', 'Sass');
$sel2->setRule('required');
$form->add($sel2);

$sel3 = new \FrontendForms\SelectMultiple('sel3');
$sel3->setLabel('I have knowledge in');
$sel3->addEmptyOption();
$sel3->addOption('CSS 1', 'CSS 1');
$sel3->addOption('CSS 2', 'CSS 2');
$sel3->addOption('CSS 3', 'CSS 3');
$sel3->addOption('Less', 'Less');
$sel3->addOption('Sass', 'Sass');
$sel3->setDefaultValue('CSS 1', 'Less', 'Sass');
$sel3->setRule('required');
$form->add($sel3);


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

