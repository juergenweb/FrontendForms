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

// add privacy checkbox as switch
$privacy = new \FrontendForms\InputCheckbox("privacy");
$privacy->setAttribute("role", "switch");
$privacy->setLabel("I accept the privacy policy");
$privacy->setRule("required")->setCustomMessage("You have to accept our privacy policy");
$privacy->setNotes('Mein Notes Text.');
$privacy->setDescription('Mein Description Text.');
$privacy->setSuccessMessage('Success');
$form->add($privacy);


//$form->setDescPosition('beforeLabel');


$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->showClearLink(true); // show an link to empty the input field under the input field
$file1->setLabel('Multiple files upload');
$file1->setDescription('Description fileupload1')->setPosition('afterLabel');
$file1->setNotes('Description fileupload1 notes');
$file1->setRule('allowedFileSize', '60000');
$file1->setRule('allowedFileExt', ['jpg','pdf']);
$file1->setSuccessMessage('Super');
$form->add($file1);

$file2 = new \FrontendForms\InputFile('fileupload2');
$file2->setLabel('Single file upload');
$file2->setDescription('Description fileupload2')->setPosition('beforeLabel');
$file2->setRule('allowedFileExt', ['jpg']);
$file2->setSuccessMessage('Super');
$form->add($file2);

$datalist = new \FrontendForms\Datalist('datalist');
$datalist->setLabel('Browsers');
$datalist->setDescription('Description datalist');
$datalist->addOption('Edge', 'Edge');
$datalist->addOption('Firefox', 'Firefox');
$datalist->addOption('Chrome', 'Chrome');
$datalist->addOption('Opera', 'Opera');
$datalist->addOption('Safari', 'Safari');
$datalist->setSuccessMessage('Super');
$datalist->setNotes('Das sind Notes');
//$datalist->setRule('required');

$form->add($datalist);


$checkboxmultiarray = new \FrontendForms\InputCheckboxMultiple('multicheckboxhorizontalarray');
$checkboxmultiarray->setLabel('Multiple checkboxes horizontal with default value as array');
$checkboxmultiarray->setDescription('Description multicheckboxhorizontalarray');
$checkboxmultiarray->addOption('Checkbox 1', '1');
$checkboxmultiarray->addOption('Checkbox 2', '2');
$checkboxmultiarray->addOption('Checkbox 3', '3');
$checkboxmultiarray->setDefaultValue(['1', '3']); // default values as array
$checkboxmultiarray->setRule('required');
$checkboxmultiarray->setSuccessMessage('Super');
$form->add($checkboxmultiarray);

$checkboxmultistring = new \FrontendForms\InputCheckboxMultiple('multicheckboxhorizontalstring');
$checkboxmultistring->setLabel('Multiple checkboxes horizontal with default value as string');
$checkboxmultistring->setDescription('Description multicheckboxhorizontalstring');
$checkboxmultistring->addOption('Checkbox 1', '1');
$checkboxmultistring->addOption('Checkbox 2', '2');
$checkboxmultistring->addOption('Checkbox 3', '3');
$checkboxmultistring->setDefaultValue('1', '3'); // default values as comma separated string
$checkboxmultistring->setRule('required');
$checkboxmultistring->setSuccessMessage('Super');
$form->add($checkboxmultistring);

$inputCheckbox = new \FrontendForms\InputCheckbox('checkbox');
$inputCheckbox->setLabel('Single checkbox');
$inputCheckbox->setDescription('Description checkbox');
$inputCheckbox->setAttribute('value', 'single');
$inputCheckbox->setRule('required');
$inputCheckbox->setSuccessMessage('Super');
$form->add($inputCheckbox);

$inputCheckbox2 = new \FrontendForms\InputCheckbox('checkbox2');
$inputCheckbox2->setLabel('Single checkbox');
$inputCheckbox2->setDescription('Description checkbox2');
$inputCheckbox2->setAttribute('value', 'single');
$inputCheckbox2->setAttribute('checked');
$inputCheckbox2->setRule('required');
$inputCheckbox2->setSuccessMessage('Super');
$form->add($inputCheckbox2);

$checkboxmulti = new \FrontendForms\InputCheckboxMultiple('multicheckboxhorizontal');
$checkboxmulti->setLabel('Multiple checkboxes horizontal');
$checkboxmulti->setDescription('Description multicheckboxhorizontal');
$checkboxmulti->addOption('Checkbox 1', 1)->setChecked();
$checkboxmulti->addOption('Checkbox 2', 2)->setChecked();
$checkboxmulti->addOption('Checkbox 3', 3);
$checkboxmulti->setRule('required');
$checkboxmulti->setSuccessMessage('Super');
$form->add($checkboxmulti);

$checkboxmultivertical = new \FrontendForms\InputCheckboxMultiple('multicheckboxvertical');
$checkboxmultivertical->setLabel('Multiple checkboxes vertical');
$checkboxmultivertical->setDescription('Description checkboxmultivertical');
$checkboxmultivertical->alignVertical();
$checkboxmultivertical->addOption('Checkbox 1', 1);
$checkboxmultivertical->addOption('Checkbox 2', 2)->setChecked();
$checkboxmultivertical->addOption('Checkbox 3', 3);
$checkboxmultivertical->setRule('required');
$checkboxmultivertical->setSuccessMessage('Super');
$form->add($checkboxmultivertical);

$inputcolor = new \FrontendForms\InputColor('color');
$inputcolor->setLabel('Input Color');
$inputcolor->setDescription('Description color');
$inputcolor->setAttribute('value', '#990000');
$inputcolor->setRule('required');
$inputcolor->setSuccessMessage('Super');
$form->add($inputcolor);

$inputDate = new \FrontendForms\InputDate('date');
$inputDate->setLabel('Input Date');
$inputDate->setDescription('Description date');
$inputDate->setAttribute('min', '018-01-01');
$inputDate->setAttribute('max', '2018-12-31');
$inputDate->setRule('required');
$inputDate->setSuccessMessage('Super');
$form->add($inputDate);

$inputDateTime = new \FrontendForms\InputDateTime('datetime');
$inputDateTime->setLabel('Input Datetime');
$inputDateTime->setDescription('Description dateTime');
$inputDateTime->setAttribute('value', '2022-06-08T09:00');
$inputDateTime->setRule('required');
$inputDateTime->setSuccessMessage('Super');
$form->add($inputDateTime);

$inputemail = new \FrontendForms\InputEmail('email');
$inputemail->setLabel('Input Email');
$inputemail->setDescription('Description email');
//$inputemail->setAttribute('value', 'test@gmx.at');
//$inputemail->setRule('required');
$inputemail->setSuccessMessage('Das ist eine korrekte Emailadresse');
$inputemail->setNotes('My notes');
$inputemail->setSuccessMessage('Super');
$form->add($inputemail);

$inputmonth = new \FrontendForms\InputMonth('month');
$inputmonth->setLabel('Input Month');
$inputmonth->setDescription('Description month');
//$inputmonth->setAttribute('value', '2018-05');
$inputmonth->setRule('required');
$inputmonth->setSuccessMessage('Super');
$form->add($inputmonth);

$inputnumber = new \FrontendForms\InputNumber('number');
$inputnumber->setLabel('Input Number');
$inputnumber->setDescription('Description number');
$inputnumber->setAttribute('value', '2');
$inputnumber->setRule('required');
$inputnumber->setSuccessMessage('Super');
$form->add($inputnumber);

$password = new \FrontendForms\InputPassword('pass');
$password->setLabel('Password');
$password->setDescription('Description pass');
$password->setRule('required');
$password->setRule('safePassword');
$password->showPasswordRequirements();
//$password->showPasswordToggle(false); // if you want to hide the toggle checkbox
$password->setSuccessMessage('Super');
$form->add($password);

$singleRadio = new \FrontendForms\InputRadio('single');
$singleRadio->setLabel('Single radio button');
$singleRadio->setDescription('Description single');
$singleRadio->setAttribute('value', 'single');
$singleRadio->setRule('required');
$singleRadio->setNotes('This field makes no sense');
$singleRadio->setSuccessMessage('Super');
$form->add($singleRadio);

$gender = new \FrontendForms\InputRadioMultiple('gender');
$gender->setLabel('Gender')->setAttribute('class', 'myextralabelclass');
$gender->setDescription('Description gender');
//$gender->setDefaultValue('Male');
$gender->addOption('Male', 'Male')->setAttribute('class','male');
$gender->addOption('Female', 'Female')->setAttribute('class','female');
$gender->addOption('Diverse', 'Diverse')->setAttribute('class','diverse');
$gender->setRule('required');
$gender->setSuccessMessage('Super');
$form->add($gender);

$gendervertical = new \FrontendForms\InputRadioMultiple('gendervertical');
$gendervertical->setLabel('Gender');
$gendervertical->setDescription('Description gender vertical');
$gendervertical->alignVertical();
//$gender->setDefaultValue('Male');
$gendervertical->addOption('Male', 'Male')->setAttribute('class','male');
$gendervertical->addOption('Female', 'Female')->setAttribute('class','female');
$gendervertical->addOption('Diverse', 'Diverse')->setAttribute('class','diverse');
$gendervertical->setRule('required');
$gendervertical->setSuccessMessage('Super');
$form->add($gendervertical);

$inputRange = new \FrontendForms\InputRange('range');
$inputRange->setLabel('Input Range');
$inputRange->setDescription('Description range');
$inputRange->setAttribute('min', '0');
$inputRange->setAttribute('max', '10');
$inputRange->setAttribute('value', '5');
$inputRange->setRule('required');
$inputRange->setSuccessMessage('Super');
$form->add($inputRange);

$inputRange2 = new \FrontendForms\InputRange('range2');
$inputRange2->setLabel('Input Range 2');
$inputRange2->setDescription('Description range2');
$inputRange2->setDefaultValue('100');
$inputRange2->setSuccessMessage('Super');
$form->add($inputRange2);

$inputSearch = new \FrontendForms\InputSearch('search');
$inputSearch->setLabel('Input Search');
$inputSearch->setDescription('Description search');
$inputSearch->setRule('required');
$inputSearch->setSuccessMessage('Super');
$form->add($inputSearch);

$inputPhone = new \FrontendForms\InputTel('phone');
$inputPhone->setLabel('Phone');
$inputPhone->setDescription('Description phone');
$inputPhone->setRule('required');
$inputPhone->setRule('integer');
$inputPhone->getFieldWrapper()->removeAttributeValue('class', 'uk-margin');
$inputPhone->setSuccessMessage('Super');
$form->add($inputPhone);

$inputText = new \FrontendForms\InputText('text');
$inputText->setLabel('Input Text');
$inputText->setDescription('Description text');
$inputText->setRule('time');
//$inputText->setRule('required');
$inputText->setSuccessMessage('Super');
$form->add($inputText);

$inputTime = new \FrontendForms\InputTime('time');
$inputTime->setLabel('Input Time');
$inputTime->setDescription('Description time');
$inputTime->setRule('required');
$inputTime->setSuccessMessage('Super');
$form->add($inputTime);

$inputURL = new \FrontendForms\InputUrl('url');
$inputURL->setLabel('Input URL');
$inputURL->setDescription('Description url');
$inputURL->setRule('required');
$inputURL->setSuccessMessage('Super');
$form->add($inputURL);

$inputWeek = new \FrontendForms\InputWeek('week');
$inputWeek->setLabel('Input Week');
$inputWeek->setDescription('Description week');
$inputWeek->setAttribute('value', '2017-W01');
$inputWeek->setRule('required');
$inputWeek->setSuccessMessage('Super');
$form->add($inputWeek);

$textarea = new \FrontendForms\Textarea('textarea');
$textarea->setLabel('Textarea');
$textarea->setDescription('Description textarea');
$textarea->setRule('required');
$textarea->setSuccessMessage('Super');
$form->add($textarea);

$php = new \FrontendForms\Select('php');
$php->setLabel('My preferred PHP version is');
$php->setDescription('Description php');
//$php->setDefaultValue('PHP 8');
$php->addEmptyOption();
$php->addOption('PHP 6', 'PHP 6');
$php->addOption('PHP 7', 'PHP 7');
$php->addOption('PHP 8', 'PHP 8');
$php->setRule('required');
$php->setSuccessMessage('Super');
$form->add($php);

$php2 = new \FrontendForms\Select('php2');
$php2->setLabel('My preferred PHP version is');
$php2->setDescription('Description php2');
$php->setDefaultValue('PHP 8');
$php2->addEmptyOption();
$php2->addOption('PHP 6', 'PHP 6');
$php2->addOption('PHP 7', 'PHP 7');
$php2->addOption('PHP 8', 'PHP 8');
$php2->setRule('required');
$php2->setSuccessMessage('Super');
$form->add($php2);

$css = new \FrontendForms\SelectMultiple('css');
$css->setLabel('I have knowledge in');
$css->setDescription('Description css');
$css->addEmptyOption();
$css->addOption('CSS 1', 'CSS 1');
$css->addOption('CSS 2', 'CSS 2');
$css->addOption('CSS 3', 'CSS 3');
$css->addOption('Less', 'Less');
$css->addOption('Sass', 'Sass');
$css->setRule('required');
$css->setSuccessMessage('Super');
$form->add($css);

$sel2 = new \FrontendForms\SelectMultiple('sel2');
$sel2->setLabel('I have knowledge in (Default values as array)');
$sel2->setDescription('Description sel2');
$sel2->addEmptyOption();
$sel2->addOption('CSS 1', 'CSS 1');
$sel2->addOption('CSS 2', 'CSS 2');
$sel2->addOption('CSS 3', 'CSS 3');
$sel2->addOption('Less', 'Less');
$sel2->addOption('Sass', 'Sass');
$sel2->setDefaultValue(['Less', 'CSS 1' , 'CSS 2']);
$sel2->setRule('required');
$sel2->setSuccessMessage('Super');
$form->add($sel2);

$sel3 = new \FrontendForms\SelectMultiple('sel3');
$sel3->setLabel('I have knowledge in (Default values as comma separated string)');
$sel3->setDescription('Description sel3');
$sel3->addEmptyOption();
$sel3->addOption('CSS 1', 'CSS 1');
$sel3->addOption('CSS 2', 'CSS 2');
$sel3->addOption('CSS 3', 'CSS 3');
$sel3->addOption('Less', 'Less');
$sel3->addOption('Sass', 'Sass');
$sel3->setDefaultValue('CSS 1', 'CSS 3');
$sel3->setRule('required');
$sel3->setSuccessMessage('Super');
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

