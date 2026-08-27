<?php
declare(strict_types=1);

namespace ProcessWire;
/*
 * File description
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: contactform.php
 * Created: 12.07.2022 
 */

/*
 * Demonstration of a simple contactform
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content =  '<h2>A simple working contact form using the WireMail class</h2>';
$content .=  '<p>Please replace the recipient email address with your own.</p>';

$form = new \FrontendForms\Form('contact');

$gender = new \FrontendForms\InputRadioMultiple('gender');
$gender->setLabel('Gender')->setAttribute('class', 'myextralabelclass');
$gender->setDefaultValue('Mister');
$gender->addOption('Mister', 'Mister')->setAttribute('class', 'male');
$gender->addOption('Miss', 'Miss')->setAttribute('class', 'female');
$gender->addOption('Diverse', 'Diverse')->setAttribute('class', 'diverse');
$gender->getFieldWrapper()->setAttribute('class', 'uk-width-1-1')->removeAttributeValue('class', 'uk-margin');
$form->add($gender);

$firstname = new \FrontendForms\InputText('firstname');
$firstname->setLabel('Firstname');
$firstname->setRule('required')->setCustomFieldName('The first name');
$form->add($firstname);

$lastname = new \FrontendForms\InputText('lastname');
$lastname->setLabel('Lastname');
$lastname->setRule('required')->setCustomFieldName('The last name');
$form->add($lastname);

$email = new \FrontendForms\InputEmail('email');
$email->setLabel('Email address');
if($user->isLoggedIn())
    $email->setAttribute('value', $user->email);
$email->setSanitizer('email');
$email->setRule('required')->setCustomFieldName('The Email address');
$form->add($email);

$subject = new \FrontendForms\InputText('subject');
$subject->setLabel('Subject');
$subject->setRule('required')->setCustomFieldName('The subject');
$form->add($subject);

$message = new \FrontendForms\Textarea('message');
$message->setLabel('Message');
$message->setRule('required')->setCustomFieldName('The message');
$form->add($message);

$file1 = new \FrontendForms\InputFile('fileupload1');
$file1->setLabel('Multiple files upload');
$file1->setMultiple(true);
$file1->setRule('allowedFileSize', '40000');
$file1->setRule('allowedFileExt', ['pdf', 'docx','txt']);
$form->add($file1);

$file2 = new \FrontendForms\InputFile('fileupload2');
$file2->setLabel('Single file upload');
$file2->setRule('allowedFileSize', '10000');
$form->add($file2);

$accept = new \FrontendForms\InputCheckbox('accept');
$accept->setLabel('I accept the data privacy');
$accept->setRule('required')->setCustomMessage('You have to accept the data privacy');
$form->add($accept);

$sendcopy = new \FrontendForms\InputCheckbox('sendcopy');
$sendcopy->setLabel('Send a copy to me');
$form->add($sendcopy);

// you can also add text elements to the form - not only input fields
$text = new \FrontendForms\TextElements();
$text->setContent('This is a text.');
$form->add($text);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {

    /** You can grab the values with the getValue() method - this is the default (old) way */
    /*
    $body = $m->title;
    $body .= '<p>'.'Sender: '.$form->getValue('gender').' '. $form->getValue('firstname').' '.$form->getValue('lastname').'</p>';
    $body .= '<p>'.'Mail: '.$form->getValue('email').'</p>';
    $body .= '<p>'.'Subject: '.$form->getValue('subject').'</p>';
    $body .= '<p>'.'Message: '.$form->getValue('message').'</p>';
    */

    /** You can use placeholders for the labels and the values of the form fields
     * This is the modern way - only available at version 2.1.9 or higher
     * Big advantage: The placeholders can be easier integrated in HTML as PHP code
     * But it is up to you, if you want to use placeholders or do it the old way
     * If you want to use the placeholder, please read the doc first ;-)
     */
    $body = '<p>[[TITLE]]</p><ul>
            <li>[[GENDERLABEL]]: [[GENDERVALUE]]</li>
            <li>[[FIRSTNAMELABEL]]: [[FIRSTNAMEVALUE]]</li>
            <li>[[LASTNAMELABEL]]: [[LASTNAMEVALUE]]</li>
            <li>[[EMAILLABEL]]: [[EMAILVALUE]]</li>
            <li>[[SUBJECTLABEL]]: [[SUBJECTVALUE]]</li>
            <li>[[MESSAGELABEL]]: [[MESSAGEVALUE]]</li>
          </ul>';

    // send the form with wireMail
    $m = wireMail();

    // send a copy to the sender if set
    if($form->getValue('sendcopy')){
        // send copy to sender
        $m->to($form->getValue('email'));
    }

    $m->to('myemail@example.com')// please change this email address to your own
    ->from($form->getValue('email'))
        ->subject($form->getValue('subject'))
        ->title('<h1>A new message via contact form</h1>') // this is a new property for the mail object from this module
        ->bodyHTML($body)
        ->sendAttachments($form, true);

    if (!$m->send())
    {
        $form->generateEmailSentErrorAlert(); // generates an error message if something went wrong during the sending process
    }

}

$content = $form->render();
echo $content;

