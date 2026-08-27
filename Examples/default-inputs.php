<?php
declare(strict_types=1);

/*
 * Showcase of all pre-defined input types
 *
 * Created by Jürgen K.
 * https://github.com/juergenweb 
 * File name: default-inputs.php
 * Created: 09.03.2023 
 */

$content = '<h2>A form containing all ready-to-use pre-defined input fields</h2>';
$content .= '<p>Pre-defined input fields are inputs, which have all properties, sanitizers and validation rules added by default. </p>';
$content .= '<p>The big advantage is, that you do not need to create them by yourself and it is very consistent site wide</p>';
$content .= '<p>This form should be a showcase for devs, which input types are ready to use. I recommend you to use a pre-definde input type
instead of a self created, whenever possible.<br>
By the way: it is always possible to adapt or extend a pre-defined input type.</p>';
$content .= '<p>Just to mention: the following form does not make sense. It is only for demonstration purposes of the inputfields ;-).</p>'.

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

// add the language field
$lang = new \FrontendForms\Language('language');
$form->add($lang);

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
$uploadmultiple =  new \FrontendForms\FileUploadMultiple('multiple');
$form->add($uploadmultiple);

$password = new \FrontendForms\password('password');
$form->add($password);

// IMPORTANT: A password confirmation field must have always 2 parameters
// 1) the id of the password confirmation field itself (like any other field) +
// 2) the id of the password field including the id of the form as prefix (in this case contact-password)
$passwordconfirm = new \FrontendForms\password('passwordconfirm', 'contact-password');
$form->add($passwordconfirm);

$username = new \FrontendForms\Username('username');
$form->add($username);

// add the send copy field
$sendcopy = new \FrontendForms\SendCopy('copy');
$form->add($sendcopy);

/**
* For output a accept the privacy policy option, you have 2 possibilities
* 1) output a checkbox, which the user has to check or
* 2) output a simple text string which informs the user that he accepts the Terms and Privacy Policy by submitting the form
*/

// add the privacy field as a checkbox
$privacy = new \FrontendForms\Privacy('privacy');
$form->add($privacy);

// or add the privacy hint as a pure text string
$privacyText = new \FrontendForms\PrivacyText();
$form->add($privacyText);

$button = new \FrontendForms\Button('submit');
$button->setAttribute('value', 'Send');
$form->add($button);

if ($form->isValid()) {


}

$content .= $form->render();
echo $content;
