<?php
    declare(strict_types=1);

    namespace ProcessWire;
    /*
    * Demonstration example on how to create a form using the simple question CAPTCHA with multiple
    * question, that will be displayed randomly
    *
    * Created by Jürgen K.
    * https://github.com/juergenweb
    * File name: randomsimplequestioncaptcha.php
    * Created: 27.05.2024
    */

    /*
    * You can copy this code to a template to show the form
    * If you use these classes inside another namespace (fe ProcessWire) like in this case, you have to take care about the namespace in front of the class instances.
    * Fe you have to use $form = new \FrontendForms\Form('myForm'); or $form = new FrontendForms\Form('myForm'); and not only $form = new Form('myForm');
    */

    $content = '<h2>A simple working contact form with a simple question Captcha and multiple questions</h2>';
    $content .= '<p>Please note: This only works if you have chosen the simple question CAPTCHA as the CAPTCHA type that should be used.</p>';

    // form setup
    $form = new \FrontendForms\Form("contactform");
    $form->setErrorMsg("<p>Sorry, but there were some errors!</p>");
    $form->setSuccessMsg("<p>Thank you! Right now this form doesn't really do anything but someday it might!</p>");


    // set several questions as a multidimensional assoc. array
    // as minimum requirements: each item must have at least a question and an answer key
    // all other keys are optional
    // you can define following optional keys:
    // * errorMsg: define a custom error message for this question
    // * successMsg: define a custom success message for this question
    // * notes: define a custom notes text for this question
    // * description: define a custom description text for this question
    // * descriptionPosition: define an individual position for the description for this question (beforeLabel, afterLabel, afterInput)
    // * placeholder: define a custom placeholder
    // Please take care of the cameltoe writing of this key names, otherwise it will not work
    // Please take also care of the structur of the array
    // Tip: You can also load this question array from another source

$questions = [
    [
        'question' => 'How many eyes does a person have?',
        'answers' => ['2', 'two', '2 eyes', 'two eyes'],
        'errorMsg' => 'Unfortunately not the right answer! Take a look at the mirror ;-).',
        'successMsg' => 'Perfect! You know how humans look like.',
    ],
    [
        'question' => 'How many legs does a dog have?',
        'answers' => ['4', 'four', '4 legs', 'four legs'],
        'notes' => 'Tip: A dog has more than 3 but less than 5 legs',
        'successMsg' => 'Yes you are absolute right! (Most) Dogs have 4 legs.',
        'errorMsg' => 'Not really! It seems that you do not have seen a dog before ;-).',
    ],
    [
        'question' => 'What is the last day of the week called?',
        'answers' => ['Sunday', 'its Sunday', 'it is Sunday'],
        'notes' => 'A little tip: S**day',
        'errorMsg' => 'No! Not really. A look at a calendar would probably help ;-).',
        'successMsg' => 'It is Sunday! Absolut correct!',
    ],
    [
        'question' => 'How many halves make a whole?',
        'answers' => ['2', 'two', '2 halves', 'two halves']
    ],

];

    // This is the magic method, which turns your single question CAPTCHA into the random multi question CAPTCHA
    // Works only if you have chosen the simple question CAPTCHA as the CAPTCHA type
    $form->setSimpleQuestionCaptchaRandomRotation($questions);

    // Now create the form fields as usual

    // add name field
    $name = new \FrontendForms\InputText("name");
    $name->setAttribute("placeholder", "Name");
    $name->setRule("required")->setCustomMessage("Please enter your name");
    $name->setSuccessMessage("Looking good!");
    $form->add($name);

// add email field
    $email = new \FrontendForms\InputText("email");
    $email->setAttribute("placeholder", "E-Mail");
    $email->setRule("required")->setCustomMessage("Please enter your e-mail");
    $email->setRule("email")->setCustomMessage("Please enter a valid e-mail address");
    $form->add($email);

// add message field
    $message = new \FrontendForms\Textarea("message");
    $message->setAttribute("placeholder", "Your message...");
    $message->setRule("required")->setCustomMessage("Please enter your message");
    $form->add($message);

// add privacy checkbox as switch
    $privacy = new \FrontendForms\InputCheckbox("privacy");
    $privacy->setAttribute("role", "switch");
    $privacy->setLabel("I accept the privacy policy");
    $privacy->setRule("required")->setCustomMessage("You have to accept our privacy policy");
    $form->add($privacy);

// add submit button
    $button = new \FrontendForms\Button("submit");
    $button->setAttribute("value", "Send");
    $form->add($button);


// validate the form
    if ($form->isValid()) {
        bd($form->getValues());
        // do what you want
    }

    // render the form
    $content .= $form->render();
    echo $content;
