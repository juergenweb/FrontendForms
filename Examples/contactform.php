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

echo  '<h2>A simple working contact form using the WireMail class</h2>';
echo '<p>Please replace the recipient email address with your own.</p>';

$form = new \FrontendForms\Form('contact');
$form->setAttribute('enctype', 'multipart/form-data');

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
$email->setRule('email');
$email->setRule('emailDNS');
$form->add($email);

$subject = new \FrontendForms\InputText('subject');
$subject->setLabel('Subject');
$subject->setRule('required')->setCustomFieldName('The subject');
$form->add($subject);

$message = new \FrontendForms\Textarea('message');
$message->setLabel('Message');
$message->setRule('required')->setCustomFieldName('The message');
$form->add($message);

$files = new \FrontendForms\InputFile('fileupload1');
$files->setLabel('Multiple files upload');
$files->allowMultiple(true);
$form->add($files);

$file = new \FrontendForms\InputFile('fileupload2');
$file->setLabel('Single file upload');
$form->add($file);

$accept = new \FrontendForms\InputCheckbox('accept');
$accept->setLabel('I accept the data privacy');
$accept->setRule('required')->setCustomMessage('You have to accept the data privacy');
$accept->setRule('accepted');
$form->add($accept);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {

    // send the form with wireMail
    $m = wireMail();
    $m->to('myemail@example.com');// please change this email address to your own
    $m->from($form->getValue('email'));
    $m->subject($form->getValue('subject'));
    $m->title('<h1>'.$this->_('A new message via contact form').'</h1>'); // this is a new property from this module

    /** You can grab the values with the getValue() method - this is the default way */
    /*
    $body = $m->title;
    $body .= '<p>'.$this->_('Sender').': '.$form->getValue('gender').' '. $form->getValue('firstname').' '.$form->getValue('lastname').'</p>';
    $body .= '<p>'.$this->_('Mail').': '.$form->getValue('email').'</p>';
    $body .= '<p>'.$this->_('Subject').': '.$form->getValue('subject').'</p>';
    $body .= '<p>'.$this->_('Message').': '.$form->getValue('message').'</p>';
    */

    /** NEW: or you can use placeholders for the labels and the values of the form fields
     * This is the new way - only available at version 2.1.9 or higher
     * Big advantage: The placeholders can be easier integrated in HTML as PHP code
     * But it is up to you, if you want to use placeholders or do it the old way
     */

    $body = '<p>[[TITLE]]</p><ul>
            <li>[[GENDERLABEL]]: [[GENDERVALUE]]</li>
            <li>[[FIRSTNAMELABEL]]: [[FIRSTNAMEVALUE]]</li>
            <li>[[LASTNAMELABEL]]: [[LASTNAMEVALUE]]</li>
            <li>[[EMAILLABEL]]: [[EMAILVALUE]]</li>
            <li>[[SUBJECTLABEL]]: [[SUBJECTVALUE]]</li>
            <li>[[MESSAGELABEL]]: [[MESSAGEVALUE]]</li>
          </ul>';

    $m->bodyHTML($body);
    $m->sendAttachments();

    if (!$m->send())
    {
        $this->generateEmailSentErrorAlert(); // generates an error message if something went wrong during the sending process
    }

}

echo $form->render();
