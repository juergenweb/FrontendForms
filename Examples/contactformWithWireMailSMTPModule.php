<?php
declare(strict_types=1);

namespace ProcessWire;
/*
 * Example for sending mails with WireMailSMTP class
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: contactformWithWireMailSMTPModule.php
 * Created: 04.02.2024
 */

/*
 * Demonstration of a simple contactform using the WireMailSMTP module
 * You can copy this code to a template to show the form
 * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
 * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
 */

$content =  '<h2>A simple working contact form using the WireMailSMTP class</h2>';
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

$file1 = new \FrontendForms\InputFile('fileupload');
$file1->setLabel('Multiple files upload');
$file1->setMultiple(true);
$file1->setRule('allowedFileSize', '40000');
$file1->setRule('allowedFileExt', ['pdf', 'docx','txt']);
$form->add($file1);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {

    // Using placeholders for the body text in this case, but you can also use $form->getValue('fieldname') to populate the values
    $body = '<p>[[TITLE]]</p><ul>
            <li>[[GENDERLABEL]]: [[GENDERVALUE]]</li>
            <li>[[FIRSTNAMELABEL]]: [[FIRSTNAMEVALUE]]</li>
            <li>[[LASTNAMELABEL]]: [[LASTNAMEVALUE]]</li>
            <li>[[EMAILLABEL]]: [[EMAILVALUE]]</li>
            <li>[[SUBJECTLABEL]]: [[SUBJECTVALUE]]</li>
            <li>[[MESSAGELABEL]]: [[MESSAGEVALUE]]</li>
          </ul>';

    /** Sending the mail with the custom WireMailSMTP module */
    $mail = wireMail(); // instantiate the WireMailSMTP object
    $mail->from($form->getValue('email'))
        ->fromName($form->getValue('name'))
        ->to('myemail@example.com') // please change this to your recipient email address
        ->subject($form->getValue('subject'))
        ->title('New message')
        ->bodyHTML($body)
        ->sendAttachments($form);  // send attachments with my custom method and do not use the attachment() method from the WireMailSMTP class

    if (!$mail->send())
    {
        $form->generateEmailSentErrorAlert(); // generates an error message if something went wrong during the sending process
    }

}

$content .= $form->render();

echo $content;

