<?php
    declare(strict_types=1);

    namespace ProcessWire;

    /*
     * File description
     *
     * Created by Jürgen K.
     * https://github.com/juergenweb
     * File name: contactform-2.php
     * Created: 15.02.2023
     */

    $content = '<h2>A simple working contact form using the WireMail class</h2>';
    $content .= '<p>Please replace the recipient email address with your own.</p>';
    $content .= '<p>This form uses pre-defined input types, so you do not have to create them by yourself.</p>';

    $form = new \FrontendForms\Form('contact');
    $form->setMaxAttempts(0); // disable max attempts

    // add the gender field
    $gender = new \FrontendForms\Gender('gender');
    $form->add($gender);

    // add the name field
    $name = new \FrontendForms\Name('firstname');
    $form->add($name);

    // add the surname field
    $surname = new \FrontendForms\Surname('lastname');
    $form->add($surname);

    $contacttype = new \FrontendForms\InputCheckboxMultiple('contacttype');
    $contacttype->addOption('Checkbox 1', '1');
    $contacttype->addOption('Checkbox 2', '2');
    $contacttype->addOption('Checkbox 3', '3');
    $form->add($contacttype);

    // add the email field
    $email = new \FrontendForms\Email('email');
    if ($user->isLoggedIn()) {
        $email->setDefaultValue($user->email);
    }
    $form->add($email);

    // add the subject field
    $subject = new \FrontendForms\Subject('subject');
    $form->add($subject);

    // add the message field
    $message = new \FrontendForms\Message('message');
    $form->add($message);

    // add single-upload field
    $uploadsingle = new \FrontendForms\FileUploadSingle('single');
    $uploadsingle->setRule('allowedFileExt', ['docx', 'pdf', 'txt'])->setCustomMessage('One of the uploaded files is not of the type doc or pdf and therefore not allowed.'); // only allow doc and pdf files
    $uploadsingle->setRule('allowedFileSize', '30000')->setCustomMessage('One of the uploaded files is larger than 20kb, which is not allowed.'); // only allow files up to 20kb (= 20000 Byte)
    $form->add($uploadsingle);

    // add multi-upload field with no restrictions
    $uploadmultiple = new \FrontendForms\FileUploadMultiple('multiple');
    $form->add($uploadmultiple);

    // add the privacy field
    $privacy = new \FrontendForms\Privacy('privacy');
    $form->add($privacy);

    // add the send copy field
    $sendcopy = new \FrontendForms\SendCopy('sendcopy');
    $form->add($sendcopy);

    $button = new \FrontendForms\Button('submit');
    $button->setAttribute('value', 'Send');
    $form->add($button);

    if ($form->isValid()) {

        /** You can grab the values with the getValue() method - this is the default (old) way */
        /*
        $body = $m->title;
        $body .= '<p>Sender: '.$form->getValue('gender').' '. $form->getValue('firstname').' '.$form->getValue('lastname').'</p>';
        $body .= '<p>Mail: '.$form->getValue('email').'</p>';
        $body .= '<p>Subject: '.$form->getValue('subject').'</p>';
        $body .= '<p>Message: '.$form->getValue('message').'</p>';
        */

        /** You can use placeholders for the labels and the values of the form fields
         * This is the modern new way - only available at version 2.1.9 or higher
         * Big advantage: You do not have to use PHP code and there are a lot of ready-to-use placeholders containing fe the current date, the domain,.....
         * But it is up to you, if you want to use placeholders or do it the old way
         *
         */

        $body = '<p>[[TITLE]]</p><ul>
            <li>[[GENDERLABEL]]: [[GENDERVALUE]]</li>
            <li>[[FIRSTNAMELABEL]]: [[FIRSTNAMEVALUE]]</li>
            <li>[[LASTNAMELABEL]]: [[LASTNAMEVALUE]]</li>
            <li>[[EMAILLABEL]]: [[EMAILVALUE]]</li>
            <li>[[SUBJECTLABEL]]: [[SUBJECTVALUE]]</li>
            <li>[[MESSAGELABEL]]: [[MESSAGEVALUE]]</li>
          </ul>';

        // send the form with WireMail
        $m = wireMail();

        if ($form->getValue('sendcopy')) {
            // send copy to sender
            $m->to($form->getValue('email'));
        }

        $m->to('myemail@example.com')// please change this email address to your own
        ->from($form->getValue('email'))
        ->subject($form->getValue('subject'))
        ->title('<h1>A new message via contact form</h1>') // this is a new property from this module
        ->bodyHTML($body)
        ->sendAttachments($form);

        if (!$m->send()) {
            $form->generateEmailSentErrorAlert(); // generates an error message if something went wrong during the sending process
        }

    }

    $content .= $form->render();

    echo $content;
