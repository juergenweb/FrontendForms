<?php

namespace ProcessWire;
/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb
 * File name: validator-page.php
 * Created: 07.03.2023
 */


use FrontendForms\TextElements;

$content =  '<h2>A form for using a lot (not all) validators</h2>';
$content .=  '<p>This is a test page for testing validation rules and to show how the validation rules should be used.</p>';

$form = new \FrontendForms\Form('validators');
// disable all the following security features
$form->setMaxAttempts(0);
$form->setMinTime(0);
$form->setMaxTime(0);

$required = new \FrontendForms\InputText('required');
$required->setLabel('Validator required');
$required->setRule('required');
$required->setDescription('Validator to check field is not empty');
$form->add($required);

$hex = new \FrontendForms\InputText('hex');
$hex->setLabel('Validator checkHex');
$hex->setRule('checkHex');
$hex->setDescription('Validator to check field contains a valid HEX color code');
$hex->setNotes('Valid value: #666 or #666666, invalid value: #1548');
$form->add($hex);

$checkboxmultiarray = new \FrontendForms\InputCheckboxMultiple('multicheckbox');
$checkboxmultiarray->setLabel('Please select checkbox 1 and 3');
$checkboxmultiarray->setDescription('Validator to check if checkbox 1 and 3 of a multicheckbox field are checked');
$checkboxmultiarray->addOption('Checkbox 1', '1');
$checkboxmultiarray->addOption('Checkbox 2', '2');
$checkboxmultiarray->addOption('Checkbox 3', '3');
$checkboxmultiarray->setRule('required');
$checkboxmultiarray->setRule('listContains' , '1');
$checkboxmultiarray->setRule('listContains' , '3');
$form->add($checkboxmultiarray);

$min = new \FrontendForms\InputNumber('min');
$min->setLabel('Validator min');
$min->setRule('min', '5');
$min->setDescription('Validator to check if number entered is at least 5 or higher');
$min->setNotes('Valid value: 6, invalid value: 4');
$form->add($min);

$max = new \FrontendForms\InputNumber('max');
$max->setLabel('Validator max');
$max->setRule('max', '10');
$max->setDescription('Validator to check if number entered is max least 10 or lower');
$max->setNotes('Valid value: 9, invalid value: 11');
$form->add($max);

$lengthmin = new \FrontendForms\InputText('lengthmin');
$lengthmin->setLabel('Validator lengthmin');
$lengthmin->setRule('lengthMin', '5');
$lengthmin->setDescription('Validator to check if the string contains at least 5 characters');
$lengthmin->setNotes('Valid value: abcdef, invalid value: abd');
$form->add($lengthmin);

$lengthmax = new \FrontendForms\InputText('lengthmax');
$lengthmax->setLabel('Validator lengthMax');
$lengthmax->setRule('lengthMax', '10');
$lengthmax->setDescription('Validator to check if the string is max 10 characters long');
$lengthmin->setNotes('Valid value: abcdef, invalid value: abcdefghijklmn');
$form->add($lengthmax);

$lengthbetween = new \FrontendForms\InputText('lengthbetween');
$lengthbetween->setLabel('Validator lengthbetween');
$lengthbetween->setRule('lengthbetween', '5', '10');
$lengthbetween->setDescription('Validator to check if the string is between 5 and 10 characters long');
$lengthbetween->setNotes('Valid value: abcdefg, invalid value: abc');
$form->add($lengthbetween);

$alpha = new \FrontendForms\InputText('alpha');
$alpha->setLabel('Validator alpha');
$alpha->setRule('alpha');
$alpha->setDescription('Validator to check if string contains only alphabetical letters');
$alpha->setNotes('Valid value: abcdefg, invalid value: abc123');
$form->add($alpha);

$alphanum = new \FrontendForms\InputText('alphanum');
$alphanum->setLabel('Validator alphaNum');
$alphanum->setRule('alphanum');
$alphanum->setDescription('Validator to check if string contains only alphabetical and numeric characters');
$alphanum->setNotes('Valid value: abc123, invalid value: abc123$');
$form->add($alphanum);

$ascii= new \FrontendForms\InputText('ascii');
$ascii->setLabel('Validator ascii');
$ascii->setRule('ascii');
$ascii->setDescription('Validator to check if string contains only ascii characters');
$ascii->setNotes('Valid value: abc123, invalid value: abc123¡');
$form->add($ascii);

$slug= new \FrontendForms\InputText('slug');
$slug->setLabel('Validator slug');
$slug->setRule('slug');
$slug->setDescription('Validator to check if string is a valid slug');
$slug->setNotes('Valid value: this-is-the-slug, invalid value: $123');
$form->add($slug);

$url = new \FrontendForms\InputText('url');
$url->setLabel('Validator url');
$url->setRule('url');
$url->setDescription('Validator to check if string is a valid URL');
$url->setNotes('Valid value: http://www.google.com, invalid value: www.google.com');
$form->add($url);

$email = new \FrontendForms\InputText('email');
$email->setLabel('Validator email');
$email->setRule('email');
$email->setDescription('Validator to check if string is a valid email address');
$email->setNotes('Valid value: myemail@gmx.com, invalid value: abdce');
$form->add($email);

$numeric = new \FrontendForms\InputText('numeric');
$numeric->setLabel('Validator numeric');
$numeric->setRule('numeric');
$numeric->setDescription('Validator to check if value is numeric');
$numeric->setNotes('Valid value: 1.4, invalid value: abd');
$form->add($numeric);

$integer = new \FrontendForms\InputText('integer');
$integer->setLabel('Validator integer');
$integer->setRule('integer');
$integer->setDescription('Validator to check if value is integer');
$integer->setNotes('Valid value: 20, invalid value: 10.5');
$form->add($integer);

$ip = new \FrontendForms\InputText('ip');
$ip->setLabel('Validator ip');
$ip->setRule('ip');
$ip->setDescription('Validator to check if value is a valid IP address');
$ip->setNotes('Valid value: 94.198.41.250, invalid value: 123456');
$form->add($ip);

$ip4 = new \FrontendForms\InputText('ip4');
$ip4->setLabel('Validator ipv4');
$ip4->setRule('ipv4');
$ip4->setDescription('Validator to check if value is a valid IP4 address');
$ip4->setNotes('Valid value: 255.255.255.255, invalid value: 123456');
$form->add($ip4);

$ip6 = new \FrontendForms\InputText('ip6');
$ip6->setLabel('Validator ipv6');
$ip6->setRule('ipv6');
$ip6->setDescription('Validator to check if value is a valid IP6 address');
$ip6->setNotes('Valid value: 2001:0db8:85a3:08d3:1319:8a2e:0370:7334, invalid value: 123456');
$form->add($ip6);

$equals = new \FrontendForms\InputText('equals');
$equals->setLabel('Validator equals');
$equals->setRule('equals', 'validators-ip'); // be aware that the field name is not ip - it is validators-ip!!!
$equals->setDescription('Validator to check if this value is the same value as in the field Validator ip above');
$equals->setNotes('Valid value: enter the same value as in field ip, invalid value: enter different value as in field ip');
$form->add($equals);

$different = new \FrontendForms\InputText('different');
$different->setLabel('Validator different');
$different->setRule('different', 'validators-ip'); // be aware that the field name is not ip - it is validators-ip!!!
$different->setDescription('Validator to check if this value is different as the value as field Validator ip above');
$different->setNotes('Valid value: enter different value as in field ip, invalid value: enter same value as in field ip');
$form->add($different);

$in = new \FrontendForms\InputText('in');
$in->setLabel('Validator in');
$in->setRule('in', ['word1', 'word2', 'word3']);
$in->setDescription('Validator to check if the value is inside a list (array) of values');
$in->setNotes('Valid value: word1, invalid value: word4');
$form->add($in);

$notin = new \FrontendForms\InputText('notin');
$notin->setLabel('Validator notin');
$notin->setRule('notin', ['word1', 'word2', 'word3']);
$notin->setDescription('Validator to check if the value is NOT inside a list (array) of values');
$notin->setNotes('Valid value: word4, invalid value: word1');
$form->add($notin);

// validator only for input fields with array as value, such fe SelectMultiple
$listcontains = new \FrontendForms\SelectMultiple('listcontains');
$listcontains->setLabel('Validator listcontains');
$listcontains->addOption('word1','word1');
$listcontains->addOption('word2','word2');
$listcontains->addOption('word3','word3');
$listcontains->setRule('listcontains', 'word1');
$listcontains->setDescription('Validator to check if the array value contains a value word1');
$listcontains->setNotes('Valid value: word1, invalid value: word2');
$form->add($listcontains);


$time = new \FrontendForms\InputText('time');
$time->setLabel('Validator time');
$time->setRule('time');
$time->setDescription('Validator to check if the value is a valid time string');
$time->setNotes('Valid value: 12:45, invalid value: 25:10');
$form->add($time);

$month = new \FrontendForms\InputText('month');
$month->setLabel('Validator month');
$month->setRule('month');
$month->setAttribute('placeholder', 'YYYY-MM');
$month->setDescription('Validator to check if the value is a valid month string');
$month->setNotes('Valid value: 2023-01, invalid value: 574-10');
$form->add($month);

$week = new \FrontendForms\InputText('week');
$week->setLabel('Validator week');
$week->setRule('week');
$week->setAttribute('placeholder', 'YYYY-Www');
$week->setDescription('Validator to check if the value is a valid week string');
$week->setNotes('Valid value: 2023-W27, invalid value: 2023-12');
$form->add($week);

$date = new \FrontendForms\InputDate('date');
$date->setLabel('Validator date');
$date->setRule('date', ['word1', 'word2', 'word3']);
$date->setDescription('Validator to check if the value is a valid date');
$date->setNotes('Valid value: 01.01.2023, invalid value: 01.01.20235');
$form->add($date);

$dateafter = new \FrontendForms\InputDate('dateafterfield');
$dateafter->setLabel('Validator dateAfterField');
$dateafter->setRule('dateAfterField', 'date');
$dateafter->setDescription('Validator to check if the value is a date after the date entered in the field with the id/name "date"');
$dateafter->setNotes('Enter a date, that is after the date entered in the previous field.');
$form->add($dateafter);

$datebefore = new \FrontendForms\InputDate('datebeforefield');
$datebefore->setLabel('Validator dateBeforeField');
$datebefore->setRule('dateBeforeField', 'date');
$datebefore->setDescription('Validator to check if the value is a date before the date entered in the field with the id/name "date"');
$datebefore->setNotes('Enter a date, that is before the date as entered in the pre-previous field.');
$form->add($datebefore);

// you can enter a positive or negative number of days (in this case +8, but you can also enter -8)
// positive means within 8 days in the future starting from the reference date
// negative means within 8 days in the past starting from the reference date
$dateWithinDaysRange = new \FrontendForms\InputDate('datewithindaysrange');
$dateWithinDaysRange->setLabel('Validator dateWithinDaysRange');
// it is recommended to use a custom message that fits better than the default error message
$dateWithinDaysRange->setRule('dateWithinDaysRange', 'date', 8)->setCustomMessage(sprintf('The date entered must be within 8 days starting from %s', ($_POST) ? $_POST['validators-date'] : ''));
$dateWithinDaysRange->setDescription('Validator to check if the value is a date within the time range of 8 days in the future.');
$dateWithinDaysRange->setNotes('Enter a date, that is within the time range between the date entered inside the field with the id "date" and 8 days in the future.');
$form->add($dateWithinDaysRange);

// you can enter a positive or negative number of days (in this case +8, but you can also enter -8)
// positive means date must be at least 8 days after in the future starting from the reference date
// negative means date must be at least 8 days before in the past starting from the reference date
$dateOutsideDaysRange = new \FrontendForms\InputDate('dateoutsideofdaysrange');
$dateOutsideDaysRange->setLabel('Validator dateOutsideOfDaysRange');
$dateOutsideDaysRange->setRule('dateOutsideOfDaysRange', 'date', 8)->setCustomMessage(sprintf('The date entered must be after 8 days after the starting date %s', ($_POST) ? $_POST['validators-date'] : ''));
$dateOutsideDaysRange->setDescription('Validator to check if the value is a date outside the time range of 8 days in the future.');
$dateOutsideDaysRange->setNotes('Enter a date, that is after the time range between the date entered inside the field with the id "date" and 8 days in the future.');
$form->add($dateOutsideDaysRange);

$dateformat = new \FrontendForms\InputText('dateformat');
$dateformat->setLabel('Validator dateformat');
$dateformat->setRule('dateformat', 'd-m-Y');
$dateformat->setAttribute('placeholder', 'd-m-Y');
$dateformat->setDescription('Validator to check if the value is a valid date and in the given format');
$dateformat->setNotes('Valid value: 01-01-2023, invalid value: 01.01.2023');
$form->add($dateformat);

$dateBefore = new \FrontendForms\InputText('dateBefore');
$dateBefore->setLabel('Validator dateBefore');
$dateBefore->setRule('dateBefore', '31-01-2023');
$dateBefore->setAttribute('placeholder', '31-01-2023');
$dateBefore->setDescription('Validator to check if the value is a valid date and before a given date');
$dateBefore->setNotes('Valid value: 01-01-2023, invalid value: 31-03-2023');
$form->add($dateBefore);

$dateAfter = new \FrontendForms\InputText('dateAfter');
$dateAfter->setLabel('Validator dateAfter');
$dateAfter->setRule('dateAfter', '28-02-2023');
$dateAfter->setAttribute('placeholder', '28-02-2023');
$dateAfter->setDescription('Validator to check if the value is a valid date and after a given date');
$dateAfter->setNotes('Valid value: 31-03-2023, invalid value: 31-01-2023');
$form->add($dateAfter);

$contains = new \FrontendForms\InputText('contains');
$contains->setLabel('Validator contains');
$contains->setRule('contains', 'minute');
$contains->setAttribute('placeholder', 'minute');
$contains->setDescription('Validator to check if the value contains a given string');
$contains->setNotes('Valid value: a minute to midnight, invalid value: after an hour');
$form->add($contains);

$urlActive = new \FrontendForms\InputText('urlActive');
$urlActive->setLabel('Validator urlActive');
$urlActive->setRule('urlActive');
$urlActive->setDescription('Validator to check if the value is a valid url and the url is active');
$urlActive->setNotes('Valid value: http://www.google.com, invalid value: https://abcdefghixyz.com/');
$form->add($urlActive);

$emailDNS = new \FrontendForms\InputText('emailDNS');
$emailDNS->setLabel('Validator emailDNS');
$emailDNS->setRule('emailDNS');
$emailDNS->setDescription('Validator to check if the value is a valid email address and the address has an active DNS record');
$emailDNS->setNotes('Valid value: myemail@gmx.at, invalid value: myemail@com.com');
$form->add($emailDNS);

$regex = new \FrontendForms\InputText('regex');
$regex->setLabel('Validator regex');
$regex->setRule('regex','/^[0-9]*$/');
$regex->setDescription('Validator to check if the value matches the regex (in this case only numbers)');
$regex->setNotes('Valid value: 123, invalid value: w12a');
$form->add($regex);

$username = new \FrontendForms\InputText('username');
$username->setLabel('Validator usernameSyntax');
$username->setRule('usernameSyntax');
$username->setDescription('Validator to check if the value matches the regex (in this case only numbers)');
$username->setNotes('Valid value: test_01, invalid value: $user-1');
$form->add($username);

$exactValue = new \FrontendForms\InputText('exactValue');
$exactValue->setLabel('Validator exactValue');
$exactValue->setRule('exactValue', 'test');
$exactValue->setAttribute('placeholder', 'test');
$exactValue->setDescription('Validator to check if the value is exactly the same as given');
$exactValue->setNotes('Valid value: test, invalid value: wrong');
$form->add($exactValue);

$differentValue = new \FrontendForms\InputText('differentValue');
$differentValue->setLabel('Validator differentValue');
$differentValue->setRule('differentValue', 'test');
$differentValue->setAttribute('placeholder', 'test');
$differentValue->setDescription('Validator to check if the value is different from the given');
$differentValue->setNotes('Valid value: nottest, invalid value: test');
$form->add($differentValue);

$allowedFileExt = new \FrontendForms\InputFile('allowedFileExt');
$allowedFileExt->setLabel('Validator allowedFileExt');
$allowedFileExt->setRule('allowedFileExt', ['.jpg', '.doc']);
$allowedFileExt->setDescription('Validator to check if the file is of one of the allowed extensions');
$allowedFileExt->setNotes('Valid extensions: jpg, doc');
$form->add($allowedFileExt);

$uniqueFileName = new \FrontendForms\InputFile('uniqueFilename');
$uniqueFileName->setLabel('Validator uniqueFilenameInDir');
$uniqueFileName->setRule('uniqueFilenameInDir');
$uniqueFileName->setDescription('Validator to check if the file has the same name as a file inside the destination directory');
$uniqueFileName->setNotes('If you want to force the filename to be overwritten, add "true" as the second parameter.');
$form->add($uniqueFileName);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);


if ($form->isValid()) {



}

$content .= $form->render();
echo $content;

